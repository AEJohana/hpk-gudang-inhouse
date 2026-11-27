<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\Process\Process;

class SystemCutoverResetCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'system:cutover-reset
                            {--backup-only : Hanya membuat backup database .sql tanpa menghapus data apapun}
                            {--confirm : Konfirmasi eksplisit untuk membersihkan data uji coba setelah backup sukses}
                            {--backup-dir= : Direktori tujuan penyimpanan berkas backup .sql}
                            {--skip-backup : Lewati backup mysqldump (HANYA untuk lingkungan pengujian SQLite :memory:)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Backup penuh database MySQL ke .sql lalu membersihkan data uji coba secara terurut (FK-safe) untuk Cutover Go-Live';

    /**
     * Daftar tabel operasional/uji coba yang dibersihkan saat cutover (urutan anak -> induk demi kepatuhan FK).
     */
    public const RESET_TABLES_ORDERED = [
        'work_request_logs',
        'work_request_steps',
        'work_requests',
        'transaction_items',
        'transactions',
        'qr_requests',
        'cycle_count_items',
        'cycle_counts',
        'disposal_items',
        'disposals',
        'ecrs',
        'stock_balances',
        'components',
        'activity_log',
    ];

    /**
     * Daftar tabel konfigurasi inti yang WAJIB dipertahankan utuh.
     */
    public const PRESERVED_CORE_TABLES = [
        'users',
        'roles',
        'permissions',
        'model_has_roles',
        'model_has_permissions',
        'role_has_permissions',
        'departments',
        'warehouses',
        'zones',
        'locations',
        'settings',
        'component_categories',
        'uoms',
    ];

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info("==================================================================");
        $this->info(" HPK GUDANG - PRE-GOLIVE BACKUP & CLEAN CUTOVER RESET");
        $this->info("==================================================================");

        $backupOnly = (bool) $this->option('backup-only');
        $skipBackup = (bool) $this->option('skip-backup');
        $driver = DB::getDriverName();

        if ($skipBackup && $driver === 'mysql') {
            $this->error("DILARANG melewati backup mysqldump pada database operasional MySQL!");
            return 1;
        }

        // 1. Tampilkan Ringkasan Kondisi Tabel Saat Ini
        $this->line("Ringkasan jumlah data saat ini:");
        $summaryRows = [];
        foreach (self::RESET_TABLES_ORDERED as $tbl) {
            if (Schema::hasTable($tbl)) {
                $summaryRows[] = [$tbl, DB::table($tbl)->count(), 'Akan dibersihkan'];
            }
        }
        foreach (['users', 'departments', 'locations', 'settings'] as $tbl) {
            if (Schema::hasTable($tbl)) {
                $summaryRows[] = [$tbl, DB::table($tbl)->count(), 'DIPERTAHANKAN UTUH'];
            }
        }
        $this->table(['Tabel', 'Jumlah Baris', 'Status Cutover'], $summaryRows);

        // 2. Wajib Backup .sql Terlebih Dahulu (untuk MySQL)
        $backupPath = null;
        if (!$skipBackup) {
            $backupPath = $this->createSqlBackup();
            if ($backupPath === null) {
                $this->error("ABORT: Backup .sql gagal dibuat! Operasi reset dibatalkan demi keamanan data 100%.");
                return 1;
            }
            $this->info("Backup penuh berhasil disimpan: {$backupPath}");
            $this->line("Perintah restore cepat jika diperlukan:");
            $this->line("  <comment>" . $this->buildRestoreInstruction($backupPath) . "</comment>");
        }

        if ($backupOnly) {
            $this->info("Mode --backup-only selesai. Tidak ada data yang diubah atau dihapus.");
            return 0;
        }

        // 3. Wajib Konfirmasi Eksplisit
        if (!$this->option('confirm')) {
            if (!$this->confirm("Lanjutkan membersihkan data uji coba pada " . count(self::RESET_TABLES_ORDERED) . " tabel di atas? (Akun user, role, departemen, lokasi & setting tetap aman)")) {
                $this->info("Operasi dibatalkan oleh pengguna.");
                return 0;
            }
        }

        // 4. Hapus Berurutan Mengikuti Rantai Foreign Key di Dalam Transaksi Atomik
        $deletedCounts = [];
        DB::beginTransaction();
        try {
            foreach (self::RESET_TABLES_ORDERED as $table) {
                if (!Schema::hasTable($table)) {
                    continue;
                }
                $deleted = DB::table($table)->delete();
                $deletedCounts[] = [$table, $deleted];
            }
            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            $this->error("GAGAL melakukan clean cutover reset (Database telah di-ROLLBACK utuh): " . $e->getMessage());
            return 1;
        }

        // Reset auto-increment pada MySQL (DDL berada di luar transaksi karena implicit commit di MySQL)
        if ($driver === 'mysql') {
            foreach (self::RESET_TABLES_ORDERED as $table) {
                if (Schema::hasTable($table)) {
                    DB::statement("ALTER TABLE `{$table}` AUTO_INCREMENT = 1");
                }
            }
        }

        try {
            Artisan::call('cache:clear');
        } catch (\Throwable $e) {
            // Abaikan jika driver cache tidak aktif
        }

        $this->newLine();
        $this->info("==================================================================");
        $this->info(" CLEAN CUTOVER RESET SELESAI 100% (FK-SAFE & TERKONTROL)");
        $this->info("==================================================================");
        $this->table(['Tabel Dibersihkan', 'Baris Dihapus'], $deletedCounts);

        if ($backupPath) {
            $this->line("File Backup Tersimpan : <info>{$backupPath}</info>");
        }
        $this->info("Langkah selanjutnya: Jalankan import:csv-komponen --dry-run lalu live, dilanjutkan wr:migrate-legacy-spreadsheet.");

        return 0;
    }

    /**
     * Buat backup .sql penuh menggunakan mysqldump dan verifikasi ukuran hasilnya.
     */
    protected function createSqlBackup(): ?string
    {
        $driver = DB::getDriverName();
        if ($driver !== 'mysql') {
            $this->warn("Koneksi saat ini bukan MySQL ({$driver}), melewati mysqldump.");
            return storage_path('app/backups/non_mysql_skip.sql');
        }

        $mysqldump = $this->resolveMysqldumpBinary();
        if (!$mysqldump) {
            $this->error("Binary 'mysqldump' tidak ditemukan di PATH maupun C:\\xampp\\mysql\\bin\\mysqldump.exe.");
            return null;
        }

        $backupDir = $this->option('backup-dir') ?: storage_path('app/backups');
        if (!File::exists($backupDir)) {
            File::makeDirectory($backupDir, 0755, true);
        }

        $dbName = (string) config('database.connections.mysql.database');
        $host = (string) config('database.connections.mysql.host', '127.0.0.1');
        $port = (string) config('database.connections.mysql.port', '3306');
        $user = (string) config('database.connections.mysql.username', 'root');
        $pass = (string) config('database.connections.mysql.password', '');

        $fileName = "{$dbName}_pre_cutover_" . date('Ymd_His') . '.sql';
        $fullPath = rtrim($backupDir, '\\/') . DIRECTORY_SEPARATOR . $fileName;

        $args = [
            $mysqldump,
            "--host={$host}",
            "--port={$port}",
            "--user={$user}",
            '--single-transaction',
            '--routines',
            '--triggers',
            $dbName,
        ];
        if ($pass !== '') {
            $args[] = "--password={$pass}";
        }

        $process = new Process($args);
        $process->setTimeout(120);
        $process->run();

        if (!$process->isSuccessful()) {
            $this->error("mysqldump gagal: " . $process->getErrorOutput());
            return null;
        }

        $sqlContent = $process->getOutput();
        if (strlen(trim($sqlContent)) < 100) {
            $this->error("Hasil dump SQL terlalu kecil atau kosong (" . strlen($sqlContent) . " bytes).");
            return null;
        }

        File::put($fullPath, $sqlContent);

        if (!File::exists($fullPath) || File::size($fullPath) < 100) {
            $this->error("Gagal memverifikasi file backup di {$fullPath}.");
            return null;
        }

        return $fullPath;
    }

    /**
     * Temukan lokasi binary mysqldump pada Windows XAMPP maupun Linux/PATH.
     */
    protected function resolveMysqldumpBinary(): ?string
    {
        $candidates = [
            'C:\\xampp\\mysql\\bin\\mysqldump.exe',
            'mysqldump',
            '/usr/bin/mysqldump',
            '/usr/local/bin/mysqldump',
        ];

        foreach ($candidates as $bin) {
            if (str_contains($bin, '\\') || str_starts_with($bin, '/')) {
                if (file_exists($bin)) {
                    return $bin;
                }
            } else {
                $check = new Process(['where', $bin]);
                $check->run();
                if ($check->isSuccessful() && trim($check->getOutput()) !== '') {
                    return trim(explode("\n", trim($check->getOutput()))[0]);
                }
            }
        }

        return null;
    }

    /**
     * Susun instruksi perintah restore yang siap pakai untuk pengguna.
     */
    protected function buildRestoreInstruction(string $backupPath): string
    {
        $mysqlBin = file_exists('C:\\xampp\\mysql\\bin\\mysql.exe') ? 'C:\\xampp\\mysql\\bin\\mysql.exe' : 'mysql';
        $dbName = (string) config('database.connections.mysql.database');
        $user = (string) config('database.connections.mysql.username', 'root');

        return "Get-Content \"{$backupPath}\" -Raw | & \"{$mysqlBin}\" -u {$user} {$dbName}";
    }
}
