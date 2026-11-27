<?php

namespace App\Console\Commands;

use App\Models\Component;
use App\Models\Location;
use App\Models\StockBalance;
use App\Models\Uom;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ImportCsvKomponen extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'import:csv-komponen
                            {--file= : Path ke berkas CSV komponen acuan}
                            {--dry-run : Jalankan simulasi validasi tanpa menulis perubahan ke database}
                            {--preserve-stock : Pertahankan saldo stock_balances eksisting (jangan timpa stok dari CSV)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import master komponen karoseri dari file CSV acuan ke database HPK WMS secara aman dan tervalidasi';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $filePath = $this->option('file') ?: base_path('Data acuan/Form Work Request - Data komponen.csv');
        $dryRun = (bool) $this->option('dry-run');
        $preserveStock = (bool) $this->option('preserve-stock');

        if (!file_exists($filePath)) {
            $this->error("File CSV tidak ditemukan di: {$filePath}");
            return 1;
        }

        $this->info("Memulai proses import data acuan komponen...");
        $this->line("Sumber File: <comment>{$filePath}</comment>");
        if ($dryRun) {
            $this->warn(">>> MODE SIMULASI (DRY-RUN) AKTIF: Database tidak akan dimodifikasi <<<");
        }
        if ($preserveStock) {
            $this->info(">>> MODE PRESERVE STOCK AKTIF: Saldo stock_balances eksisting tidak akan ditimpa <<<");
        }

        $f = fopen($filePath, 'r');
        if (!$f) {
            $this->error("Gagal membuka file CSV!");
            return 1;
        }

        $lineNum = 0;
        $headerRow = null;
        $totalProcessed = 0;
        $seenCodes = [];
        $duplicatesResolved = 0;
        $allocatedCount = 0;
        $unallocatedCount = 0;
        $multiRackCount = 0;
        $stockBalancesCreated = 0;
        $locationsCreated = 0;
        $warnings = [];

        // Collect rows first to validate header & determine progress bar count
        $rows = [];
        while (($row = fgetcsv($f)) !== false) {
            $lineNum++;
            if ($lineNum === 1) {
                continue;
            }
            if ($lineNum === 2) {
                $headerRow = $row;
                continue;
            }
            if (count(array_filter($row)) === 0) continue; // Skip empty rows
            $rows[] = array_merge(['csv_line' => $lineNum], $row);
        }
        fclose($f);

        // Pre-flight dynamic header mapping (aman terhadap pergeseran kolom antara layout lama dan baru)
        $colMap = $this->resolveColumnIndexes($headerRow);
        if ($colMap['code'] === null || $colMap['name'] === null) {
            $this->error("Format header baris ke-2 tidak sesuai standar! Harus memuat kolom 'Kode Komponen' dan 'Nama Komponen'.");
            return 1;
        }
        $this->line("Pemetaan Kolom Terdeteksi: Kode=[{$colMap['code']}], Nama=[{$colMap['name']}], Rak=[" . ($colMap['rack'] ?? '-') . "], Saldo=[" . ($colMap['stock'] ?? '-') . "], Min=[" . ($colMap['min_stock'] ?? '-') . "]");

        $holdingLoc = !$dryRun ? Location::ensureUnassignedLocationExists() : null;
        $simulatedLocations = [];
        $hasUomTable = Schema::hasTable('uoms');

        $this->info("Ditemukan " . count($rows) . " baris data komponen di file CSV.");
        $bar = $this->output->createProgressBar(count($rows));
        $bar->start();

        if (!$dryRun) {
            DB::beginTransaction();
        }

        try {
            // Hitung frekuensi kode mentah terlebih dahulu agar suffix duplikat hanya diterapkan jika benar-benar ada duplikasi di CSV
            $rawCodeCounts = [];
            foreach ($rows as $rowCheck) {
                $rc = trim($rowCheck[$colMap['code']] ?? '');
                if ($rc !== '') {
                    $rawCodeCounts[$rc] = ($rawCodeCounts[$rc] ?? 0) + 1;
                }
            }

            // Clean up any legacy unsuffixed duplicate records only when those codes are genuinely duplicated in CSV
            $duplicatedLegacy = array_intersect(
                ['CLOP10180100120', 'CRDA10200390000', 'CSPA40150320000', 'SD3RD0240060550'],
                array_keys(array_filter($rawCodeCounts, fn ($cnt) => $cnt > 1))
            );
            if (!$dryRun && !empty($duplicatedLegacy)) {
                Component::whereIn('part_number', $duplicatedLegacy)->delete();
            }

            foreach ($rows as $r) {
                $rawCode = trim($r[$colMap['code']] ?? '');
                $rawName = trim($r[$colMap['name']] ?? '');
                $rawImg = $colMap['image'] !== null ? trim($r[$colMap['image']] ?? '') : '';
                $rawPos = $colMap['rack'] !== null ? trim($r[$colMap['rack']] ?? '') : '';
                $rawStock = $colMap['stock'] !== null ? trim($r[$colMap['stock']] ?? '') : '';
                $rawMinStock = $colMap['min_stock'] !== null ? trim($r[$colMap['min_stock']] ?? '') : '';
                $rawP = $colMap['length'] !== null ? trim($r[$colMap['length']] ?? '') : '';
                $rawL = $colMap['width'] !== null ? trim($r[$colMap['width']] ?? '') : '';
                $rawT = $colMap['thickness'] !== null ? trim($r[$colMap['thickness']] ?? '') : '';

                if (!$rawCode) {
                    $warnings[] = "Baris CSV #{$r['csv_line']}: Kode Komponen kosong (dilewati).";
                    $bar->advance();
                    continue;
                }

                // Default UoM for Karoseri fabrication parts
                $rawUom = 'PCS';
                if (str_contains(strtoupper($rawName), '(SET)') || str_contains(strtoupper($rawName), ' ASSY')) {
                    $rawUom = 'SET';
                }

                // 1. Resolve Duplicate Part Numbers only when rawCode actually appears > 1 time in CSV
                $code = $rawCode;
                if (($rawCodeCounts[$rawCode] ?? 0) > 1) {
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
                        $warnings[] = "Baris CSV #{$r['csv_line']}: Kode duplikat '{$rawCode}' diubah otomatis menjadi '{$code}'.";
                    }
                } elseif (isset($seenCodes[$rawCode])) {
                    $duplicatesResolved++;
                    $code = $rawCode . '-DUPL-' . $duplicatesResolved;
                    $warnings[] = "Baris CSV #{$r['csv_line']}: Kode duplikat '{$rawCode}' diubah otomatis menjadi '{$code}'.";
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

                    if (!$dryRun) {
                        $locRecord = $this->findOrCreateLocation($first['zone'], $first['rack_code'], $first['rack_number'], $locationsCreated);
                        $primaryLocationId = $locRecord->id;
                    } else {
                        $this->simulateFindOrCreateLocation($first['zone'], $first['rack_code'], $first['rack_number'], $simulatedLocations, $locationsCreated);
                    }
                    $primaryLevel = $first['level'];
                    $primarySlot = $first['slot'];

                    if (count($parsedRacks) > 1) {
                        $multiRackCount++;
                    }
                } else {
                    $unallocatedCount++;
                }

                $uomId = null;
                if (!$dryRun && $hasUomTable) {
                    $uomModel = Uom::firstOrCreate(
                        ['code' => strtoupper($rawUom)],
                        ['name' => $rawUom]
                    );
                    $uomId = $uomModel->id;
                }

                // 5. Create or Update Component Record (Prinsip Satu Pintu: source = 'master')
                if (!$dryRun) {
                    $component = Component::updateOrCreate(
                        ['part_number' => $code],
                        [
                            'name' => $rawName,
                            'category' => null, // Isu 1: Nullable with proactive alert badge
                            'uom' => $rawUom,
                            'uom_id' => $uomId,
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
                            'source' => 'master',
                        ]
                    );
                }

                // 6. Process Initial Stock Balances
                $stockQty = 0;
                if ($rawStock !== '' && is_numeric(str_replace(',', '.', $rawStock))) {
                    $stockQty = max(0, (float) str_replace(',', '.', $rawStock));
                }

                if (!$preserveStock) {
                    if (!$dryRun) {
                        StockBalance::where('component_id', $component->id)
                            ->where('batch_lot_number', 'like', 'LOT-INIT%')
                            ->delete();
                    }

                    if ($stockQty > 0) {
                        if (empty($parsedRacks)) {
                            if (!$dryRun) {
                                StockBalance::create([
                                    'component_id' => $component->id,
                                    'location_id' => $holdingLoc->id,
                                    'quantity' => $stockQty,
                                    'shelf_level' => 'L1',
                                    'slot_number' => '01',
                                    'specific_location_code' => 'HOLDING-TRANSIT',
                                    'batch_lot_number' => 'LOT-INIT-HOLDING',
                                ]);
                            }
                            $stockBalancesCreated++;
                        } elseif (count($parsedRacks) === 1) {
                            $firstRack = $parsedRacks[0];
                            if (!$dryRun) {
                                StockBalance::create([
                                    'component_id' => $component->id,
                                    'location_id' => $primaryLocationId,
                                    'quantity' => $stockQty,
                                    'shelf_level' => $firstRack['level'],
                                    'slot_number' => $firstRack['slot'],
                                    'specific_location_code' => $firstRack['full_code'],
                                    'batch_lot_number' => 'LOT-INIT-' . date('Ym'),
                                ]);
                            }
                            $stockBalancesCreated++;
                        } else {
                            $slotCount = count($parsedRacks);
                            $qtyPerSlot = floor($stockQty / $slotCount);
                            $remainder = $stockQty - ($qtyPerSlot * $slotCount);

                            foreach ($parsedRacks as $idx => $rInfo) {
                                $slotQty = $qtyPerSlot + ($idx === 0 ? $remainder : 0);
                                if (!$dryRun) {
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
                                } else {
                                    $this->simulateFindOrCreateLocation($rInfo['zone'], $rInfo['rack_code'], $rInfo['rack_number'], $simulatedLocations, $locationsCreated);
                                }
                                $stockBalancesCreated++;
                            }
                        }
                    }
                }

                $totalProcessed++;
                $bar->advance();
            }

            if (!$dryRun) {
                DB::commit();
            }
        } catch (\Exception $e) {
            if (!$dryRun) {
                DB::rollBack();
            }
            $this->error("\nTerjadi error saat import: " . $e->getMessage());
            $this->error("File: " . $e->getFile() . ":" . $e->getLine());
            return 1;
        }

        $bar->finish();
        $this->newLine(2);

        $this->info("=================================================");
        $this->info($dryRun ? "  SIMULASI DRY-RUN IMPORT KOMPONEN SELESAI!  " : "  PROSES IMPORT DATA ACUAN KOMPONEN BERHASIL!  ");
        $this->info("=================================================");
        $this->table(
            ['Metrik', 'Jumlah'],
            [
                ['Mode Eksekusi', $dryRun ? 'SIMULASI (Dry-Run)' : 'RIIL DATABASE'],
                ['Total Baris CSV Berhasil Diproses', $totalProcessed],
                ['Duplikasi Kode Selesai (Diberi Suffix)', $duplicatesResolved],
                ['Komponen Terpetakan ke Rak Fisik', $allocatedCount],
                ['Komponen Belum Ditempatkan (Area HOLDING)', $unallocatedCount],
                ['Komponen Multi-Lokasi Rak (Pemisah ;)', $multiRackCount],
                ['Catatan Saldo Fisik (Stock Balances)', $preserveStock ? 'Dipertahankan (--preserve-stock)' : $stockBalancesCreated],
                ['Lokasi Rak Baru Dibuat Otomatis', $locationsCreated],
            ]
        );

        if (!empty($warnings)) {
            $this->warn("Peringatan Pre-Flight (" . count($warnings) . "):");
            foreach (array_slice($warnings, 0, 15) as $w) {
                $this->line("  - {$w}");
            }
            if (count($warnings) > 15) {
                $this->line("  ... dan " . (count($warnings) - 15) . " peringatan lainnya.");
            }
        }

        return 0;
    }

    /**
     * Petakan indeks kolom CSV secara dinamis berdasarkan nama header baris ke-2.
     */
    protected function resolveColumnIndexes(?array $headerRow): array
    {
        $map = [
            'code' => null,
            'name' => null,
            'image' => null,
            'rack' => null,
            'stock' => null,
            'min_stock' => null,
            'length' => null,
            'width' => null,
            'thickness' => null,
        ];

        if (!$headerRow) {
            return $map;
        }

        foreach ($headerRow as $idx => $rawHeader) {
            $h = strtolower(trim((string) $rawHeader));
            if ($h === '' || str_contains($h, '|')) {
                continue;
            }

            if ($map['code'] === null && (str_contains($h, 'kode komponen') || $h === 'kode')) {
                $map['code'] = (int) $idx;
            } elseif ($map['name'] === null && (str_contains($h, 'nama komponen') || $h === 'nama')) {
                $map['name'] = (int) $idx;
            } elseif ($map['image'] === null && str_contains($h, 'gambar')) {
                $map['image'] = (int) $idx;
            } elseif ($map['rack'] === null && (str_contains($h, 'kode rak') || str_contains($h, 'posisi rak'))) {
                $map['rack'] = (int) $idx;
            } elseif ($map['min_stock'] === null && str_contains($h, 'saldo minimum')) {
                $map['min_stock'] = (int) $idx;
            } elseif ($map['stock'] === null && $h === 'saldo') {
                $map['stock'] = (int) $idx;
            } elseif ($map['length'] === null && str_contains($h, 'panjang')) {
                $map['length'] = (int) $idx;
            } elseif ($map['width'] === null && (str_contains($h, 'diameter') || str_contains($h, 'lebar'))) {
                $map['width'] = (int) $idx;
            } elseif ($map['thickness'] === null && (str_contains($h, 'tebal') || str_contains($h, 'tinggi'))) {
                $map['thickness'] = (int) $idx;
            }
        }

        return $map;
    }

    /**
     * Simulasi deteksi lokasi rak pada mode --dry-run tanpa menulis ke database.
     */
    protected function simulateFindOrCreateLocation(string $zone, string $rackCode, string $rackNumber, array &$simulatedLocations, int &$locationsCreated): void
    {
        $key = "{$zone}|{$rackCode}|{$rackNumber}";
        if (isset($simulatedLocations[$key])) {
            return;
        }

        $exists = Location::where('zone_code', $zone)
            ->where(function ($q) use ($rackCode, $rackNumber) {
                $q->where('rack_code', $rackCode)
                  ->orWhere('rack_code', 'R' . preg_replace('/[^0-9]/', '', $rackCode))
                  ->orWhere('rack_number', $rackNumber);
            })
            ->exists();

        if (!$exists) {
            $locationsCreated++;
        }
        $simulatedLocations[$key] = true;
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
