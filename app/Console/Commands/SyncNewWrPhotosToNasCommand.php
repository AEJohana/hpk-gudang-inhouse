<?php

namespace App\Console\Commands;

use App\Models\Component;
use App\Services\ComponentPhotoStorageService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class SyncNewWrPhotosToNasCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'wr:sync-new-komponen-to-nas
                            {--dry-run : Simulasi penyalinan tanpa menulis berkas fisik}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sinkronkan seluruh berkas foto komponen baru WR (ad-hoc) dari uploads lokal ke folder NewKomponenWR di Drive Z:';

    /**
     * Execute the console command.
     */
    public function handle(ComponentPhotoStorageService $storageService): int
    {
        $this->info("==================================================================");
        $this->info(" HPK GUDANG - SINKRONISASI FOTO NewKomponenWR KE NAS (DRIVE Z:)");
        $this->info("==================================================================");

        $targetDir = $storageService->getNewWrComponentDirectory();
        $uncDir = $storageService->getNewWrUncDirectory();
        $mirrorDir = $storageService->getMirrorDirectory();

        $targetWritable = $storageService->isDirectoryWritable($targetDir);
        $uncWritable = (!$targetWritable) ? $storageService->isDirectoryWritable($uncDir) : false;

        $destDir = $targetWritable ? $targetDir : ($uncWritable ? $uncDir : null);

        $this->line("Direktori Tujuan:");
        $this->line(" - Primary NAS (Z:)      : " . ($targetWritable ? "<info>[TERHUBUNG & DAPAT DITULIS]</info> {$targetDir}" : "<comment>[TIDAK TERJANGKAU]</comment> {$targetDir}"));
        if (!$targetWritable) {
            $this->line(" - Fallback UNC (IP)     : " . ($uncWritable ? "<info>[TERHUBUNG & DAPAT DITULIS]</info> {$uncDir}" : "<comment>[TIDAK TERJANGKAU]</comment> {$uncDir}"));
        }
        $this->line(" - Sumber Mirror Lokal   : <info>[TERSEDIA]</info> {$mirrorDir}");
        $this->newLine();

        if (!$destDir) {
            $this->error("Gagal menjangkau folder NewKomponenWR di Server NAS (baik via Drive Z: maupun UNC). Pastikan jaringan terhubung.");
            return 1;
        }

        // Cari semua file NEW-* di uploads/components atau file milik komponen ad-hoc
        $adhocPartNumbers = Component::where('source', 'wr_adhoc')->pluck('part_number')->map(function($pn) use ($storageService) {
            return strtoupper($storageService->sanitizePartNumber($pn));
        })->flip()->toArray();

        $mirrorFiles = File::files($mirrorDir);
        $filesToSync = [];

        foreach ($mirrorFiles as $file) {
            $nameWithoutExt = strtoupper(pathinfo($file->getFilename(), PATHINFO_FILENAME));
            $isNewPrefix = str_starts_with($nameWithoutExt, 'NEW-');
            $isAdhocPart = isset($adhocPartNumbers[$nameWithoutExt]);

            if ($isNewPrefix || $isAdhocPart) {
                $filesToSync[$file->getFilename()] = $file;
            }
        }

        $totalFound = count($filesToSync);
        $this->info("Ditemukan {$totalFound} berkas foto komponen baru WR di cermin lokal.");

        $dryRun = (bool) $this->option('dry-run');
        if ($dryRun) {
            $this->warn(">>> MODE SIMULASI (DRY-RUN) AKTIF: Tidak ada file yang akan disalin. <<<");
        }

        $copied = 0;
        $skipped = 0;
        $failed = 0;

        $bar = $this->output->createProgressBar($totalFound);
        $bar->start();

        foreach ($filesToSync as $filename => $file) {
            $destFile = rtrim($destDir, '\\/') . DIRECTORY_SEPARATOR . $filename;

            if (file_exists($destFile) && filesize($destFile) === $file->getSize()) {
                $skipped++;
                $bar->advance();
                continue;
            }

            if (!$dryRun) {
                if (@copy($file->getRealPath(), $destFile)) {
                    $copied++;
                } else {
                    $failed++;
                }
            } else {
                $copied++;
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);

        $storageService->clearCache();

        $this->info("==================================================================");
        $this->info(" HASIL PROSES SINKRONISASI NewKomponenWR:");
        $this->line(" - Berhasil Disalin ke NAS : <info>{$copied}</info> berkas");
        $this->line(" - Sudah Ada di NAS (Skip) : <comment>{$skipped}</comment> berkas");
        $this->line(" - Gagal Disalin           : " . ($failed > 0 ? "<error>{$failed}</error>" : "0") . " berkas");
        $this->line(" - Folder Tujuan           : {$destDir}");
        $this->info("==================================================================");
        $this->info("Cache indeks penyimpanan foto telah disegarkan secara otomatis.");

        return 0;
    }
}
