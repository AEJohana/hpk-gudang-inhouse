<?php

namespace App\Console\Commands;

use App\Models\Component;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use ZipArchive;

class ExportPhotosToLocalDirectoryCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'components:export-to-local 
                            {--path= : Folder tujuan penyimpanan foto (default dari config COMPONENT_EXTERNAL_PHOTO_DIR)} 
                            {--force : Timpa file jika sudah ada}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Unduh seluruh foto komponen dari Google Drive, rename menjadi [Part_Number].jpg, dan kemas ke file ZIP di folder lokal';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $targetDir = $this->option('path') ?: (new \App\Services\ComponentPhotoStorageService())->getExternalDirectory();
        $force = $this->option('force');

        $this->info("==================================================================");
        $this->info(" HPK GUDANG - EKSPOR FOTO KOMPONEN DARI GOOGLE DRIVE KE LOKAL");
        $this->info("==================================================================");
        $this->line("Folder Tujuan: <comment>{$targetDir}</comment>");

        // Pastikan folder tujuan ada
        if (!file_exists($targetDir)) {
            $this->info("Membuat folder target: {$targetDir}...");
            if (!mkdir($targetDir, 0777, true)) {
                $this->error("Gagal membuat folder tujuan di {$targetDir}. Periksa hak akses.");
                return 1;
            }
        }

        // Ambil seluruh komponen yang memiliki tautan Google Drive atau URL gambar
        $components = Component::whereNotNull('image_path')
            ->where(function ($q) {
                $q->where('image_path', 'like', '%drive.google%')
                  ->orWhere('image_path', 'like', 'http%');
            })
            ->get();

        $totalCount = $components->count();
        if ($totalCount === 0) {
            $this->warn("Tidak ditemukan komponen dengan tautan foto Google Drive di database.");
            return 0;
        }

        $this->info("Ditemukan <comment>{$totalCount}</comment> komponen dengan foto Google Drive.");
        $this->info("Memulai proses pengunduhan & pengemasan ZIP...");

        // Inisialisasi ZipArchive
        $zipFile = rtrim($targetDir, '\\/') . DIRECTORY_SEPARATOR . 'foto_komponen_hpk.zip';
        $zip = new ZipArchive();
        $zipOpen = $zip->open($zipFile, ZipArchive::CREATE | ZipArchive::OVERWRITE);
        if ($zipOpen !== true) {
            $this->warn("Peringatan: Gagal menginisialisasi file ZIP di {$zipFile}. Proses akan tetap menyimpan file gambar fisik.");
            $zip = null;
        }

        $bar = $this->output->createProgressBar($totalCount);
        $bar->setFormat(' %current%/%max% [%bar%] %percent:3s%% -- %message%');
        $bar->start();

        $successCount = 0;
        $skipCount = 0;
        $failCount = 0;

        foreach ($components as $component) {
            $pn = $component->part_number;
            $cleanPn = preg_replace('/[^a-zA-Z0-9_-]/', '-', $pn);
            $fileName = "{$cleanPn}.jpg";
            $targetFilePath = rtrim($targetDir, '\\/') . DIRECTORY_SEPARATOR . $fileName;

            $bar->setMessage("Memproses {$pn}...");

            // Cek jika file sudah ada dan tidak diforce
            if (file_exists($targetFilePath) && filesize($targetFilePath) > 100 && !$force) {
                if ($zip) {
                    $zip->addFile($targetFilePath, $fileName);
                }
                $skipCount++;
                $bar->advance();
                continue;
            }

            // Unduh dari Google Drive CDN
            $imageContent = $this->downloadFromGoogleDrive($component->image_path);

            if ($imageContent && strlen($imageContent) > 100) {
                file_put_contents($targetFilePath, $imageContent);

                if ($zip) {
                    $zip->addFromString($fileName, $imageContent);
                }

                $successCount++;
            } else {
                $failCount++;
                Log::warning("Gagal mengunduh foto untuk part [{$pn}] dari URL: {$component->image_path}");
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);

        if ($zip) {
            $zip->close();
            $zipSizeMb = file_exists($zipFile) ? round(filesize($zipFile) / (1024 * 1024), 2) : 0;
        } else {
            $zipSizeMb = 0;
        }

        $this->info("==================================================================");
        $this->info(" HASIL EKSPOR FOTO KOMPONEN SELESAI");
        $this->info("==================================================================");
        $this->line("Total Komponen Diproses : <info>{$totalCount}</info>");
        $this->line("Berhasil Diunduh Baru   : <info>{$successCount}</info> file");
        $this->line("Sudah Ada (Dilewati)    : <comment>{$skipCount}</comment> file");
        $this->line("Gagal Diunduh           : " . ($failCount > 0 ? "<error>{$failCount}</error>" : "<info>0</info>") . " file");
        $this->line("Folder Penyimpanan      : <comment>{$targetDir}</comment>");
        if ($zip && file_exists($zipFile)) {
            $this->line("File Arsip ZIP          : <info>{$zipFile}</info> ({$zipSizeMb} MB)");
        }
        $this->info("==================================================================");

        return 0;
    }

    /**
     * Download direct image binary from Google Drive sharing link
     */
    protected function downloadFromGoogleDrive(string $url): ?string
    {
        try {
            $targetUrl = $url;
            if (preg_match('/(?:file\/d\/|id=|open\?id=)([a-zA-Z0-9_-]+)/i', $url, $matches)) {
                $fileId = $matches[1];
                // Google Drive high-resolution direct image stream endpoint
                $targetUrl = "https://lh3.googleusercontent.com/d/{$fileId}";
            }

            $response = Http::timeout(8)->withHeaders([
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) HPK-Gudang/1.0',
            ])->get($targetUrl);

            if ($response->successful() && strlen($response->body()) > 100) {
                return $response->body();
            }

            // Fallback: coba download via uc?export=view
            if (isset($fileId)) {
                $fallbackUrl = "https://drive.google.com/uc?export=view&id={$fileId}";
                $response2 = Http::timeout(8)->get($fallbackUrl);
                if ($response2->successful() && strlen($response2->body()) > 100) {
                    return $response2->body();
                }
            }
        } catch (\Throwable $e) {
            // Error captured by caller
        }

        return null;
    }
}
