<?php

namespace App\Console\Commands;

use App\Models\Component;
use App\Models\Department;
use App\Models\User;
use App\Models\WorkRequest;
use App\Models\WorkRequestLog;
use App\Models\WorkRequestStep;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class MigrateLegacySpreadsheetWr extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'wr:migrate-legacy-spreadsheet
                            {--file= : Path kustom ke file Log WR.csv}
                            {--komponen-baru-file= : Path kustom ke file Komponen baru.csv}
                            {--dry-run : Jalankan simulasi validasi tanpa menyimpan perubahan ke database}
                            {--sync : Sinkronkan dan perbarui status tiket eksis jika di spreadsheet statusnya telah maju/selesai}
                            {--only-in-progress : Hanya migrasikan tiket yang belum selesai (in-progress)}
                            {--limit= : Batasi jumlah baris yang diproses}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Migrasikan seluruh data Work Request dari spreadsheet lama (Log WR.csv) ke database WMS secara aman dan bebas perubahan stok';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info("==================================================================");
        $this->info(" HPK GUDANG - MIGRASI WORK REQUEST LAMA KE DATABASE (ZERO STOCK)");
        $this->info("==================================================================");

        $logWrFile = $this->option('file') ?: base_path('System WR lama/Spreadsheet/Form Work Request - Log WR.csv');
        $kompBaruFile = $this->option('komponen-baru-file') ?: base_path('System WR lama/Spreadsheet/Form Work Request - Komponen baru.csv');

        if (!file_exists($logWrFile)) {
            $this->error("Berkas Log WR.csv tidak ditemukan: {$logWrFile}");
            return 1;
        }

        $dryRun = (bool) $this->option('dry-run');
        $sync = (bool) $this->option('sync');
        $onlyInProgress = (bool) $this->option('only-in-progress');
        $limit = $this->option('limit') ? (int) $this->option('limit') : null;

        if ($dryRun) {
            $this->warn(">>> MODE SIMULASI (DRY-RUN) AKTIF: Database tidak akan dimodifikasi <<<");
        }
        if ($sync) {
            $this->info(">>> MODE SINKRONISASI AKTIF: Status tiket eksis akan dimutakhirkan secara aman <<<");
        }
        if ($onlyInProgress) {
            $this->warn(">>> FILTER AKTIF: Hanya memproses tiket yang belum selesai (In-Progress) <<<");
        }

        // 1. Persiapan Mapping User
        $adminUser = User::where('role', 'admin_gudang')->first() ?? User::first();
        $userMap = $adminUser ? $this->buildUserMap($adminUser) : [];

        // 2. Persiapan Mapping Komponen Baru
        $this->line("1. Menyiapkan kamus data Komponen Baru...");
        $baruCodeMap = $this->buildBaruComponentMap($kompBaruFile);
        $this->info("Kamus komponen baru siap: " . count($baruCodeMap) . " item.");

        // 3. Baca dan validasi header Log WR.csv
        $this->line("2. Membaca berkas Log WR.csv...");
        $h = fopen($logWrFile, 'r');
        fgetcsv($h); // empty line 1
        $header = fgetcsv($h); // header line 2

        $hWr = strtolower(trim($header[2] ?? ''));
        $hKode = strtolower(trim($header[3] ?? ''));
        $hStatus = strtolower(trim($header[14] ?? ''));
        if (!str_contains($hWr, 'wr') || !str_contains($hKode, 'kode') || !str_contains($hStatus, 'status')) {
            fclose($h);
            $this->error("Format header baris ke-2 Log WR.csv tidak valid! Ditemukan [2]='{$hWr}', [3]='{$hKode}', [14]='{$hStatus}'.");
            return 1;
        }

        $totalRead = 0;
        $totalMigrated = 0;
        $totalUpdatedExisting = 0;
        $totalSkippedExisting = 0;
        $totalSkippedCompleted = 0;
        $totalErrors = 0;

        $inProgressStats = [
            'Belum Verifikasi' => 0,
            'Dalam antrian' => 0,
            'Proses In' => 0,
            'Siap diambil' => 0,
            'Menuju Pengaju' => 0,
        ];

        // Kumpulkan baris yang valid
        $validRows = [];
        while (($row = fgetcsv($h)) !== false) {
            $noWr = trim($row[2] ?? '');
            if (empty($noWr)) continue;
            $totalRead++;
            $validRows[] = $row;
            if ($limit && count($validRows) >= $limit) break;
        }
        fclose($h);

        $this->info("Total baris valid yang ditemukan: " . count($validRows));

        $bar = $this->output->createProgressBar(count($validRows));
        $bar->start();

        foreach ($validRows as $row) {
            $tglOrder = trim($row[1] ?? date('Y-m-d'));
            $noWr = trim($row[2] ?? '');
            $kodePart = trim($row[3] ?? '');
            $namaPart = trim($row[4] ?? 'Komponen WRI');
            $routing = trim($row[6] ?? '');
            $dimensi = trim($row[7] ?? '-');
            $qty = (float) trim($row[8] ?? 1);
            if ($qty <= 0) $qty = 1;

            $deptPengaju = trim($row[9] ?? 'Dept Gudang');
            $deptPenerima = trim($row[10] ?? 'Dept Laser Cutting');
            $deskripsi = trim($row[11] ?? '');
            $stPengaju = trim($row[12] ?? 'Diterima');
            $stPenerima = trim($row[13] ?? 'Diterima');
            $stBarang = trim($row[14] ?? 'Sudah diambil');
            $dataProses = trim($row[18] ?? '');

            $opPengaju = trim($row[20] ?? '');
            $kepalaPengaju = trim($row[21] ?? '');
            $opPenerima = trim($row[22] ?? '');
            $kepalaPenerima = trim($row[23] ?? '');
            $pengambilMh = trim($row[24] ?? '');

            // Filter In-Progress jika diminta
            $isCompleted = ($stBarang === 'Sudah diambil' || $stPengaju === 'Ditolak' || $stPenerima === 'Ditolak');
            if ($onlyInProgress && $isCompleted) {
                $totalSkippedCompleted++;
                $bar->advance();
                continue;
            }

            // Normalisasi Status Approval
            $approvalPengaju = match($stPengaju) {
                'Diterima' => 'Diterima',
                'Ditolak' => 'Ditolak',
                default => 'Menunggu',
            };
            $approvalPenerima = match($stPenerima) {
                'Diterima' => 'Diterima',
                'Ditolak' => 'Ditolak',
                default => 'Menunggu',
            };

            // Hierarki Baku: Jika Ka. Pengaju menolak, tiket batal total seketika
            if ($approvalPengaju === 'Ditolak') {
                $approvalPenerima = 'Ditolak';
                $stBarang = 'cancelled';
            } elseif ($approvalPenerima === 'Ditolak') {
                $stBarang = 'cancelled';
            }

            $isFinal = ($stBarang === 'Sudah diambil' || strtolower($stBarang) === 'sudah diambil' || $stBarang === 'cancelled' || $approvalPengaju === 'Ditolak' || $approvalPenerima === 'Ditolak');
            $dbStatus = (strtolower($stBarang) === 'sudah diambil') ? 'Sudah Diambil' : $stBarang;

            // Parsing Qty Selesai, Diterima, dan Defect dari Data Proses
            $parsedQty = $this->parseDataProsesQuantities($dataProses, $qty, $stBarang);
            $qtyProduced = $parsedQty['produced'];
            $qtyReceived = $parsedQty['received'];
            $qtyDefect = $parsedQty['defect'];
            $activeQty = max(0, $qty - $qtyDefect);
            $currentBatchQty = $this->computeCurrentBatchQty($dbStatus, $isFinal, $qtyProduced, $qtyReceived, $dataProses);

            // Normalisasi Prioritas
            $priority = (stripos($deskripsi, 'urgent') !== false) ? 'urgent_line_stop' : 'normal';

            // Resolusi User ID Pembuat
            $requesterUser = $adminUser ? $this->resolveUser($opPengaju, $kepalaPengaju, $userMap, $adminUser) : null;

            // Resolusi Komponen & Foreign Key (Membawa $noWr untuk item sequence yang akurat)
            $component = $this->resolveOrCreateComponent($kodePart, $namaPart, $dimensi, $tglOrder, $baruCodeMap, $dryRun, $noWr);

            // Tentukan Timestamp Pembuatan Tiket sesuai Tanggal Order & Data Proses
            $targetCreatedAt = $this->determineWorkRequestCreatedAt($tglOrder, $dataProses);

            // Cek apakah tiket sudah pernah ada di database (Idempotensi & Smart Sync)
            $existingWr = WorkRequest::where('wri_number', $noWr)->first();
            if ($existingWr) {
                $needsComponentUpdate = ($existingWr->component_id !== $component->id);
                $needsDateUpdate = ($existingWr->created_at?->format('Y-m-d') !== $tglOrder);
                $needsLocationBackfill = (empty($existingWr->target_location_id) && !empty($component->default_location_id));

                $needsUpdate = (
                    $existingWr->status !== $dbStatus
                    || (bool)$existingWr->is_final !== (bool)$isFinal
                    || (float)$existingWr->quantity_received < (float)$qtyReceived
                    || (float)$existingWr->quantity_produced < (float)$qtyProduced
                    || (float)$existingWr->quantity_defect < (float)$qtyDefect
                    || $existingWr->approval_pengaju !== $approvalPengaju
                    || $existingWr->approval_penerima !== $approvalPenerima
                    || $needsComponentUpdate
                    || $needsDateUpdate
                    || $needsLocationBackfill
                );

                if ($sync && ($needsUpdate || !empty($dataProses))) {
                    if (!$dryRun) {
                        DB::transaction(function () use (
                            $existingWr, $dbStatus, $isFinal, $approvalPengaju, $approvalPenerima,
                            $qtyProduced, $qtyReceived, $qtyDefect, $activeQty, $currentBatchQty,
                            $dataProses, $tglOrder, $component,
                            $needsComponentUpdate, $needsDateUpdate, $needsLocationBackfill, $targetCreatedAt
                        ) {
                            $updateData = [
                                'status' => $dbStatus,
                                'current_process_status' => $dbStatus,
                                'is_final' => $isFinal,
                                'approval_pengaju' => $approvalPengaju,
                                'approval_penerima' => $approvalPenerima,
                                'quantity_requested' => $activeQty,
                                'quantity_produced' => max((float)$existingWr->quantity_produced, (float)$qtyProduced),
                                'quantity_received' => max((float)$existingWr->quantity_received, (float)$qtyReceived),
                                'quantity_defect' => max((float)$existingWr->quantity_defect, (float)$qtyDefect),
                                'current_batch_qty' => $currentBatchQty,
                            ];
                            if ($needsComponentUpdate) {
                                $updateData['component_id'] = $component->id;
                            }
                            if ($needsLocationBackfill || $needsComponentUpdate) {
                                $updateData['target_location_id'] = $component->default_location_id;
                                $updateData['target_shelf_level'] = $component->default_shelf_level;
                                $updateData['target_slot_number'] = $component->default_slot_number;
                            }
                            if ($needsDateUpdate) {
                                $updateData['created_at'] = $targetCreatedAt;
                                $updateData['due_date'] = $tglOrder;
                            }
                            $existingWr->update($updateData);

                            if ($isFinal) {
                                $existingWr->steps()->update([
                                    'status' => 'completed',
                                    'completed_at' => $tglOrder . ' 16:30:00',
                                ]);
                            }

                            $this->syncWorkRequestLogs($existingWr->id, $dataProses, $tglOrder);
                        });
                    }
                    $totalUpdatedExisting++;
                } else {
                    $totalSkippedExisting++;
                }
                $bar->advance();
                continue;
            }

            // Track status statistik
            if (str_contains($stBarang, 'Laser') || str_contains($stBarang, 'M1') || str_contains($stBarang, 'Proses')) {
                $inProgressStats['Proses In']++;
            } elseif (isset($inProgressStats[$stBarang])) {
                $inProgressStats[$stBarang]++;
            } elseif ($approvalPengaju === 'Menunggu' || $approvalPenerima === 'Menunggu') {
                $inProgressStats['Belum Verifikasi']++;
            }

            if (!$dryRun) {
                DB::transaction(function () use (
                    $noWr, $component, $requesterUser, $deptPengaju, $deptPenerima,
                    $routing, $approvalPengaju, $approvalPenerima, $opPenerima, $kepalaPenerima,
                    $activeQty, $qtyProduced, $qtyReceived, $qtyDefect, $currentBatchQty,
                    $stBarang, $dbStatus, $isFinal, $priority, $deskripsi,
                    $tglOrder, $dataProses, $targetCreatedAt
                ) {
                    $wr = WorkRequest::create([
                        'wri_number' => $noWr,
                        'component_id' => $component->id,
                        'requested_by_user_id' => $requesterUser?->id,
                        'dept_pengaju' => $deptPengaju,
                        'dept_penerima' => $deptPenerima,
                        'routing_text' => $routing ?: $deptPenerima,
                        'approval_pengaju' => $approvalPengaju,
                        'approval_penerima' => $approvalPenerima,
                        'assigned_operator_name' => $opPenerima ?: ($kepalaPenerima ?: null),
                        'target_location_id' => $component->default_location_id,
                        'target_shelf_level' => $component->default_shelf_level,
                        'target_slot_number' => $component->default_slot_number,
                        'quantity_requested' => $activeQty,
                        'quantity_produced' => $qtyProduced,
                        'quantity_received' => $qtyReceived,
                        'quantity_defect' => $qtyDefect,
                        'current_batch_qty' => $currentBatchQty,
                        'is_final' => $isFinal,
                        'priority' => $priority,
                        'status' => $dbStatus,
                        'current_process_status' => $dbStatus,
                        'notes' => $deskripsi ?: null,
                        'due_date' => $tglOrder,
                        'created_at' => $targetCreatedAt,
                        'updated_at' => $isFinal ? ($tglOrder . ' 17:00:00') : $targetCreatedAt,
                    ]);

                    // Buat Step Pengerjaan Mesin (mendukung Single maupun Multi-Routing)
                    $this->createMultiRoutingSteps($wr->id, $routing, $deptPenerima, $opPenerima, $tglOrder, $isFinal, $stBarang, $dataProses);

                    // Parsing dan Insert Log Riwayat Proses
                    $this->insertWorkRequestLogs($wr->id, $dataProses, $tglOrder);
                });
            }

            $totalMigrated++;
            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);

        $this->info("==================================================================");
        $this->info(" RINGKASAN HASIL MIGRASI DATA:");
        $this->line(" - Total Baris Diproses    : {$totalRead} baris");
        $this->line(" - Tiket Baru Dimigrasikan : <info>{$totalMigrated}</info> tiket " . ($dryRun ? "(Simulasi)" : "(Riil DB)"));
        if ($sync) {
            $this->line(" - Tiket Eksis Diperbarui  : <info>{$totalUpdatedExisting}</info> tiket " . ($dryRun ? "(Simulasi)" : "(Riil DB)"));
        }
        $this->line(" - Dilewati (Sudah Eksis)  : <comment>{$totalSkippedExisting}</comment> tiket (0 Duplikasi)");
        if ($onlyInProgress) {
            $this->line(" - Dilewati (Sudah Selesai): {$totalSkippedCompleted} tiket");
        }
        $this->newLine();
        $this->info(" Status Tiket In-Progress yang Diaktifkan:");
        foreach ($inProgressStats as $st => $count) {
            $this->line("   • {$st} : {$count} tiket");
        }
        $this->info("==================================================================");

        return 0;
    }

    /**
     * Susun pemetaan User berdasarkan nama di spreadsheet.
     */
    protected function buildUserMap(User $fallbackUser): array
    {
        $map = [];
        foreach (User::all() as $u) {
            $map[strtolower(trim($u->name))] = $u;
            if ($u->username) {
                $map[strtolower(trim($u->username))] = $u;
            }
        }
        return $map;
    }

    /**
     * Resolusi akun User pembuat tiket.
     */
    protected function resolveUser(string $op, string $kepala, array $userMap, User $fallbackUser): User
    {
        $opKey = strtolower(trim($op));
        if ($opKey && isset($userMap[$opKey])) {
            return $userMap[$opKey];
        }

        $kepalaKey = strtolower(trim($kepala));
        if ($kepalaKey && isset($userMap[$kepalaKey])) {
            return $userMap[$kepalaKey];
        }

        // Cari dengan kecocokan parsial (misal 'panji' -> 'Pak Panji')
        foreach ($userMap as $nameKey => $userObj) {
            if ($opKey && (str_contains($nameKey, $opKey) || str_contains($opKey, $nameKey))) {
                return $userObj;
            }
            if ($kepalaKey && (str_contains($nameKey, $kepalaKey) || str_contains($kepalaKey, $nameKey))) {
                return $userObj;
            }
        }

        return $fallbackUser;
    }

    /**
     * Susun kamus kode Komponen Baru dari Komponen baru.csv
     */
    protected function buildBaruComponentMap(string $filePath): array
    {
        $map = [];
        $counters = [];

        if (!file_exists($filePath)) {
            return $map;
        }

        $h = fopen($filePath, 'r');
        fgetcsv($h); fgetcsv($h); fgetcsv($h); fgetcsv($h);
        while (($r = fgetcsv($h)) !== false) {
            $rawTgl = trim($r[1] ?? '');
            $nama = trim($r[3] ?? '');
            $dimensi = trim($r[12] ?? '-');
            if (empty($nama)) continue;

            $tglFormatted = date('Ymd');
            if (preg_match('/^(\d{1,2})\/(\d{1,2})\/(\d{4})$/', $rawTgl, $tm)) {
                $tglFormatted = sprintf('%04d%02d%02d', $tm[3], $tm[2], $tm[1]);
            }

            $key = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $nama));
            if (!isset($map[$key])) {
                $counters[$tglFormatted] = ($counters[$tglFormatted] ?? 0) + 1;
                $seq = sprintf('%03d', $counters[$tglFormatted]);
                $map[$key] = [
                    'code' => "NEW-{$tglFormatted}-{$seq}",
                    'name' => $nama,
                    'dimensi' => $dimensi,
                ];
            }
        }
        fclose($h);

        return $map;
    }

    /**
     * Temukan atau buat master komponen (Shadow Part) tanpa mengubah stok.
     */
    protected function resolveOrCreateComponent(
        string $kodePart, string $namaPart, string $dimensi, string $tglOrder, array $baruCodeMap, bool $dryRun, ?string $noWr = null
    ): Component {
        // Kasus 1: Komponen dengan kode resmi
        if (!empty($kodePart) && $kodePart !== 'Baru') {
            $comp = Component::where('part_number', $kodePart)->first();
            if ($comp) {
                return $comp;
            }

            // Jika belum ada di master components, buat sebagai komponen ad-hoc WR (Guardrail #7: Satu Pintu Master)
            if (!$dryRun) {
                return Component::create([
                    'part_number' => $kodePart,
                    'name' => $namaPart,
                    'specification' => $dimensi,
                    'uom' => 'PCS',
                    'category' => 'fabricated',
                    'minimum_stock' => 0,
                    'is_active' => true,
                    'source' => 'wr_adhoc',
                ]);
            }

            return new Component([
                'id' => 9999,
                'part_number' => $kodePart,
                'name' => $namaPart,
                'source' => 'wr_adhoc',
            ]);
        }

        // Kasus 2: Komponen bertanda "Baru"
        $cleanNameKey = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $namaPart));
        $meta = $baruCodeMap[$cleanNameKey] ?? null;

        // Ambil urutan item dari segmen ke-6 No WR jika ada (format: WR/Dept Gudang/YYYYMMDD/OrderSeq/Baru/ItemSeq)
        $itemSeq = '001';
        if ($noWr) {
            $parts = explode('/', $noWr);
            $lastPart = end($parts);
            if (is_numeric($lastPart)) {
                $itemSeq = sprintf('%03d', (int)$lastPart);
            }
        }

        $targetCode = $meta['code'] ?? ('NEW-' . str_replace('-', '', $tglOrder) . '-' . $itemSeq);

        // 1. Cari komponen dengan nama persis yang sama dan part_number diawali 'NEW-'
        $comp = Component::where('name', $namaPart)
            ->where('part_number', 'like', 'NEW-%')
            ->first();

        // 2. Jika belum ketemu, cari berdasarkan targetCode spesifik (hanya jika namanya cocok)
        if (!$comp) {
            $comp = Component::where('part_number', $targetCode)->first();
            if ($comp && strcasecmp(trim($comp->name), trim($namaPart)) !== 0) {
                $comp = null; // targetCode sudah dipakai oleh part lain akibat bug sebelumnya
            }
        }

        if ($comp) {
            return $comp;
        }

        if (!$dryRun) {
            // Jika $targetCode ternyata sudah terpakai di DB oleh part lain (akibat bug terdahulu), cari suffix yang tersedia
            $existingWithTargetCode = Component::where('part_number', $targetCode)->first();
            if ($existingWithTargetCode) {
                $basePrefix = 'NEW-' . str_replace('-', '', $tglOrder) . '-';
                $counter = (int)$itemSeq;
                while (Component::where('part_number', sprintf('%s%03d', $basePrefix, $counter))->exists()) {
                    $counter++;
                }
                $targetCode = sprintf('%s%03d', $basePrefix, $counter);
            }

            return Component::create([
                'part_number' => $targetCode,
                'name' => $namaPart,
                'specification' => $dimensi,
                'uom' => 'PCS',
                'category' => 'fabricated',
                'minimum_stock' => 0,
                'is_active' => true,
                'source' => 'wr_adhoc',
            ]);
        }

        return new Component([
            'id' => 9999,
            'part_number' => $targetCode,
            'name' => $namaPart,
            'source' => 'wr_adhoc',
        ]);
    }

    /**
     * Buat tahapan work_request_steps secara berurutan (mendukung single maupun multi-routing).
     */
    protected function createMultiRoutingSteps(
        int $workRequestId,
        string $routing,
        string $deptPenerima,
        string $opPenerima,
        string $tglOrder,
        bool $isFinal,
        string $stBarang,
        string $dataProses
    ): void {
        $rawSource = trim($routing) !== '' ? $routing : $deptPenerima;
        $steps = array_values(array_filter(array_map('trim', preg_split('/\s*(?:->|,)\s*/', $rawSource))));
        if (empty($steps)) {
            $steps = [$deptPenerima ?: 'Dept Laser Cutting'];
        }

        foreach ($steps as $idx => $stepName) {
            $stepNumber = $idx + 1;
            $isLastStep = ($idx === count($steps) - 1);

            $stepStatus = 'pending';
            $startedAt = null;
            $completedAt = null;

            if ($isFinal) {
                $stepStatus = 'completed';
                $startedAt = $tglOrder . ' 08:30:00';
                $completedAt = $tglOrder . ' 16:30:00';
            } elseif (
                ($isLastStep && (stripos($stBarang, 'Siap diambil') !== false || stripos($stBarang, 'Menuju Pengaju') !== false))
                || stripos($dataProses, "Proses: {$stepName} (Out)") !== false
                || stripos($dataProses, "MH Pickup: {$stepName}") !== false
            ) {
                $stepStatus = 'completed';
                $startedAt = $tglOrder . ' 08:30:00';
                $completedAt = $tglOrder . ' 16:30:00';
            } elseif (
                stripos($stBarang, $stepName) !== false
                || stripos($dataProses, "Proses: {$stepName} (In)") !== false
                || ($stepNumber === 1 && stripos($stBarang, 'Dalam antrian') === false && stripos($stBarang, 'Belum Verifikasi') === false)
            ) {
                $stepStatus = 'in_progress';
                $startedAt = $tglOrder . ' 08:30:00';
            }

            WorkRequestStep::create([
                'work_request_id' => $workRequestId,
                'step_number' => $stepNumber,
                'machine_id' => null,
                'process_name' => $stepName,
                'status' => $stepStatus,
                'operator_name' => ($stepNumber === 1 && $opPenerima) ? $opPenerima : null,
                'started_at' => $startedAt,
                'completed_at' => $completedAt,
            ]);
        }
    }

    /**
     * Hitung kuantitas batch berjalan untuk kebutuhan serah terima MH / Gudang.
     */
    protected function computeCurrentBatchQty(
        string $dbStatus,
        bool $isFinal,
        float $qtyProduced,
        float $qtyReceived,
        string $dataProses
    ): float {
        if ($isFinal) {
            return 0.0;
        }

        if (stripos($dbStatus, 'Menuju Pengaju') !== false || stripos($dbStatus, 'Siap diambil') !== false) {
            return max(0.0, $qtyProduced - $qtyReceived);
        }

        if (stripos($dbStatus, '(Out)') !== false || stripos($dbStatus, 'MH Pickup') !== false) {
            $snippets = array_reverse(explode(' ; ', $dataProses));
            foreach ($snippets as $snip) {
                if (stripos($snip, '(Out)') !== false || stripos($snip, 'MH Pickup') !== false) {
                    $parsed = $this->parseLogSnippet($snip, date('Y-m-d'));
                    if ($parsed && $parsed['qty'] > 0) {
                        return (float) $parsed['qty'];
                    }
                }
            }
        }

        return 0.0;
    }

    /**
     * Tentukan tahun yang tepat untuk log DD/MM HH:mm terhadap tanggal order (aman lintas tahun Des -> Jan).
     */
    protected function resolveLogTimestamp(int $day, int $month, string $time, string $fallbackDate): string
    {
        $orderYear = (int) substr($fallbackDate, 0, 4) ?: (int) date('Y');
        $orderMonth = (int) substr($fallbackDate, 5, 2) ?: $month;
        $year = ($month < $orderMonth && ($orderMonth - $month) >= 6) ? ($orderYear + 1) : $orderYear;

        return sprintf('%04d-%02d-%02d %s:00', $year, $month, $day, $time);
    }

    /**
     * Tentukan timestamp created_at untuk Work Request berdasarkan tanggal order dan log proses pertama.
     */
    protected function determineWorkRequestCreatedAt(string $tglOrder, string $dataProses): string
    {
        if (!empty($dataProses)) {
            $snippets = explode(' ; ', $dataProses);
            foreach ($snippets as $snip) {
                $clean = trim($snip, " []\t\n\r");
                $parts = explode(' - ', $clean);
                if (isset($parts[2]) && preg_match('/^(\d{1,2})\/(\d{1,2})\s*(\d{1,2}:\d{2})$/', trim($parts[2]), $tm)) {
                    return $this->resolveLogTimestamp((int) $tm[1], (int) $tm[2], $tm[3], $tglOrder);
                }
            }
        }
        return $tglOrder . ' 08:00:00';
    }

    /**
     * Hitung kuantitas selesai, diterima, dan defect dari log naratif Data Proses.
     */
    protected function parseDataProsesQuantities(string $dataProses, float $totalQty, string $statusOperasional): array
    {
        if ($statusOperasional === 'Sudah diambil') {
            return ['produced' => $totalQty, 'received' => $totalQty, 'defect' => 0.0];
        }

        $produced = 0.0;
        $received = 0.0;
        $defect = 0.0;

        if (empty($dataProses)) {
            return ['produced' => $produced, 'received' => $received, 'defect' => $defect];
        }

        // Cari pola: [ Parsial > Sudah diambil - 176 ... ] atau [ Sudah diambil - 752 ... ]
        if (preg_match_all('/Sudah diambil\s*-\s*(\d+(?:[.,]\d+)?)/i', $dataProses, $m)) {
            foreach ($m[1] as $q) {
                $received += (float) str_replace(',', '.', $q);
            }
        }

        // Cari pola: [ Parsial > Siap diambil - 176 ... ] atau [ Siap diambil - 752 ... ]
        if (preg_match_all('/Siap diambil\s*-\s*(\d+(?:[.,]\d+)?)/i', $dataProses, $m)) {
            foreach ($m[1] as $q) {
                $produced += (float) str_replace(',', '.', $q);
            }
        }

        // Cari pola defect historis bila ada: [ Defect > ... - 2 ... ]
        if (preg_match_all('/Defect\s*[^-]*-\s*(\d+(?:[.,]\d+)?)/i', $dataProses, $m)) {
            foreach ($m[1] as $q) {
                $defect += (float) str_replace(',', '.', $q);
            }
        }

        return [
            'produced' => max($produced, $received),
            'received' => min($received, max(0.0, $totalQty - $defect)),
            'defect' => min($defect, $totalQty),
        ];
    }

    /**
     * Parsing 1 segmen snippet dari kolom Data Proses menjadi struktur log standar.
     */
    protected function parseLogSnippet(string $snip, string $fallbackDate): ?array
    {
        $clean = trim($snip, " []\t\n\r");
        if (empty($clean)) {
            return null;
        }

        $parts = explode(' - ', $clean);
        $statusLabel = trim($parts[0] ?? 'Proses');
        $qty = 0.0;
        if (isset($parts[1]) && preg_match('/(\d+(?:[.,]\d+)?)/', $parts[1], $qm)) {
            $qty = (float) str_replace(',', '.', $qm[1]);
        }

        $logTimestamp = $fallbackDate . ' 12:00:00';
        $notes = trim($parts[3] ?? ($parts[2] ?? ''));
        if (isset($parts[2]) && preg_match('/^(\d{1,2})\/(\d{1,2})\s*(\d{1,2}:\d{2})$/', trim($parts[2]), $tm)) {
            $logTimestamp = $this->resolveLogTimestamp((int) $tm[1], (int) $tm[2], $tm[3], $fallbackDate);
            $notes = trim($parts[3] ?? '');
        }

        $actionType = 'process';
        if (stripos($statusLabel, 'Defect') !== false) {
            $actionType = 'defect';
        } elseif (str_contains($statusLabel, 'Pickup')) {
            $actionType = 'pickup';
        } elseif (str_contains($statusLabel, 'Parsial')) {
            $actionType = 'partial';
        }

        return [
            'action_type' => $actionType,
            'status_label' => $statusLabel,
            'qty' => $qty,
            'notes' => $notes ?: null,
            'timestamp' => $logTimestamp,
        ];
    }

    /**
     * Parsing dan insert baris riwayat pengerjaan ke tabel work_request_logs.
     */
    protected function insertWorkRequestLogs(int $workRequestId, string $dataProses, string $fallbackDate): void
    {
        if (empty($dataProses)) return;

        $snippets = explode(' ; ', $dataProses);
        $accumulated = 0.0;

        foreach ($snippets as $snip) {
            $parsed = $this->parseLogSnippet($snip, $fallbackDate);
            if (!$parsed) continue;

            $accumulated += $parsed['qty'];

            WorkRequestLog::create([
                'work_request_id' => $workRequestId,
                'action_type' => $parsed['action_type'],
                'status_label' => $parsed['status_label'],
                'quantity' => $parsed['qty'],
                'quantity_accumulated' => $accumulated,
                'operator_name' => null,
                'notes' => $parsed['notes'],
                'created_at' => $parsed['timestamp'],
                'updated_at' => $parsed['timestamp'],
            ]);
        }
    }

    /**
     * Sinkronisasi log pengerjaan untuk tiket yang sudah ada tanpa menduplikasi log eksis.
     */
    protected function syncWorkRequestLogs(int $workRequestId, string $dataProses, string $fallbackDate): void
    {
        if (empty($dataProses)) return;

        $snippets = explode(' ; ', $dataProses);
        $accumulated = 0.0;
        $existingLogs = WorkRequestLog::where('work_request_id', $workRequestId)->get();

        foreach ($snippets as $snip) {
            $parsed = $this->parseLogSnippet($snip, $fallbackDate);
            if (!$parsed) continue;

            $accumulated += $parsed['qty'];

            $matchedLog = $existingLogs->first(function ($l) use ($parsed) {
                return $l->status_label === $parsed['status_label'] && (float)$l->quantity === (float)$parsed['qty'];
            });

            if ($matchedLog) {
                if ($matchedLog->created_at?->format('Y-m-d H:i') !== substr($parsed['timestamp'], 0, 16)) {
                    $matchedLog->created_at = $parsed['timestamp'];
                    $matchedLog->updated_at = $parsed['timestamp'];
                    if ($parsed['notes'] && empty($matchedLog->notes)) {
                        $matchedLog->notes = $parsed['notes'];
                    }
                    $matchedLog->save();
                }
            } else {
                WorkRequestLog::create([
                    'work_request_id' => $workRequestId,
                    'action_type' => $parsed['action_type'],
                    'status_label' => $parsed['status_label'],
                    'quantity' => $parsed['qty'],
                    'quantity_accumulated' => $accumulated,
                    'operator_name' => null,
                    'notes' => $parsed['notes'],
                    'created_at' => $parsed['timestamp'],
                    'updated_at' => $parsed['timestamp'],
                ]);
            }
        }
    }
}
