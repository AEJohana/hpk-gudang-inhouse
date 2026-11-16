<?php

namespace App\Console\Commands;

use App\Services\ComponentPhotoStorageService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class DownloadLegacyWrPhotos extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'wr:download-legacy-photos
                            {--limit= : Batasi jumlah file yang diunduh (untuk uji coba)}
                            {--force : Unduh ulang meskipun file sudah ada di disk}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Unduh seluruh berkas foto dari Google Drive spreadsheet WR lama dan simpan ke NAS dan mirror uploads';

    /**
     * Execute the console command.
     */
    public function handle(ComponentPhotoStorageService $storageService): int
    {
        $this->info("==================================================================");
        $this->info(" HPK GUDANG - AUTO-DOWNLOAD FOTO GOOGLE DRIVE WR LAMA KE NAS");
        $this->info("==================================================================");

        $logWrFile = base_path('System WR lama/Spreadsheet/Form Work Request - Log WR.csv');
        $kompBaruFile = base_path('System WR lama/Spreadsheet/Form Work Request - Komponen baru.csv');

        if (!file_exists($logWrFile)) {
            $this->error("Berkas Log WR.csv tidak ditemukan: {$logWrFile}");
            return 1;
        }

        $primaryDir = $storageService->getExternalDirectory();
        $localDir = 'D:\Ali Magang\KOMPONEN';
        $mirrorDir = $storageService->getMirrorDirectory();

        // Pastikan direktori tujuan tersedia
        if (!file_exists($mirrorDir)) {
            File::makeDirectory($mirrorDir, 0755, true);
        }
        $primaryAccessible = file_exists($primaryDir) && is_writable($primaryDir);
        $localAccessible = file_exists($localDir) && is_writable($localDir);

        $this->line("Direktori Penyimpanan:");
        $this->line(" - Primary NAS (Z:)      : " . ($primaryAccessible ? "<info>[TERHUBUNG]</info> {$primaryDir}" : "<comment>[TIDAK TERJANGKAU]</comment> {$primaryDir}"));
        $this->line(" - Local Backup (D:)     : " . ($localAccessible ? "<info>[TERHUBUNG]</info> {$localDir}" : "<comment>[TIDAK TERJANGKAU]</comment> {$localDir}"));
        $this->line(" - Web Mirror (uploads/) : <info>[TERSEDIA]</info> {$mirrorDir}");
        $this->newLine();

        // 1. Ekstrak data Komponen Baru terlebih dahulu untuk pemetaan NEW-YYYYMMDD-SEQ
        $this->line("Menganalisis berkas spreadsheet...");
        $baruCodeMap = []; // nama_clean -> NEW-YYYYMMDD-SEQ
        $dailyCounters = [];
        $uniquePhotos = []; // file_id -> [part_number, name, url, is_baru]

        if (file_exists($kompBaruFile)) {
            $h2 = fopen($kompBaruFile, 'r');
            // Lewati header
            fgetcsv($h2); fgetcsv($h2); fgetcsv($h2); fgetcsv($h2);
            while (($r = fgetcsv($h2)) !== false) {
                $rawTgl = trim($r[1] ?? ''); // 17/06/2026
                $nama = trim($r[3] ?? '');
                $url = trim($r[4] ?? '');
                if (empty($nama)) continue;

                // Format tanggal menjadi YYYYMMDD
                $tglFormatted = date('Ymd');
                if (preg_match('/^(\d{1,2})\/(\d{1,2})\/(\d{4})$/', $rawTgl, $tm)) {
                    $tglFormatted = sprintf('%04d%02d%02d', $tm[3], $tm[2], $tm[1]);
                }

                $cleanNameKey = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $nama));
                if (!isset($baruCodeMap[$cleanNameKey])) {
                    $dailyCounters[$tglFormatted] = ($dailyCounters[$tglFormatted] ?? 0) + 1;
                    $seqStr = sprintf('%03d', $dailyCounters[$tglFormatted]);
                    $baruCodeMap[$cleanNameKey] = "NEW-{$tglFormatted}-{$seqStr}";
                }

                $partCode = $baruCodeMap[$cleanNameKey];

                if (!empty($url) && preg_match('/(?:id=|file\/d\/)([a-zA-Z0-9_-]+)/', $url, $um)) {
                    $fileId = $um[1];
                    $uniquePhotos[$fileId] = [
                        'part_number' => $partCode,
                        'name' => $nama,
                        'url' => $url,
                        'is_baru' => true,
                    ];
                }
            }
            fclose($h2);
        }

        // 2. Ekstrak dari Log WR.csv
        $h1 = fopen($logWrFile, 'r');
        fgetcsv($h1); // empty line 1
        fgetcsv($h1); // header line 2
        while (($r = fgetcsv($h1)) !== false) {
            $rawTgl = trim($r[1] ?? ''); // 2026-06-08
            $code = trim($r[3] ?? '');
            $nama = trim($r[4] ?? '');
            $url = trim($r[5] ?? '');

            if (empty($url) || !preg_match('/(?:id=|file\/d\/)([a-zA-Z0-9_-]+)/', $url, $um)) {
                continue;
            }

            $fileId = $um[1];
            if (isset($uniquePhotos[$fileId])) {
                continue; // Sudah terdaftar
            }

            if ($code === 'Baru' || empty($code)) {
                $cleanNameKey = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $nama));
                $tglFormatted = !empty($rawTgl) ? str_replace('-', '', $rawTgl) : date('Ymd');

                if (!isset($baruCodeMap[$cleanNameKey])) {
                    $dailyCounters[$tglFormatted] = ($dailyCounters[$tglFormatted] ?? 0) + 1;
                    $seqStr = sprintf('%03d', $dailyCounters[$tglFormatted]);
                    $baruCodeMap[$cleanNameKey] = "NEW-{$tglFormatted}-{$seqStr}";
                }
                $partCode = $baruCodeMap[$cleanNameKey];
                $uniquePhotos[$fileId] = [
                    'part_number' => $partCode,
                    'name' => $nama,
                    'url' => $url,
                    'is_baru' => true,
                ];
            } else {
                $uniquePhotos[$fileId] = [
                    'part_number' => $code,
                    'name' => $nama,
                    'url' => $url,
                    'is_baru' => false,
                ];
            }
        }
        fclose($h1);

        $totalFound = count($uniquePhotos);
        $this->info("Ditemukan {$totalFound} berkas foto unik dari spreadsheet.");

        $limit = $this->option('limit') ? (int) $this->option('limit') : null;
        if ($limit && $limit > 0) {
            $uniquePhotos = array_slice($uniquePhotos, 0, $limit, true);
            $this->warn("Mode pengujian aktif: Hanya memproses {$limit} berkas pertama.");
        }

        $force = (bool) $this->option('force');

        $countDownloaded = 0;
        $countSkipped = 0;
        $countFailed = 0;

        $bar = $this->output->createProgressBar(count($uniquePhotos));
        $bar->start();

        foreach ($uniquePhotos as $fileId => $meta) {
            $pn = $storageService->sanitizePartNumber($meta['part_number']);
            $expectedExt = 'jpg';
            $fileName = "{$pn}.{$expectedExt}";

            // Periksa apakah file sudah ada di NAS atau uploads (Idempotensi)
            $existingPath = null;
            if (!$force) {
                if (file_exists($mirrorDir . DIRECTORY_SEPARATOR . $fileName) && filesize($mirrorDir . DIRECTORY_SEPARATOR . $fileName) > 100) {
                    $existingPath = $mirrorDir . DIRECTORY_SEPARATOR . $fileName;
                } elseif ($primaryAccessible && file_exists($primaryDir . DIRECTORY_SEPARATOR . $fileName) && filesize($primaryDir . DIRECTORY_SEPARATOR . $fileName) > 100) {
                    $existingPath = $primaryDir . DIRECTORY_SEPARATOR . $fileName;
                } elseif ($localAccessible && file_exists($localDir . DIRECTORY_SEPARATOR . $fileName) && filesize($localDir . DIRECTORY_SEPARATOR . $fileName) > 100) {
                    $existingPath = $localDir . DIRECTORY_SEPARATOR . $fileName;
                }
            }

            if ($existingPath) {
                // File sudah ada, pastikan juga tercermin di uploads
                if (!file_exists($mirrorDir . DIRECTORY_SEPARATOR . $fileName) && file_exists($existingPath)) {
                    copy($existingPath, $mirrorDir . DIRECTORY_SEPARATOR . $fileName);
                }
                $countSkipped++;
                $bar->advance();
                continue;
            }

            // Unduh dari Google Drive
            $binaryData = $this->downloadGoogleDriveBinary($fileId);
            if ($binaryData && strlen($binaryData) > 500) {
                // Simpan ke Mirror uploads/
                file_put_contents($mirrorDir . DIRECTORY_SEPARATOR . $fileName, $binaryData);

                // Simpan ke Master NAS Z: jika tersedia
                if ($primaryAccessible) {
                    file_put_contents($primaryDir . DIRECTORY_SEPARATOR . $fileName, $binaryData);
                }

                // Simpan ke Cadangan Lokal D: jika tersedia
                if ($localAccessible) {
                    file_put_contents($localDir . DIRECTORY_SEPARATOR . $fileName, $binaryData);
                }

                $countDownloaded++;
            } else {
                $countFailed++;
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);

        $this->info("==================================================================");
        $this->info(" HASIL PROSES UNDUH FOTO:");
        $this->line(" - Berhasil Diunduh Baru : <info>{$countDownloaded}</info> file");
        $this->line(" - Dilewati / Sudah Ada  : <comment>{$countSkipped}</comment> file (0 duplikasi)");
        $this->line(" - Gagal / Link Privat   : " . ($countFailed > 0 ? "<error>{$countFailed}</error>" : "0") . " file");
        $this->info("==================================================================");

        return 0;
    }

    /**
     * Unduh file biner dari Google Drive dengan penanganan direct download & thumbnail fallback.
     */
    protected function downloadGoogleDriveBinary(string $fileId): ?string
    {
        $downloadUrl = "https://drive.google.com/uc?export=download&id={$fileId}";

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $downloadUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)');
        $data = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode === 200 && strlen($data) > 1000 && !str_contains($data, '<!DOCTYPE html>')) {
            return $data;
        }

        // Fallback ke Google Drive Thumbnail Export API (sangat stabil untuk gambar preview)
        $thumbUrl = "https://drive.google.com/thumbnail?id={$fileId}&sz=w1200";
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $thumbUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)');
        $thumbData = curl_exec($ch);
        $thumbCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($thumbCode === 200 && strlen($thumbData) > 1000 && !str_contains($thumbData, '<!DOCTYPE html>')) {
            return $thumbData;
        }

        return null;
    }
}
