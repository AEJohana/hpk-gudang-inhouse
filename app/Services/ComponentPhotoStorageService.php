<?php

namespace App\Services;

use App\Models\Component;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;

class ComponentPhotoStorageService
{
    /**
     * Supported technical drawing and photo extensions.
     */
    public const SUPPORTED_EXTENSIONS = ['jpg', 'jpeg', 'png', 'webp', 'pdf', 'svg'];

    /**
     * Get candidate extensions including uppercase variants for Linux case-sensitive filesystems.
     */
    public function getCandidateExtensions(): array
    {
        $exts = [];
        foreach (self::SUPPORTED_EXTENSIONS as $ext) {
            $exts[] = strtolower($ext);
            $exts[] = strtoupper($ext);
        }
        return array_values(array_unique($exts));
    }

    /**
     * Resolve public directory path safely even in isolated testing containers.
     */
    public function resolvePublicPath(string $path = ''): string
    {
        $base = '';
        if (function_exists('app') && method_exists(app(), 'publicPath')) {
            $base = public_path();
        } elseif (function_exists('app') && method_exists(app(), 'basePath')) {
            $base = base_path('public');
        } else {
            $base = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'public';
        }

        $cleanPath = ltrim(str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $path), '\\/');
        return $cleanPath ? rtrim($base, '\\/') . DIRECTORY_SEPARATOR . $cleanPath : $base;
    }

    /**
     * Get the primary external storage directory (e.g. Z:\... or D:\Ali Magang\KOMPONEN or /mnt/nas-gudang/...).
     */
    public function getExternalDirectory(): string
    {
        $dir = '';
        if (function_exists('config') && app()->has('config')) {
            $dir = config('services.components.photo_dir') ?: env('COMPONENT_EXTERNAL_PHOTO_DIR', 'Z:\08.Project\04. HPK Digitalisasi Program\04. Gudang-Komponen\Komonpen Gudang In House');
        } else {
            $dir = env('COMPONENT_EXTERNAL_PHOTO_DIR', 'Z:\08.Project\04. HPK Digitalisasi Program\04. Gudang-Komponen\Komonpen Gudang In House');
        }

        $dir = trim((string)$dir, " \t\n\r\0\x0B\"'");

        // Normalisasi path Linux jika pengguna menulis mnt/... tanpa leading slash
        if (!empty($dir) && !str_starts_with($dir, '/') && !preg_match('/^[a-zA-Z]:/', $dir) && !str_starts_with($dir, '\\\\') && str_starts_with($dir, 'mnt/')) {
            $dir = '/' . $dir;
        }

        return $dir;
    }

    /**
     * Get fallback UNC path for external master directory (\\192.168.1.150\...).
     */
    public function getUncDirectory(): string
    {
        $extDir = $this->getExternalDirectory();
        if (preg_match('/^[zZ]:/i', $extDir)) {
            $subPath = ltrim(preg_replace('/^[zZ]:[\\\\\/]*/i', '', $extDir), '\\/');
            return '\\\\192.168.1.150\\HPK_Eng_Online\\' . $subPath;
        }
        return '\\\\192.168.1.150\\HPK_Eng_Online\\08.Project\\04. HPK Digitalisasi Program\\04. Gudang-Komponen\\Komonpen Gudang In House';
    }

    /**
     * Get dedicated directory for ad-hoc / new components created via Work Request.
     * Path: Z:\08.Project\04. HPK Digitalisasi Program\04. Gudang-Komponen\Komonpen Gudang In House\NewKomponenWR
     */
    public function getNewWrComponentDirectory(): string
    {
        return rtrim($this->getExternalDirectory(), '\\/') . DIRECTORY_SEPARATOR . 'NewKomponenWR';
    }

    /**
     * Get fallback UNC path for NewKomponenWR.
     */
    public function getNewWrUncDirectory(): string
    {
        return rtrim($this->getUncDirectory(), '\\/') . DIRECTORY_SEPARATOR . 'NewKomponenWR';
    }

    /**
     * Check if a directory is physically writable (safe for Windows SMB Mapped Drives).
     */
    public function isDirectoryWritable(?string $dir): bool
    {
        if (!$dir || !file_exists($dir) || !is_dir($dir)) {
            return false;
        }

        $testFile = rtrim($dir, '\\/') . DIRECTORY_SEPARATOR . '.test_perm_' . uniqid();
        $ok = @file_put_contents($testFile, '1');
        if ($ok !== false) {
            @unlink($testFile);
            return true;
        }

        return false;
    }

    /**
     * Get the local webroot mirror directory (public/uploads/components).
     */
    public function getMirrorDirectory(): string
    {
        return $this->resolvePublicPath('uploads/components');
    }

    /**
     * Sanitize part number to a safe filesystem string.
     */
    public function sanitizePartNumber(string $partNumber): string
    {
        $clean = preg_replace('/[^a-zA-Z0-9_-]/', '-', trim($partNumber));
        return $clean ?: 'part-' . time();
    }

    /**
     * Store an uploaded file (JPG, PNG, PDF, etc.) directly to Drive Z: (Master or NewKomponenWR)
     * with standard naming [part_number].[ext] and sync to mirror.
     */
    public function storeUploadedFile(UploadedFile $file, string $partNumber, bool $isNewWr = false): string
    {
        $cleanPn = $this->sanitizePartNumber($partNumber);
        $ext = strtolower($file->getClientOriginalExtension() ?: $file->guessExtension() ?: 'jpg');
        $fileName = "{$cleanPn}.{$ext}";

        $this->ensureDirectoriesExist();
        $this->cleanExistingFilesForPart($cleanPn, $ext, $isNewWr);

        // 1. Simpan salinan ke cermin webroot public/uploads/components/
        $mirrorDir = $this->getMirrorDirectory();
        $file->move($mirrorDir, $fileName);
        $savedMirrorPath = $mirrorDir . DIRECTORY_SEPARATOR . $fileName;

        // 2. Simpan fisik ke Drive Z: (Folder Utama Master atau Subfolder NewKomponenWR)
        $targetNasDir = $isNewWr ? $this->getNewWrComponentDirectory() : $this->getExternalDirectory();
        $targetUncDir = $isNewWr ? $this->getNewWrUncDirectory() : $this->getUncDirectory();

        $destDir = $this->isDirectoryWritable($targetNasDir) ? $targetNasDir : ($this->isDirectoryWritable($targetUncDir) ? $targetUncDir : null);
        if ($destDir) {
            @copy($savedMirrorPath, rtrim($destDir, '\\/') . DIRECTORY_SEPARATOR . $fileName);
        }

        // 3. Reset cache memori dan Laravel
        $this->clearCache();

        return "uploads/components/{$fileName}";
    }

    /**
     * Store camera snapshot (base64 string) to Drive Z: (Master or NewKomponenWR) and mirror.
     */
    public function storeBase64(string $base64Data, string $partNumber, bool $isNewWr = false): ?string
    {
        $imageParts = explode(';base64,', $base64Data);
        if (count($imageParts) !== 2) {
            return null;
        }

        $binary = base64_decode($imageParts[1]);
        if (!$binary) {
            return null;
        }

        return $this->storeBinary($binary, $partNumber, 'jpg', $isNewWr);
    }

    /**
     * Store raw binary data directly to Drive Z: (Master or NewKomponenWR) and mirror.
     */
    public function storeBinary(string $binaryData, string $partNumber, string $ext = 'jpg', bool $isNewWr = false): string
    {
        $cleanPn = $this->sanitizePartNumber($partNumber);
        $cleanExt = strtolower(trim($ext, '. '));
        if (!in_array($cleanExt, self::SUPPORTED_EXTENSIONS, true)) {
            $cleanExt = 'jpg';
        }
        $fileName = "{$cleanPn}.{$cleanExt}";

        $this->ensureDirectoriesExist();
        $this->cleanExistingFilesForPart($cleanPn, $cleanExt, $isNewWr);

        // 1. Simpan salinan cermin di public/uploads/components/
        $mirrorPath = $this->getMirrorDirectory() . DIRECTORY_SEPARATOR . $fileName;
        @file_put_contents($mirrorPath, $binaryData);

        // 2. Simpan fisik ke Drive Z: (Folder Utama Master atau Subfolder NewKomponenWR)
        $targetNasDir = $isNewWr ? $this->getNewWrComponentDirectory() : $this->getExternalDirectory();
        $targetUncDir = $isNewWr ? $this->getNewWrUncDirectory() : $this->getUncDirectory();

        $destDir = $this->isDirectoryWritable($targetNasDir) ? $targetNasDir : ($this->isDirectoryWritable($targetUncDir) ? $targetUncDir : null);
        if ($destDir) {
            @file_put_contents(rtrim($destDir, '\\/') . DIRECTORY_SEPARATOR . $fileName, $binaryData);
        }

        // 3. Reset cache memori dan Laravel
        $this->clearCache();

        return "uploads/components/{$fileName}";
    }

    /**
     * In-memory cache for resolved photo paths within the same request lifecycle.
     */
    protected static array $resolvedCache = [];

    /**
     * In-memory directory indexes to avoid repetitive filesystem/SMB scans.
     */
    protected static array $dirIndexMemoryCache = [];

    /**
     * Clear memory caches.
     */
    public static function clearDirectoryIndexCache(): void
    {
        self::$resolvedCache = [];
        self::$dirIndexMemoryCache = [];
    }

    /**
     * Clear memory and persistent caches for photo indexing and storage status.
     */
    public function clearCache(): void
    {
        self::clearDirectoryIndexCache();
        if (function_exists('app') && app()->has('cache')) {
            try {
                \Illuminate\Support\Facades\Cache::forget('component_photo_index');
                \Illuminate\Support\Facades\Cache::forget('nas_status_info');
            } catch (\Throwable $e) {}
        }
    }

    /**
     * Get indexed filename map for a directory (keys in lowercase, values are real file basenames).
     */
    public function getDirectoryIndex(string $dir): array
    {
        $realDir = @realpath($dir) ?: $dir;
        $cacheKey = md5($realDir);

        if (isset(self::$dirIndexMemoryCache[$cacheKey])) {
            return self::$dirIndexMemoryCache[$cacheKey];
        }

        if (!@is_dir($realDir)) {
            return self::$dirIndexMemoryCache[$cacheKey] = [];
        }

        $resolver = function() use ($realDir) {
            $files = @scandir($realDir);
            if (!is_array($files)) {
                return [];
            }

            $index = [];
            foreach ($files as $f) {
                if ($f === '.' || $f === '..') {
                    continue;
                }
                $base = pathinfo($f, PATHINFO_FILENAME);
                $raw = strtolower($base);
                $clean = strtolower(preg_replace('/[^a-zA-Z0-9_-]/', '-', $base));
                $condensed = preg_replace('/[^a-z0-9]/', '', $raw);

                $index[$raw] = $f;
                $index[$clean] = $f;
                $index[$condensed] = $f;
                $index[strtolower($f)] = $f; // Also indexed with extension
            }
            return $index;
        };

        if (function_exists('app') && app()->has('cache') && !app()->environment('testing')) {
            $index = \Illuminate\Support\Facades\Cache::remember("dir_index_{$cacheKey}", 60, $resolver);
            return self::$dirIndexMemoryCache[$cacheKey] = $index;
        }

        return self::$dirIndexMemoryCache[$cacheKey] = $resolver();
    }

    /**
     * Find local physical file path for a component across external storage and mirror with high-speed caching.
     */
    public function findLocalPhoto(string $partNumber, ?string $imagePath = null): ?string
    {
        if (empty($partNumber)) {
            return null;
        }

        $cacheKey = md5(trim($partNumber) . '|' . ($imagePath ?? ''));
        if (array_key_exists($cacheKey, self::$resolvedCache)) {
            return self::$resolvedCache[$cacheKey];
        }

        // Cache resolution ONLY when successfully found (prevent negative cache poisoning)
        if (function_exists('app') && app()->has('cache') && !app()->environment('testing')) {
            $cacheName = "comp_photo_resolved_{$cacheKey}";
            $cached = \Illuminate\Support\Facades\Cache::get($cacheName);
            if ($cached !== null) {
                return self::$resolvedCache[$cacheKey] = $cached;
            }

            $resolved = $this->doFindLocalPhoto($partNumber, $imagePath);
            if ($resolved) {
                \Illuminate\Support\Facades\Cache::put($cacheName, $resolved, 600); // 10 minutes for positive hits
            }
            return self::$resolvedCache[$cacheKey] = $resolved;
        }

        return self::$resolvedCache[$cacheKey] = $this->doFindLocalPhoto($partNumber, $imagePath);
    }

    /**
     * Internal filesystem search for component photo prioritizing Master Shared Drive Z: & UNC.
     */
    protected function doFindLocalPhoto(string $partNumber, ?string $imagePath = null): ?string
    {
        $cleanPn = $this->sanitizePartNumber($partNumber);
        $rawPn = trim($partNumber);
        $condensedPn = preg_replace('/[^a-zA-Z0-9]/', '', $partNumber);

        $keysToTry = array_values(array_unique(array_filter([
            strtolower($rawPn),
            strtolower($cleanPn),
            strtolower($condensedPn)
        ])));

        // =========================================================================
        // PRIORITAS 1: MASTER NETWORK DRIVE Z: / UNC & SUBFOLDER NewKomponenWR
        // Kecepatan mikrodetik (In-Memory Directory Indexing, 4ms untuk seluruh katalog)
        // =========================================================================
        $extDir = $this->getExternalDirectory();
        $externalDirsToCheck = [];
        if (!empty($extDir)) {
            $externalDirsToCheck[] = $extDir;
            $newWrDir = $this->getNewWrComponentDirectory();
            if (!in_array($newWrDir, $externalDirsToCheck, true)) {
                $externalDirsToCheck[] = $newWrDir;
            }

            $uncDir = $this->getUncDirectory();
            if (!in_array($uncDir, $externalDirsToCheck, true)) {
                $externalDirsToCheck[] = $uncDir;
            }
            $newWrUnc = $this->getNewWrUncDirectory();
            if (!in_array($newWrUnc, $externalDirsToCheck, true)) {
                $externalDirsToCheck[] = $newWrUnc;
            }
        }

        foreach ($externalDirsToCheck as $dir) {
            if (@file_exists($dir)) {
                $index = $this->getDirectoryIndex($dir);
                $dirTrimmed = rtrim($dir, '\\/');

                foreach ($keysToTry as $lk) {
                    if (isset($index[$lk])) {
                        $full = $dirTrimmed . DIRECTORY_SEPARATOR . $index[$lk];
                        if (@file_exists($full) && @filesize($full) > 50) {
                            return realpath($full) ?: $full;
                        }
                    }
                }

                if ($imagePath && !str_starts_with($imagePath, 'http')) {
                    $baseImg = strtolower(basename($imagePath));
                    if (isset($index[$baseImg])) {
                        $full = $dirTrimmed . DIRECTORY_SEPARATOR . $index[$baseImg];
                        if (@file_exists($full) && @filesize($full) > 50) {
                            return realpath($full) ?: $full;
                        }
                    }
                }
            }
        }

        // =========================================================================
        // PRIORITAS 2: Folder Cermin Lokal (public/uploads/components/)
        // =========================================================================
        $mirrorDir = $this->getMirrorDirectory();
        if (@file_exists($mirrorDir)) {
            $mirrorIndex = $this->getDirectoryIndex($mirrorDir);
            $mirrorTrimmed = rtrim($mirrorDir, '\\/');

            foreach ($keysToTry as $lk) {
                if (isset($mirrorIndex[$lk])) {
                    $full = $mirrorTrimmed . DIRECTORY_SEPARATOR . $mirrorIndex[$lk];
                    if (@file_exists($full) && @filesize($full) > 50) {
                        return realpath($full) ?: $full;
                    }
                }
            }
        }

        // =========================================================================
        // PRIORITAS 3: image_path di database jika berupa file lokal valid di webroot
        // =========================================================================
        if ($imagePath && !str_starts_with($imagePath, 'http')) {
            $fullDbPath = $this->resolvePublicPath($imagePath);
            if (@file_exists($fullDbPath) && @filesize($fullDbPath) > 50) {
                return realpath($fullDbPath) ?: $fullDbPath;
            }
        }

        // =========================================================================
        // PRIORITAS 4: Cadangan Fisik Lokal D:\Ali Magang\KOMPONEN (Jika Tersedia)
        // =========================================================================
        $localBackupDir = env('COMPONENT_LOCAL_BACKUP_DIR', 'D:\Ali Magang\KOMPONEN');
        if (@file_exists($localBackupDir) && !in_array($localBackupDir, $externalDirsToCheck, true)) {
            $backupIndex = $this->getDirectoryIndex($localBackupDir);
            $localTrimmed = rtrim($localBackupDir, '\\/');

            foreach ($keysToTry as $lk) {
                if (isset($backupIndex[$lk])) {
                    $full = $localTrimmed . DIRECTORY_SEPARATOR . $backupIndex[$lk];
                    if (@file_exists($full) && @filesize($full) > 50) {
                        return realpath($full) ?: $full;
                    }
                }
            }
        }

        return null;
    }

    /**
     * Delete existing physical photos for a part when replaced.
     */
    public function cleanExistingFilesForPart(string $cleanPn, string $exceptExt = '', bool $isNewWr = false): void
    {
        $extDirs = array_filter([
            $this->getExternalDirectory(),
            $this->getNewWrComponentDirectory(),
        ]);
        $mirrorDir = $this->getMirrorDirectory();
        $candidateExts = $this->getCandidateExtensions();
        $exceptExtLower = strtolower(trim($exceptExt, '. '));

        foreach ($candidateExts as $ext) {
            if (strtolower($ext) === $exceptExtLower) {
                continue;
            }

            // External dirs cleanup
            foreach ($extDirs as $extDir) {
                if (file_exists($extDir)) {
                    $p1 = rtrim($extDir, '\\/') . DIRECTORY_SEPARATOR . "{$cleanPn}.{$ext}";
                    if (file_exists($p1)) {
                        @unlink($p1);
                    }
                }
            }

            // Mirror dir cleanup
            $p2 = $mirrorDir . DIRECTORY_SEPARATOR . "{$cleanPn}.{$ext}";
            if (file_exists($p2)) {
                @unlink($p2);
            }
        }
    }

    /**
     * Ensure storage directories exist.
     */
    protected function ensureDirectoriesExist(): void
    {
        $mirrorDir = $this->getMirrorDirectory();
        if (!file_exists($mirrorDir)) {
            @mkdir($mirrorDir, 0777, true);
        }

        $newWrDir = $this->getNewWrComponentDirectory();
        if (!file_exists($newWrDir) && $this->isDirectoryWritable($this->getExternalDirectory())) {
            @mkdir($newWrDir, 0777, true);
        }
    }

    /**
     * Get live health and metrics of NAS master storage and local mirror.
     */
    public function getStorageStatus(): array
    {
        $primaryDir = $this->getExternalDirectory();
        $uncDir = '\\\\192.168.1.150\\HPK_Eng_Online\\08.Project\\04. HPK Digitalisasi Program\\04. Gudang-Komponen\\Komonpen Gudang In House';
        $mirrorDir = $this->getMirrorDirectory();

        $primaryExists = !empty($primaryDir) && @file_exists($primaryDir);
        $uncExists = @file_exists($uncDir);
        $mirrorExists = @file_exists($mirrorDir);

        $primaryFiles = $primaryExists ? count(@glob(rtrim($primaryDir, '\\/') . '/*.*') ?: []) : 0;
        $uncFiles = $uncExists ? count(@glob(rtrim($uncDir, '\\/') . '/*.*') ?: []) : 0;
        $mirrorFiles = $mirrorExists ? count(@glob(rtrim($mirrorDir, '\\/') . '/*.*') ?: []) : 0;

        $totalComponents = Component::where('source', 'master')->orWhereNull('source')->count();
        $nasFilesCount = max($primaryFiles, $uncFiles);
        $pendingSyncCount = max(0, $nasFilesCount - $mirrorFiles);

        return [
            'primary_dir' => $primaryDir,
            'primary_connected' => $primaryExists,
            'primary_files_count' => $primaryFiles,
            'unc_dir' => $uncDir,
            'unc_connected' => $uncExists,
            'unc_files_count' => $uncFiles,
            'is_nas_connected' => ($primaryExists || $uncExists),
            'mirror_dir' => $mirrorDir,
            'mirror_files_count' => $mirrorFiles,
            'nas_total_files' => $nasFilesCount,
            'pending_sync_count' => $pendingSyncCount,
            'total_components' => $totalComponents,
        ];
    }

    /**
     * Synchronize photos from NAS to local webroot mirror.
     */
    public function syncNasToMirror(): array
    {
        $primaryDir = $this->getExternalDirectory();
        $uncDir = '\\\\192.168.1.150\\HPK_Eng_Online\\08.Project\\04. HPK Digitalisasi Program\\04. Gudang-Komponen\\Komonpen Gudang In House';
        $sourceDir = (@file_exists($primaryDir)) ? $primaryDir : ((@file_exists($uncDir)) ? $uncDir : null);

        if (!$sourceDir) {
            return [
                'success' => false,
                'message' => 'Tidak dapat menjangkau Server NAS (baik via Drive Z: maupun UNC IP \\\\192.168.1.150). Pastikan jaringan pabrik terhubung.',
                'synced_count' => 0,
            ];
        }

        $mirrorDir = $this->getMirrorDirectory();
        if (!file_exists($mirrorDir)) {
            @mkdir($mirrorDir, 0777, true);
        }

        $files = File::files($sourceDir);
        $synced = 0;

        foreach ($files as $file) {
            $dest = rtrim($mirrorDir, '\\/') . DIRECTORY_SEPARATOR . $file->getFilename();
            if (!file_exists($dest) || filesize($dest) !== $file->getSize()) {
                if (@copy($file->getRealPath(), $dest)) {
                    $synced++;
                }
            }
        }

        // Reset memory & Laravel cache
        self::clearDirectoryIndexCache();
        if (function_exists('app') && app()->has('cache')) {
            try {
                \Illuminate\Support\Facades\Cache::flush();
            } catch (\Throwable $e) {}
        }

        return [
            'success' => true,
            'message' => "Berhasil menyinkronkan {$synced} berkas foto dari NAS ({$sourceDir}) ke Webroot Mirror ({$mirrorDir}).",
            'synced_count' => $synced,
            'total_source_files' => count($files),
        ];
    }
}
