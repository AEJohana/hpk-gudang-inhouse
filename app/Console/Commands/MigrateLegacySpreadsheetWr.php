<?php

namespace App\Console\Commands;

use App\Models\Component;
use App\Models\Department;
use App\Models\Machine;
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

        $logWrFile = base_path('System WR lama/Spreadsheet/Form Work Request - Log WR.csv');
        $kompBaruFile = base_path('System WR lama/Spreadsheet/Form Work Request - Komponen baru.csv');

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

        // 1. Persiapan Mapping Mesin & User
        $machines = Machine::all();
        $defaultMachine = $machines->first();

        $adminUser = User::where('role', 'admin_gudang')->first() ?? User::first();
        $userMap = $this->buildUserMap($adminUser);

        // 2. Persiapan Mapping Komponen Baru
        $this->line("1. Menyiapkan kamus data Komponen Baru...");
        $baruCodeMap = $this->buildBaruComponentMap($kompBaruFile);
        $this->info("Kamus komponen baru siap: " . count($baruCodeMap) . " item.");

        // 3. Baca dan proses Log WR.csv
        $this->line("2. Membaca berkas Log WR.csv...");
        $h = fopen($logWrFile, 'r');
        fgetcsv($h); // empty line 1
        $header = fgetcsv($h); // header line 2

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

            // Parsing Qty Selesai & Diterima dari Data Proses
            $parsedQty = $this->parseDataProsesQuantities($dataProses, $qty, $stBarang);
            $qtyProduced = $parsedQty['produced'];
            $qtyReceived = $parsedQty['received'];

            // Normalisasi Prioritas
            $priority = (stripos($deskripsi, 'urgent') !== false) ? 'urgent_line_stop' : 'normal';

            // Resolusi User ID Pembuat
            $requesterUser = $this->resolveUser($opPengaju, $kepalaPengaju, $userMap, $adminUser);

            // Resolusi Komponen & Foreign Key (Membawa $noWr untuk item sequence yang akurat)
            $component = $this->resolveOrCreateComponent($kodePart, $namaPart, $dimensi, $tglOrder, $baruCodeMap, $dryRun, $noWr);

            // Cocokkan Mesin Tujuan
            $matchedMachine = $this->resolveMachine($deptPenerima, $machines, $defaultMachine);

            // Tentukan Timestamp Pembuatan Tiket sesuai Tanggal Order & Data Proses
            $targetCreatedAt = $this->determineWorkRequestCreatedAt($tglOrder, $dataProses);

            // Cek apakah tiket sudah pernah ada di database (Idempotensi & Smart Sync)
            $existingWr = WorkRequest::where('wri_number', $noWr)->first();
            if ($existingWr) {
                $needsComponentUpdate = ($existingWr->component_id !== $component->id);
                $needsDateUpdate = ($existingWr->created_at?->format('Y-m-d') !== $tglOrder);

                $needsUpdate = (
                    $existingWr->status !== $dbStatus
                    || (bool)$existingWr->is_final !== (bool)$isFinal
                    || (float)$existingWr->quantity_received < (float)$qtyReceived
                    || (float)$existingWr->quantity_produced < (float)$qtyProduced
                    || $existingWr->approval_pengaju !== $approvalPengaju
                    || $existingWr->approval_penerima !== $approvalPenerima
                    || $needsComponentUpdate
                    || $needsDateUpdate
                );

                if ($sync && ($needsUpdate || !empty($dataProses))) {
                    if (!$dryRun) {
                        DB::transaction(function () use (
                            $existingWr, $dbStatus, $isFinal, $approvalPengaju, $approvalPenerima,
                            $qtyProduced, $qtyReceived, $dataProses, $tglOrder, $component,
                            $needsComponentUpdate, $needsDateUpdate, $targetCreatedAt
                        ) {
                            $updateData = [
                                'status' => $dbStatus,
                                'current_process_status' => $dbStatus,
                                'is_final' => $isFinal,
                                'approval_pengaju' => $approvalPengaju,
                                'approval_penerima' => $approvalPenerima,
                                'quantity_produced' => max((float)$existingWr->quantity_produced, (float)$qtyProduced),
                                'quantity_received' => max((float)$existingWr->quantity_received, (float)$qtyReceived),
                            ];
                            if ($needsComponentUpdate) {
                                $updateData['component_id'] = $component->id;
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
                    $qty, $qtyProduced, $qtyReceived, $stBarang, $dbStatus, $isFinal, $priority, $deskripsi,
                    $tglOrder, $matchedMachine, $dataProses, $targetCreatedAt
                ) {
                    $wr = WorkRequest::create([
                        'wri_number' => $noWr,
                        'component_id' => $component->id,
                        'requested_by_user_id' => $requesterUser->id,
                        'dept_pengaju' => $deptPengaju,
                        'dept_penerima' => $deptPenerima,
                        'routing_text' => $routing ?: $deptPenerima,
                        'approval_pengaju' => $approvalPengaju,
                        'approval_penerima' => $approvalPenerima,
                        'assigned_operator_name' => $opPenerima ?: ($kepalaPenerima ?: null),
                        'quantity_requested' => $qty,
                        'quantity_produced' => $qtyProduced,
                        'quantity_received' => $qtyReceived,
                        'quantity_defect' => 0,
                        'is_final' => $isFinal,
                        'priority' => $priority,
                        'status' => $dbStatus,
                        'current_process_status' => $dbStatus,
                        'notes' => $deskripsi ?: null,
                        'due_date' => $tglOrder,
                        'created_at' => $targetCreatedAt,
                        'updated_at' => $isFinal ? ($tglOrder . ' 17:00:00') : $targetCreatedAt,
                    ]);

                    // Buat Step Pengerjaan Mesin
                    WorkRequestStep::create([
                        'work_request_id' => $wr->id,
                        'step_number' => 1,
                        'machine_id' => $matchedMachine?->id ?? 1,
                        'process_name' => $deptPenerima,
                        'status' => $isFinal ? 'completed' : ($stBarang === 'Dalam antrian' ? 'pending' : 'in_progress'),
                        'operator_name' => $opPenerima ?: null,
                        'started_at' => $tglOrder . ' 08:30:00',
                        'completed_at' => $isFinal ? $tglOrder . ' 16:30:00' : null,
                    ]);

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

            // Jika belum ada di master components, buat master baru
            if (!$dryRun) {
                return Component::create([
                    'part_number' => $kodePart,
                    'name' => $namaPart,
                    'specification' => $dimensi,
                    'uom' => 'PCS',
                    'category' => 'fabricated',
                    'minimum_stock' => 0,
                    'is_active' => true,
                ]);
            }

            return new Component([
                'id' => 9999,
                'part_number' => $kodePart,
                'name' => $namaPart,
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
            ]);
        }

        return new Component([
            'id' => 9999,
            'part_number' => $targetCode,
            'name' => $namaPart,
        ]);
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
                    $day = (int) $tm[1];
                    $month = (int) $tm[2];
                    $time = $tm[3];
                    $year = (int) substr($tglOrder, 0, 4) ?: (int) date('Y');
                    return sprintf('%04d-%02d-%02d %s:00', $year, $month, $day, $time);
                }
            }
        }
        return $tglOrder . ' 08:00:00';
    }

    /**
     * Hitung kuantitas selesai & diterima dari log naratif Data Proses.
     */
    protected function parseDataProsesQuantities(string $dataProses, float $totalQty, string $statusOperasional): array
    {
        if ($statusOperasional === 'Sudah diambil') {
            return ['produced' => $totalQty, 'received' => $totalQty];
        }

        $produced = 0;
        $received = 0;

        if (empty($dataProses)) {
            return ['produced' => $produced, 'received' => $received];
        }

        // Cari pola: [ Parsial > Sudah diambil - 176 ... ] atau [ Sudah diambil - 752 ... ]
        if (preg_match_all('/Sudah diambil\s*-\s*(\d+)/i', $dataProses, $m)) {
            foreach ($m[1] as $q) {
                $received += (float) $q;
            }
        }

        // Cari pola: [ Parsial > Siap diambil - 176 ... ] atau [ Siap diambil - 752 ... ]
        if (preg_match_all('/Siap diambil\s*-\s*(\d+)/i', $dataProses, $m)) {
            foreach ($m[1] as $q) {
                $produced += (float) $q;
            }
        }

        return [
            'produced' => max($produced, $received),
            'received' => min($received, $totalQty),
        ];
    }

    /**
     * Cocokkan mesin yang relevan berdasarkan nama departemen.
     */
    protected function resolveMachine(string $deptName, $machines, $defaultMachine): Machine
    {
        $clean = strtolower($deptName);
        foreach ($machines as $m) {
            $mName = strtolower($m->name);
            $mType = strtolower($m->machine_type);
            if (str_contains($clean, 'laser') && (str_contains($mName, 'laser') || str_contains($mType, 'laser'))) {
                return $m;
            }
            if (str_contains($clean, 'bending') && (str_contains($mName, 'bending') || str_contains($mType, 'bending'))) {
                return $m;
            }
            if (str_contains($clean, 'sharing') && (str_contains($mName, 'shearing') || str_contains($mType, 'shearing'))) {
                return $m;
            }
            if (str_contains($clean, 'cnc') && (str_contains($mName, 'cnc') || str_contains($mType, 'cnc'))) {
                return $m;
            }
            if (str_contains($clean, 'm1') && (str_contains($mName, 'shearing') || str_contains($mName, 'bubut'))) {
                return $m;
            }
        }
        return $defaultMachine ?? Machine::first();
    }

    /**
     * Parsing dan insert baris riwayat pengerjaan ke tabel work_request_logs.
     */
    protected function insertWorkRequestLogs(int $workRequestId, string $dataProses, string $fallbackDate): void
    {
        if (empty($dataProses)) return;

        $snippets = explode(' ; ', $dataProses);
        $accumulated = 0;

        foreach ($snippets as $snip) {
            $clean = trim($snip, " []\t\n\r");
            if (empty($clean)) continue;

            $parts = explode(' - ', $clean);
            $statusLabel = trim($parts[0] ?? 'Proses');
            $qty = 0;
            if (isset($parts[1]) && preg_match('/(\d+)/', $parts[1], $qm)) {
                $qty = (float) $qm[1];
            }

            $logTimestamp = $fallbackDate . ' 12:00:00';
            $notes = trim($parts[3] ?? ($parts[2] ?? ''));
            if (isset($parts[2]) && preg_match('/^(\d{1,2})\/(\d{1,2})\s*(\d{1,2}:\d{2})$/', trim($parts[2]), $tm)) {
                $day = (int) $tm[1];
                $month = (int) $tm[2];
                $time = $tm[3];
                $year = (int) substr($fallbackDate, 0, 4) ?: (int) date('Y');
                $logTimestamp = sprintf('%04d-%02d-%02d %s:00', $year, $month, $day, $time);
                $notes = trim($parts[3] ?? '');
            }

            $accumulated += $qty;

            WorkRequestLog::create([
                'work_request_id' => $workRequestId,
                'action_type' => str_contains($statusLabel, 'Pickup') ? 'pickup' : (str_contains($statusLabel, 'Parsial') ? 'partial' : 'process'),
                'status_label' => $statusLabel,
                'quantity' => $qty,
                'quantity_accumulated' => $accumulated,
                'operator_name' => null,
                'notes' => $notes ?: null,
                'created_at' => $logTimestamp,
                'updated_at' => $logTimestamp,
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
        $accumulated = 0;

        $existingLogs = WorkRequestLog::where('work_request_id', $workRequestId)->get();

        foreach ($snippets as $snip) {
            $clean = trim($snip, " []\t\n\r");
            if (empty($clean)) continue;

            $parts = explode(' - ', $clean);
            $statusLabel = trim($parts[0] ?? 'Proses');
            $qty = 0;
            if (isset($parts[1]) && preg_match('/(\d+)/', $parts[1], $qm)) {
                $qty = (float) $qm[1];
            }

            $logTimestamp = $fallbackDate . ' 12:00:00';
            $notes = trim($parts[3] ?? ($parts[2] ?? ''));
            if (isset($parts[2]) && preg_match('/^(\d{1,2})\/(\d{1,2})\s*(\d{1,2}:\d{2})$/', trim($parts[2]), $tm)) {
                $day = (int) $tm[1];
                $month = (int) $tm[2];
                $time = $tm[3];
                $year = (int) substr($fallbackDate, 0, 4) ?: (int) date('Y');
                $logTimestamp = sprintf('%04d-%02d-%02d %s:00', $year, $month, $day, $time);
                $notes = trim($parts[3] ?? '');
            }

            $accumulated += $qty;

            // Cari apakah log dengan status_label dan quantity yang sama sudah pernah ada
            $matchedLog = $existingLogs->first(function ($l) use ($statusLabel, $qty) {
                return $l->status_label === $statusLabel && (float)$l->quantity === (float)$qty;
            });

            if ($matchedLog) {
                // Perbarui timestamp jika berbeda dengan logTimestamp aktual dari Data Proses
                if ($matchedLog->created_at?->format('Y-m-d H:i') !== substr($logTimestamp, 0, 16)) {
                    $matchedLog->created_at = $logTimestamp;
                    $matchedLog->updated_at = $logTimestamp;
                    if ($notes && empty($matchedLog->notes)) {
                        $matchedLog->notes = $notes;
                    }
                    $matchedLog->save();
                }
            } else {
                WorkRequestLog::create([
                    'work_request_id' => $workRequestId,
                    'action_type' => str_contains($statusLabel, 'Pickup') ? 'pickup' : (str_contains($statusLabel, 'Parsial') ? 'partial' : 'process'),
                    'status_label' => $statusLabel,
                    'quantity' => $qty,
                    'quantity_accumulated' => $accumulated,
                    'operator_name' => null,
                    'notes' => $notes ?: null,
                    'created_at' => $logTimestamp,
                    'updated_at' => $logTimestamp,
                ]);
            }
        }
    }
}
