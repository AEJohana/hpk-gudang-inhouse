<?php

namespace App\Console\Commands;

use App\Models\Component;
use App\Models\Location;
use App\Models\StockBalance;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ImportCsvKomponen extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'import:csv-komponen {--file= : Path to CSV file}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import 613 master komponen karoseri dari file CSV acuan ke database HPK WMS';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $filePath = $this->option('file') ?: base_path('Data acuan/Form Work Request - Data komponen.csv');

        if (!file_exists($filePath)) {
            $this->error("File CSV tidak ditemukan di: {$filePath}");
            return 1;
        }

        $this->info("Memulai proses import data acuan komponen...");
        $this->line("Sumber File: <comment>{$filePath}</comment>");

        $f = fopen($filePath, 'r');
        if (!$f) {
            $this->error("Gagal membuka file CSV!");
            return 1;
        }

        // Ensure HOLDING location exists
        $holdingLoc = Location::ensureUnassignedLocationExists();

        $lineNum = 0;
        $totalProcessed = 0;
        $seenCodes = [];
        $duplicatesResolved = 0;
        $allocatedCount = 0;
        $unallocatedCount = 0;
        $multiRackCount = 0;
        $stockBalancesCreated = 0;
        $locationsCreated = 0;

        // Collect rows first to determine progress bar count
        $rows = [];
        while (($row = fgetcsv($f)) !== false) {
            $lineNum++;
            if ($lineNum <= 2) continue; // Skip header / title
            if (count(array_filter($row)) === 0) continue; // Skip empty rows
            $rows[] = array_merge(['csv_line' => $lineNum], $row);
        }
        fclose($f);

        $this->info("Ditemukan " . count($rows) . " baris data komponen di file CSV.");
        $bar = $this->output->createProgressBar(count($rows));
        $bar->start();

        DB::beginTransaction();

        try {
            // Clean up any legacy unsuffixed duplicate records from earlier runs
            Component::whereIn('part_number', [
                'CLOP10180100120', 'CRDA10200390000', 'CSPA40150320000', 'SD3RD0240060550'
            ])->delete();

            foreach ($rows as $r) {
                $rawCode = trim($r[2] ?? '');
                $rawName = trim($r[3] ?? '');
                $rawImg = trim($r[4] ?? ''); // Kolom 4: Gambar Komponen (Google Drive URL)
                $rawPos = trim($r[5] ?? ''); // Kolom 5: Posisi Rak
                $rawStock = trim($r[6] ?? ''); // Kolom 6: Saldo
                $rawMinStock = trim($r[7] ?? ''); // Kolom 7: Saldo Minimum
                $rawP = trim($r[8] ?? ''); // Kolom 8: Panjang
                $rawL = trim($r[9] ?? ''); // Kolom 9: Diameter / Lebar
                $rawT = trim($r[10] ?? ''); // Kolom 10: Tebal

                if (!$rawCode) {
                    continue;
                }

                // Default UoM for Karoseri fabrication parts
                $rawUom = 'PCS';
                if (str_contains(strtoupper($rawName), '(SET)') || str_contains(strtoupper($rawName), ' ASSY')) {
                    $rawUom = 'SET';
                }

                // 1. Resolve Duplicate Part Numbers with explicit symmetrical suffixes (Isu 2)
                $code = $rawCode;
                if ($rawCode === 'CLOP10180100120') {
                    $code = str_contains(strtoupper($rawName), 'LODGE B') ? 'CLOP10180100120-B' : 'CLOP10180100120-A';
                    $duplicatesResolved++;
                } elseif ($rawCode === 'CRDA10200390000') {
                    $code = str_contains(strtoupper($rawName), 'KANAN') ? 'CRDA10200390000R' : 'CRDA10200390000L';
                    $duplicatesResolved++;
                } elseif ($rawCode === 'CSPA40150320000') {
                    $code = str_contains($rawT, '7') || str_contains(strtoupper($rawName), 'D7') ? 'CSPA40150320000-D7' : 'CSPA40150320000-D25';
                    $duplicatesResolved++;
                } elseif ($rawCode === 'SD3RD0240060550') {
                    $code = str_contains(strtoupper($rawName), 'ULIR') ? 'SD3RD0240060550-1' : 'SD3RD0240060550-2';
                    $duplicatesResolved++;
                } elseif (isset($seenCodes[$rawCode])) {
                    $duplicatesResolved++;
                    $code = $rawCode . '-DUPL-' . $duplicatesResolved;
                }
                $seenCodes[$code] = true;

                // 2. Resolve Minimum Stock (Isu 5)
                $minStock = 0;
                if ($rawMinStock !== '' && $rawMinStock !== '#N/A' && is_numeric($rawMinStock)) {
                    $minStock = max(0, (int) $rawMinStock);
                }

                // 3. Resolve Dimensions (Isu 4: string fleksibel)
                $panjang = $rawP !== '' ? $rawP : null;
                $lebarDiameter = $rawL !== '' ? $rawL : null;
                $tebalTinggi = $rawT !== '' ? $rawT : null;

                // 4. Resolve Rack Locations (Isu 3)
                $rackTokens = array_filter(array_map('trim', explode(';', $rawPos)));
                $parsedRacks = [];

                foreach ($rackTokens as $tok) {
                    $info = $this->normalizeRackToken($tok);
                    if (!$info['is_unallocated']) {
                        $parsedRacks[] = $info;
                    }
                }

                $primaryLocationId = null;
                $primaryLevel = null;
                $primarySlot = null;

                if (!empty($parsedRacks)) {
                    $allocatedCount++;
                    $first = $parsedRacks[0];

                    // Find or create location in locations table
                    $locRecord = $this->findOrCreateLocation($first['zone'], $first['rack_code'], $first['rack_number'], $locationsCreated);
                    $primaryLocationId = $locRecord->id;
                    $primaryLevel = $first['level'];
                    $primarySlot = $first['slot'];

                    if (count($parsedRacks) > 1) {
                        $multiRackCount++;
                    }
                } else {
                    $unallocatedCount++;
                }

                // 5. Create or Update Component Record
                $component = Component::updateOrCreate(
                    ['part_number' => $code],
                    [
                        'name' => $rawName,
                        'category' => null, // Isu 1: Nullable with proactive alert badge
                        'uom' => $rawUom,
                        'length_mm' => $panjang,
                        'width_or_diameter_mm' => $lebarDiameter,
                        'thickness_or_height_mm' => $tebalTinggi,
                        'specification' => count($parsedRacks) > 1 ? "Catatan Multi-Lokasi Rak: {$rawPos}" : null,
                        'minimum_stock' => $minStock,
                        'maximum_stock' => null,
                        'default_location_id' => $primaryLocationId,
                        'default_shelf_level' => $primaryLevel,
                        'default_slot_number' => $primarySlot,
                        'image_path' => $rawImg ?: null,
                        'qr_code_payload' => "HPK-PART|{$code}",
                        'is_active' => true,
                    ]
                );

                // 6. Process Initial Stock Balances
                $stockQty = 0;
                if ($rawStock !== '' && is_numeric(str_replace(',', '.', $rawStock))) {
                    $stockQty = max(0, (float) str_replace(',', '.', $rawStock));
                }

                // Remove previous initial stock balances for this component to prevent duplication on rerun
                StockBalance::where('component_id', $component->id)
                    ->where('batch_lot_number', 'like', 'LOT-INIT%')
                    ->delete();

                if ($stockQty > 0) {
                    if (empty($parsedRacks)) {
                        // Place in HOLDING area
                        StockBalance::create([
                            'component_id' => $component->id,
                            'location_id' => $holdingLoc->id,
                            'quantity' => $stockQty,
                            'shelf_level' => 'L1',
                            'slot_number' => '01',
                            'specific_location_code' => 'HOLDING-TRANSIT',
                            'batch_lot_number' => 'LOT-INIT-HOLDING',
                        ]);
                        $stockBalancesCreated++;
                    } elseif (count($parsedRacks) === 1) {
                        // Single physical rack
                        $firstRack = $parsedRacks[0];
                        StockBalance::create([
                            'component_id' => $component->id,
                            'location_id' => $primaryLocationId,
                            'quantity' => $stockQty,
                            'shelf_level' => $firstRack['level'],
                            'slot_number' => $firstRack['slot'],
                            'specific_location_code' => $firstRack['full_code'],
                            'batch_lot_number' => 'LOT-INIT-' . date('Ym'),
                        ]);
                        $stockBalancesCreated++;
                    } else {
                        // Multi-location: split stock proportionally across all allocated slots
                        $slotCount = count($parsedRacks);
                        $qtyPerSlot = floor($stockQty / $slotCount);
                        $remainder = $stockQty - ($qtyPerSlot * $slotCount);

                        foreach ($parsedRacks as $idx => $rInfo) {
                            $slotQty = $qtyPerSlot + ($idx === 0 ? $remainder : 0);
                            $slotLoc = $this->findOrCreateLocation($rInfo['zone'], $rInfo['rack_code'], $rInfo['rack_number'], $locationsCreated);

                            StockBalance::create([
                                'component_id' => $component->id,
                                'location_id' => $slotLoc->id,
                                'quantity' => $slotQty,
                                'shelf_level' => $rInfo['level'],
                                'slot_number' => $rInfo['slot'],
                                'specific_location_code' => $rInfo['full_code'],
                                'batch_lot_number' => 'LOT-INIT-MULTI',
                            ]);
                            $stockBalancesCreated++;
                        }
                    }
                }

                $totalProcessed++;
                $bar->advance();
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            $this->error("\nTerjadi error saat import: " . $e->getMessage());
            $this->error("File: " . $e->getFile() . ":" . $e->getLine());
            return 1;
        }

        $bar->finish();
        $this->newLine(2);

        $this->info("=================================================");
        $this->info("  PROSES IMPORT DATA ACUAN KOMPONEN BERHASIL!  ");
        $this->info("=================================================");
        $this->table(
            ['Metrik', 'Jumlah'],
            [
                ['Total Baris CSV Berhasil Diimport', $totalProcessed],
                ['Duplikasi Kode Selesai (Diberi Suffix)', $duplicatesResolved],
                ['Komponen Terpetakan ke Rak Fisik', $allocatedCount],
                ['Komponen Belum Ditempatkan (Area HOLDING)', $unallocatedCount],
                ['Komponen Multi-Lokasi Rak (Pemisah ;)', $multiRackCount],
                ['Catatan Saldo Fisik (Stock Balances) Dibuat', $stockBalancesCreated],
                ['Lokasi Rak Baru Dibuat Otomatis', $locationsCreated],
            ]
        );

        return 0;
    }

    /**
     * Normalize string from CSV rack column to structured array
     */
    protected function normalizeRackToken(string $raw): array
    {
        $raw = trim($raw);
        if ($raw === '' || $raw === '-' || $raw === '#N/A') {
            return ['is_unallocated' => true];
        }

        if (strtoupper($raw) === 'BOX BIRU') {
            return [
                'is_unallocated' => false,
                'zone' => '1',
                'rack_code' => 'BOX-BIRU',
                'rack_number' => 'Box Biru (Container)',
                'level' => 'L1',
                'slot' => '01',
                'full_code' => '1-BOX-BIRU-L1-01',
            ];
        }

        $cleaned = preg_replace('/--+/', '-', $raw);
        if (preg_match('/^([12])R([0-9]+)-(.*)$/i', $cleaned, $m)) {
            $cleaned = $m[1] . '-R' . $m[2] . '-' . $m[3];
        }

        $parts = explode('-', $cleaned);
        $zone = $parts[0] ?? '1';
        $rack = $parts[1] ?? 'R1';
        $level = $parts[2] ?? 'L1';
        $slot = $parts[3] ?? '01';

        return [
            'is_unallocated' => false,
            'zone' => $zone,
            'rack_code' => $rack,
            'rack_number' => 'Rak ' . preg_replace('/[^0-9]/', '', $rack),
            'level' => $level,
            'slot' => $slot,
            'full_code' => "{$zone}-{$rack}-{$level}-{$slot}",
        ];
    }

    /**
     * Find existing location or create if not exists
     */
    protected function findOrCreateLocation(string $zone, string $rackCode, string $rackNumber, int &$locationsCreated): Location
    {
        $loc = Location::where('zone_code', $zone)
            ->where(function ($q) use ($rackCode, $rackNumber) {
                $q->where('rack_code', $rackCode)
                  ->orWhere('rack_code', 'R' . preg_replace('/[^0-9]/', '', $rackCode))
                  ->orWhere('rack_number', $rackNumber);
            })
            ->first();

        if ($loc) {
            return $loc;
        }

        $warehouse = \App\Models\Warehouse::first();
        $newLoc = Location::create([
            'warehouse_id' => $warehouse?->id ?? 1,
            'zone_code' => $zone,
            'zone_name' => "Area {$zone}",
            'rack_code' => $rackCode,
            'rack_number' => $rackNumber,
            'storage_type' => 'rack',
            'description' => "Rak {$rackCode} Area {$zone} (Karoseri)",
            'total_levels' => 4,
            'slots_per_level' => 10,
        ]);

        $locationsCreated++;
        return $newLoc;
    }
}
