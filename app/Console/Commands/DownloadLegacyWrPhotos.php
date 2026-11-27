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
                            {--komponen-file= : Path kustom ke Data komponen.csv}
                            {--limit= : Batasi jumlah file yang diunduh (untuk uji coba)}
                            {--force : Unduh ulang meskipun file sudah ada di disk}
                            {--dry-run : Simulasi pengecekan tanpa mengunduh fisik}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Unduh berkas foto dari Google Drive spreadsheet WR lama dan simpan ke NAS Z: dan mirror uploads';

    /**
     * Execute the console command.
     */
    public function handle(ComponentPhotoStorageService $storageService): int
    {
        $this->info("==================================================================");
        $this->info(" HPK GUDANG - AUTO-DOWNLOAD FOTO GOOGLE DRIVE WR LAMA KE NAS (Z:)");
        $this->info("==================================================================");

        $dataKomponenFile = $this->option('komponen-file') ?: base_path('Data acuan/Form Work Request - Data komponen.csv');
        $dbKomponenFile = base_path('System WR lama/Spreadsheet/Form Work Request - Database Komponen.csv');
        $kompBaruFile = base_path('System WR lama/Spreadsheet/Form Work Request - Komponen baru.csv');
        $logWrFile = base_path('System WR lama/Spreadsheet/Form Work Request - Log WR.csv');

        $primaryDir = $storageService->getExternalDirectory();
        $uncDir = '\\\\192.168.1.150\\HPK_Eng_Online\\08.Project\\04. HPK Digitalisasi Program\\04. Gudang-Komponen\\Komonpen Gudang In House';
        $mirrorDir = $storageService->getMirrorDirectory();
        $localDir = 'D:\Ali Magang\KOMPONEN';

        // Pastikan direktori tujuan mirror tersedia
        if (!file_exists($mirrorDir)) {
            File::makeDirectory($mirrorDir, 0755, true);
        }

        $primaryAccessible = $this->isDirectoryWritable($primaryDir);
        $uncAccessible = (!$primaryAccessible) ? $this->isDirectoryWritable($uncDir) : false;
        $localAccessible = $this->isDirectoryWritable($localDir);

        $targetStorageDir = $primaryAccessible ? $primaryDir : ($uncAccessible ? $uncDir : null);

        $this->line("Status Direktori Penyimpanan:");
        $this->line(" - Primary NAS (Z:)      : " . ($primaryAccessible ? "<info>[TERHUBUNG & DAPAT DITULIS]</info> {$primaryDir}" : "<comment>[TIDAK TERJANGKAU]</comment> {$primaryDir}"));
        if (!$primaryAccessible) {
            $this->line(" - Fallback UNC (IP)     : " . ($uncAccessible ? "<info>[TERHUBUNG & DAPAT DITULIS]</info> {$uncDir}" : "<comment>[TIDAK TERJANGKAU]</comment> {$uncDir}"));
        }
        $this->line(" - Local Backup (D:)     : " . ($localAccessible ? "<info>[TERHUBUNG]</info> {$localDir}" : "<comment>[TIDAK TERJANGKAU]</comment> {$localDir}"));
        $this->line(" - Web Mirror (uploads/) : <info>[TERSEDIA]</info> {$mirrorDir}");
        $this->newLine();

        $dryRun = (bool) $this->option('dry-run');
        if ($dryRun) {
            $this->warn(">>> MODE SIMULASI (DRY-RUN) AKTIF: Tidak ada file yang akan diunduh/ditulis. <<<");
            $this->newLine();
        }

        // 1. Ekstrak data Komponen Baru terlebih dahulu untuk pemetaan NEW-YYYYMMDD-SEQ
        $this->line("Menganalisis berkas spreadsheet...");
        $baruCodeMap = []; // nama_clean -> NEW-YYYYMMDD-SEQ
        $dailyCounters = [];
        $partTargets = []; // sanitized_pn => ['part_number' => ..., 'name' => ..., 'url' => ..., 'file_id' => ...]

        // Sumber 0: Data Acuan Komponen Terbaru (Data acuan/Form Work Request - Data komponen.csv)
        if (file_exists($dataKomponenFile)) {
            $this->line(" - Membaca Data Acuan Komponen: " . basename($dataKomponenFile));
            $this->collectComponentCsvTargets($dataKomponenFile, 'Data Komponen Acuan', $storageService, $partTargets, true);
        }

        // Sumber 1: Master Catalog Lama (Database Komponen.csv)
        if (file_exists($dbKomponenFile)) {
            $this->line(" - Membaca Katalog Master: " . basename($dbKomponenFile));
            $this->collectComponentCsvTargets($dbKomponenFile, 'Database Komponen', $storageService, $partTargets, false);
        }

        // Sumber 2: Komponen Baru (Komponen baru.csv)
        if (file_exists($kompBaruFile)) {
            $this->line(" - Membaca Komponen Baru: " . basename($kompBaruFile));
            $h2 = fopen($kompBaruFile, 'r');
            fgetcsv($h2); fgetcsv($h2); fgetcsv($h2); fgetcsv($h2); // lewati header
            while (($r = fgetcsv($h2)) !== false) {
                $rawTgl = trim($r[1] ?? ''); // 17/06/2026
                $nama = trim($r[3] ?? '');
                $url = trim($r[4] ?? '');
                if (empty($nama)) continue;

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
                    $sanitizedPn = strtoupper($storageService->sanitizePartNumber($partCode));
                    if (!isset($partTargets[$sanitizedPn])) {
                        $partTargets[$sanitizedPn] = [
                            'part_number' => $partCode,
                            'name' => $nama,
                            'url' => $url,
                            'file_id' => $fileId,
                            'source' => 'Komponen Baru',
                        ];
                    }
                }
            }
            fclose($h2);
        }

        // Sumber 3: Log WR (Log WR.csv)
        if (file_exists($logWrFile)) {
            $this->line(" - Membaca Log Permintaan: " . basename($logWrFile));
            $h1 = fopen($logWrFile, 'r');
            fgetcsv($h1); fgetcsv($h1); // lewati header
            while (($r = fgetcsv($h1)) !== false) {
                $rawTgl = trim($r[1] ?? '');
                $noWr = trim($r[2] ?? '');
                $code = trim($r[3] ?? '');
                $nama = trim($r[4] ?? '');
                $url = trim($r[5] ?? '');

                if (empty($url) || !preg_match('/(?:id=|file\/d\/)([a-zA-Z0-9_-]+)/', $url, $um)) {
                    continue;
                }

                $fileId = $um[1];
                if ($code === 'Baru' || empty($code)) {
                    $cleanNameKey = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $nama));
                    $tglFormatted = !empty($rawTgl) ? str_replace('-', '', $rawTgl) : date('Ymd');

                    // Selaraskan dengan MigrateLegacySpreadsheetWr: prioritaskan segmen ke-6 No WR (/1, /2, ...)
                    $itemSeq = null;
                    if ($noWr !== '') {
                        $wrParts = explode('/', $noWr);
                        $lastSeg = end($wrParts);
                        if (is_numeric($lastSeg)) {
                            $itemSeq = sprintf('%03d', (int) $lastSeg);
                        }
                    }

                    if ($itemSeq !== null) {
                        $partCode = "NEW-{$tglFormatted}-{$itemSeq}";
                    } else {
                        if (!isset($baruCodeMap[$cleanNameKey])) {
                            $dailyCounters[$tglFormatted] = ($dailyCounters[$tglFormatted] ?? 0) + 1;
                            $seqStr = sprintf('%03d', $dailyCounters[$tglFormatted]);
                            $baruCodeMap[$cleanNameKey] = "NEW-{$tglFormatted}-{$seqStr}";
                        }
                        $partCode = $baruCodeMap[$cleanNameKey];
                    }
                } else {
                    $partCode = $code;
                }

                $sanitizedPn = strtoupper($storageService->sanitizePartNumber($partCode));
                if (!isset($partTargets[$sanitizedPn])) {
                    $partTargets[$sanitizedPn] = [
                        'part_number' => $partCode,
                        'name' => $nama,
                        'url' => $url,
                        'file_id' => $fileId,
                        'source' => 'Log WR',
                    ];
                }
            }
            fclose($h1);
        }

        $totalUniqueParts = count($partTargets);
        $this->info("Total part unik dengan tautan foto dari seluruh spreadsheet: {$totalUniqueParts}");

        // Build file existence index on primary / UNC / mirror
        $existingFiles = [];
        $dirsToCheck = array_filter([$targetStorageDir, $mirrorDir, $localDir]);
        foreach ($dirsToCheck as $dir) {
            if ($dir && is_dir($dir)) {
                $dirIndex = $storageService->getDirectoryIndex($dir);
                foreach ($dirIndex as $cleanBase => $actualFile) {
                    $existingFiles[strtoupper($cleanBase)] = $dir . DIRECTORY_SEPARATOR . $actualFile;
                }
            }
        }

        $force = (bool) $this->option('force');

        // Pisahkan yang sudah ada vs yang perlu diunduh
        $toDownload = [];
        $alreadyExistsCount = 0;

        foreach ($partTargets as $sanitizedPn => $meta) {
            if (!$force && isset($existingFiles[$sanitizedPn])) {
                $alreadyExistsCount++;
            } else {
                $toDownload[$sanitizedPn] = $meta;
            }
        }

        $this->line(" - Sudah ada di disk    : <info>{$alreadyExistsCount}</info> komponen (dilewati)");
        $this->line(" - Perlu diunduh & copy : <comment>" . count($toDownload) . "</comment> komponen");
        $this->newLine();

        if (count($toDownload) === 0) {
            $this->info("Semua foto komponen sudah lengkap tersedia di disk!");
            return 0;
        }

        $limit = $this->option('limit') ? (int) $this->option('limit') : null;
        if ($limit && $limit > 0) {
            $toDownload = array_slice($toDownload, 0, $limit, true);
            $this->warn("Mode pengujian aktif: Hanya memproses " . count($toDownload) . " komponen.");
        }

        if ($dryRun) {
            $this->info("Daftar komponen yang akan diunduh (Sampel 20 pertama):");
            $sampleCount = 0;
            foreach ($toDownload as $sanitizedPn => $meta) {
                $sampleCount++;
                $this->line("  {$sampleCount}. [{$meta['part_number']}] {$meta['name']} (File ID: {$meta['file_id']})");
                if ($sampleCount >= 20) break;
            }
            $this->newLine();
            $this->info("Simulasi selesai. Jalankan tanpa flag --dry-run untuk memulai pengunduhan nyata.");
            return 0;
        }

        $countDownloaded = 0;
        $countFailed = 0;
        $downloadedBinaries = []; // file_id => binary_content (memory cache antar part bernomor gambar sama)

        $bar = $this->output->createProgressBar(count($toDownload));
        $bar->start();

        foreach ($toDownload as $sanitizedPn => $meta) {
            $fileId = $meta['file_id'];
            $fileName = "{$sanitizedPn}.jpg";

            // Gunakan binary cache jika fileId yang sama sudah diunduh dalam sesi ini
            $binaryData = $downloadedBinaries[$fileId] ?? null;

            if ($binaryData === null) {
                $binaryData = $this->downloadGoogleDriveBinary($fileId);
                if ($binaryData && strlen($binaryData) > 500) {
                    $downloadedBinaries[$fileId] = $binaryData;
                } else {
                    $downloadedBinaries[$fileId] = false; // tandai gagal
                }
            }

            if ($binaryData !== false && $binaryData !== null) {
                // 1. Simpan ke Target Storage Utama (Drive Z: atau UNC)
                if ($targetStorageDir) {
                    @file_put_contents($targetStorageDir . DIRECTORY_SEPARATOR . $fileName, $binaryData);
                }

                // 2. Simpan ke Mirror uploads/ (akses web cepat)
                @file_put_contents($mirrorDir . DIRECTORY_SEPARATOR . $fileName, $binaryData);

                // 3. Simpan ke Cadangan Lokal D: jika ada
                if ($localAccessible) {
                    @file_put_contents($localDir . DIRECTORY_SEPARATOR . $fileName, $binaryData);
                }

                $countDownloaded++;
            } else {
                $countFailed++;
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);

        // Segarkan cache penyimpanan foto agar web langsung mendeteksi file baru
        $storageService->clearCache();

        $this->info("==================================================================");
        $this->info(" HASIL PROSES SINKRONISASI FOTO:");
        $this->line(" - Berhasil Disimpan Baru : <info>{$countDownloaded}</info> berkas komponen");
        $this->line(" - Sudah Ada Sebelumnya   : <comment>{$alreadyExistsCount}</comment> berkas komponen (0 duplikasi)");
        $this->line(" - Gagal / Link Privat    : " . ($countFailed > 0 ? "<error>{$countFailed}</error>" : "0") . " berkas");
        $this->line(" - Lokasi Tersimpan       : " . ($targetStorageDir ?: $mirrorDir));
        $this->info("==================================================================");
        $this->info("Cache indeks foto sistem telah disegarkan secara otomatis.");

        return 0;
    }

    /**
     * Ekstrak target foto dari CSV master komponen dengan deteksi header kolom dinamis.
     */
    protected function collectComponentCsvTargets(
        string $filePath,
        string $sourceLabel,
        ComponentPhotoStorageService $storageService,
        array &$partTargets,
        bool $overwriteExisting
    ): void {
        $handle = fopen($filePath, 'r');
        if (!$handle) {
            return;
        }

        fgetcsv($handle); // baris 1
        $header = fgetcsv($handle) ?: [];

        $codeIdx = 2;
        $nameIdx = 3;
        $imgIdx = 4;
        foreach ($header as $idx => $rawHeader) {
            $h = strtolower(trim((string) $rawHeader));
            if ($h === '' || str_contains($h, '|')) {
                continue;
            }
            if (str_contains($h, 'kode komponen')) {
                $codeIdx = (int) $idx;
            } elseif (str_contains($h, 'nama komponen')) {
                $nameIdx = (int) $idx;
            } elseif (str_contains($h, 'gambar')) {
                $imgIdx = (int) $idx;
            }
        }

        while (($r = fgetcsv($handle)) !== false) {
            $code = trim($r[$codeIdx] ?? '');
            $nama = trim($r[$nameIdx] ?? '');
            $url = trim($r[$imgIdx] ?? '');

            if ($code === '' || $url === '') {
                continue;
            }

            if (preg_match('/(?:id=|file\/d\/)([a-zA-Z0-9_-]+)/', $url, $um)) {
                $sanitizedPn = strtoupper($storageService->sanitizePartNumber($code));
                if ($overwriteExisting || !isset($partTargets[$sanitizedPn])) {
                    $partTargets[$sanitizedPn] = [
                        'part_number' => $code,
                        'name' => $nama,
                        'url' => $url,
                        'file_id' => $um[1],
                        'source' => $sourceLabel,
                    ];
                }
            }
        }
        fclose($handle);
    }

    /**
     * Periksa apakah direktori dapat ditulis (aman untuk SMB Mapped Drives Windows).
     */
    protected function isDirectoryWritable(?string $dir): bool
    {
        if (!$dir || !file_exists($dir) || !is_dir($dir)) {
            return false;
        }

        $testFile = rtrim($dir, '\\/') . DIRECTORY_SEPARATOR . '.test_writable_' . uniqid();
        $ok = @file_put_contents($testFile, '1');
        if ($ok !== false) {
            @unlink($testFile);
            return true;
        }

        return false;
    }

    /**
     * Unduh file biner dari Google Drive dengan penanganan direct download & thumbnail fallback.
     */
    protected function downloadGoogleDriveBinary(string $fileId): ?string
    {
        // 1. Coba Google Drive Thumbnail Export API (sangat stabil, cepat, dan format gambar murni)
        $thumbUrl = "https://drive.google.com/thumbnail?id={$fileId}&sz=w1200";
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $thumbUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 20);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)');
        $thumbData = curl_exec($ch);
        $thumbCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($thumbCode === 200 && strlen($thumbData) > 1000 && !str_contains($thumbData, '<!DOCTYPE html>')) {
            return $thumbData;
        }

        // 2. Fallback direct download uc?export=download
        $downloadUrl = "https://drive.google.com/uc?export=download&id={$fileId}";
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $downloadUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 20);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)');
        $data = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode === 200 && strlen($data) > 1000 && !str_contains($data, '<!DOCTYPE html>')) {
            return $data;
        }

        return null;
    }
}
