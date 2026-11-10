<?php

namespace App\Console\Commands;

use App\Models\Component;
use App\Services\ComponentPhotoStorageService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class CheckNasPhotosCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'components:check-nas 
                            {--pull-to-local : Sinkronisasi/salin seluruh gambar dari NAS ke folder lokal cadangan D:\Ali Magang\KOMPONEN}
                            {--limit-missing=10 : Jumlah maksimal komponen tanpa gambar yang ditampilkan}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Periksa status konektivitas Server NAS (Z: dan UNC), audit kelengkapan gambar komponen, dan opsi cadangkan ke folder lokal';

    /**
     * Execute the console command.
     */
    public function handle(ComponentPhotoStorageService $storageService): int
    {
        $this->info("==================================================================");
        $this->info(" HPK GUDANG - AUDIT KONEKSI SERVER NAS & GAMBAR KOMPONEN");
        $this->info("==================================================================");

        $primaryDir = $storageService->getExternalDirectory();
        $uncDir = '\\\\192.168.1.150\\HPK_Eng_Online\\08.Project\\04. HPK Digitalisasi Program\\04. Gudang-Komponen\\Komonpen Gudang In House';
        $localDir = 'D:\Ali Magang\KOMPONEN';
        $mirrorDir = $storageService->getMirrorDirectory();

        // 1. Cek Status Direktori
        $this->line("1. Memeriksa Ketersediaan Jalur Penyimpanan:");
        $this->checkPathStatus("Primary Dir (Z: / Config)", $primaryDir);
        $this->checkPathStatus("Direct UNC Path (\\\\192.168.1.150)", $uncDir);
        $this->checkPathStatus("Local Backup Redundancy (D:)", $localDir);
        $this->checkPathStatus("Webroot Mirror (uploads/components)", $mirrorDir);
        $this->newLine();

        // 2. Audit Komponen di Database
        $this->line("2. Mengaudit Komponen di Database vs Berkas Fisik:");
        $components = Component::select('id', 'part_number', 'name')->orderBy('part_number')->get();
        $total = $components->count();

        $foundCount = 0;
        $missing = [];

        foreach ($components as $comp) {
            $path = $storageService->findLocalPhoto($comp->part_number, $comp->image_path);
            if ($path && file_exists($path)) {
                $foundCount++;
            } else {
                $missing[] = $comp;
            }
        }

        $percentage = $total > 0 ? round(($foundCount / $total) * 100, 1) : 0;
        $this->info("Total Komponen di Database   : <comment>{$total}</comment>");
        $this->info("Memiliki Berkas Gambar/PDF   : <info>{$foundCount} ({$percentage}%)</info>");
        $this->info("Belum Memiliki Berkas Fisik  : <error>" . count($missing) . "</error>");
        $this->newLine();

        if (count($missing) > 0) {
            $this->warn("Daftar Komponen yang Belum Memiliki Berkas di NAS:");
            $limit = (int) $this->option('limit-missing');
            $rows = [];
            foreach (array_slice($missing, 0, $limit) as $idx => $m) {
                $rows[] = [
                    $idx + 1,
                    $m->part_number,
                    $m->name,
                    "{$m->part_number}.jpg",
                ];
            }
            $this->table(['No', 'Kode Item (Part Number)', 'Nama Komponen', 'Rekomendasi Nama Berkas'], $rows);

            if (count($missing) > $limit) {
                $this->line("... dan " . (count($missing) - $limit) . " komponen lainnya.");
            }

            $this->line("<comment>Tips:</comment> Salin file foto ke folder NAS dan namai sesuai 'Rekomendasi Nama Berkas'. Sistem akan otomatis mengenalinya tanpa perlu update database!");
            $this->newLine();
        }

        // 3. Opsi Pull ke Folder Lokal
        if ($this->option('pull-to-local')) {
            $this->line("3. Menyinkronkan Berkas dari NAS ke Folder Lokal Cadangan (D:)...");
            if (!file_exists($localDir)) {
                @mkdir($localDir, 0777, true);
            }

            $sourceDir = file_exists($primaryDir) ? $primaryDir : (file_exists($uncDir) ? $uncDir : null);
            if (!$sourceDir) {
                $this->error("Gagal menyinkronkan: Tidak ada sumber NAS (Z: maupun UNC) yang dapat dijangkau.");
                return 1;
            }

            $sourceFiles = File::files($sourceDir);
            $this->line("Ditemukan <comment>" . count($sourceFiles) . "</comment> berkas di NAS ({$sourceDir}).");
            $bar = $this->output->createProgressBar(count($sourceFiles));
            $bar->start();

            $copied = 0;
            foreach ($sourceFiles as $file) {
                $destPath = rtrim($localDir, '\\/') . DIRECTORY_SEPARATOR . $file->getFilename();
                if (!file_exists($destPath) || filesize($destPath) !== $file->getSize()) {
                    @copy($file->getRealPath(), $destPath);
                    $copied++;
                }
                $bar->advance();
            }
            $bar->finish();
            $this->newLine(2);
            $this->info("Sinkronisasi Selesai! Berhasil memperbarui <info>{$copied}</info> berkas ke {$localDir}.");
        }

        return 0;
    }

    /**
     * Helper to print path status with color indicators.
     */
    protected function checkPathStatus(string $label, string $path): void
    {
        $exists = file_exists($path);
        $statusStr = $exists ? "<info>[TERHUBUNG / TERSEDIA]</info>" : "<error>[TIDAK DITEMUKAN / OFFLINE]</error>";
        $countStr = '';

        if ($exists && is_dir($path)) {
            $fileCount = count(glob(rtrim($path, '\\/') . '/*.*'));
            $countStr = " ({$fileCount} berkas)";
        }

        $this->line(sprintf(" - %-35s : %s %s", $label, $statusStr, $countStr));
    }
}
