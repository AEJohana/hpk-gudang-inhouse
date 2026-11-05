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
     * Get the primary external storage directory (e.g. D:\Ali Magang\KOMPONEN or /mnt/nas-gudang/Gudang Komponen In House).
     */
    public function getExternalDirectory(): string
    {
        $dir = '';
        if (function_exists('config') && app()->has('config')) {
            $dir = config('services.components.photo_dir') ?: env('COMPONENT_EXTERNAL_PHOTO_DIR', 'D:\Ali Magang\KOMPONEN');
        } else {
            $dir = env('COMPONENT_EXTERNAL_PHOTO_DIR', 'D:\Ali Magang\KOMPONEN');
        }

        $dir = trim((string)$dir, " \t\n\r\0\x0B\"'");

        // Normalisasi path Linux jika pengguna menulis mnt/... tanpa leading slash
        if (!empty($dir) && !str_starts_with($dir, '/') && !preg_match('/^[a-zA-Z]:/', $dir) && str_starts_with($dir, 'mnt/')) {
            $dir = '/' . $dir;
        }

        return $dir;
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
     * Store an uploaded file (JPG, PNG, PDF, etc.) directly to D:\Ali Magang\KOMPONEN
     * with standard naming [part_number].[ext] and sync to mirror.
     */
    public function storeUploadedFile(UploadedFile $file, string $partNumber): string
    {
        $cleanPn = $this->sanitizePartNumber($partNumber);
        $ext = strtolower($file->getClientOriginalExtension() ?: $file->guessExtension() ?: 'jpg');
        $fileName = "{$cleanPn}.{$ext}";

        $this->ensureDirectoriesExist();
        $this->cleanExistingFilesForPart($cleanPn, $ext);

        // 1. Simpan ke folder master eksternal D:\Ali Magang\KOMPONEN
        $extDir = $this->getExternalDirectory();
        if (file_exists($extDir) && is_writable($extDir)) {
            $destFile = rtrim($extDir, '\\/') . DIRECTORY_SEPARATOR . $fileName;
            copy($file->getRealPath(), $destFile);
        }

        // 2. Simpan salinan cermin di public/uploads/components/
        $mirrorDir = $this->getMirrorDirectory();
        $file->move($mirrorDir, $fileName);

        return "uploads/components/{$fileName}";
    }

    /**
     * Store camera snapshot (base64 string) to D:\Ali Magang\KOMPONEN and mirror.
     */
    public function storeBase64(string $base64Data, string $partNumber): ?string
    {
        $imageParts = explode(';base64,', $base64Data);
        if (count($imageParts) !== 2) {
            return null;
        }

        $binary = base64_decode($imageParts[1]);
        if (!$binary) {
            return null;
        }

        return $this->storeBinary($binary, $partNumber, 'jpg');
    }

    /**
     * Store raw binary data (e.g. from Google Drive auto-download or stream)
     * directly to D:\Ali Magang\KOMPONEN and mirror.
     */
    public function storeBinary(string $binaryData, string $partNumber, string $ext = 'jpg'): string
    {
        $cleanPn = $this->sanitizePartNumber($partNumber);
        $cleanExt = strtolower(trim($ext, '. '));
        if (!in_array($cleanExt, self::SUPPORTED_EXTENSIONS, true)) {
            $cleanExt = 'jpg';
        }
        $fileName = "{$cleanPn}.{$cleanExt}";

        $this->ensureDirectoriesExist();
        $this->cleanExistingFilesForPart($cleanPn, $cleanExt);

        // 1. Simpan ke D:\Ali Magang\KOMPONEN
        $extDir = $this->getExternalDirectory();
        if (file_exists($extDir) && is_writable($extDir)) {
            $destPath = rtrim($extDir, '\\/') . DIRECTORY_SEPARATOR . $fileName;
            file_put_contents($destPath, $binaryData);
        }

        // 2. Simpan cermin ke public/uploads/components/
        $mirrorPath = $this->getMirrorDirectory() . DIRECTORY_SEPARATOR . $fileName;
        file_put_contents($mirrorPath, $binaryData);

        return "uploads/components/{$fileName}";
    }

    /**
     * Find local physical file path for a component across external storage and mirror.
     */
    public function findLocalPhoto(string $partNumber, ?string $imagePath = null): ?string
    {
        if (empty($partNumber)) {
            return null;
        }

        $cleanPn = $this->sanitizePartNumber($partNumber);
        $rawPn = trim($partNumber);
        $candidateExts = $this->getCandidateExtensions();

        // Variasi nama file yang mungkin tersimpan di folder NAS (sanitasi, raw, upper, lower)
        $candidateNames = array_values(array_unique(array_filter([
            $cleanPn,
            $rawPn,
            strtoupper($cleanPn),
            strtolower($cleanPn),
            strtoupper($rawPn),
            strtolower($rawPn),
        ])));

        // 1. Cek folder eksternal / NAS (Prioritas Utama, misal: /mnt/nas-gudang/Gudang Komponen In House)
        $extDir = $this->getExternalDirectory();
        if (!empty($extDir) && file_exists($extDir)) {
            $extDirTrimmed = rtrim($extDir, '\\/');
            foreach ($candidateNames as $name) {
                foreach ($candidateExts as $ext) {
                    $fullPath = $extDirTrimmed . DIRECTORY_SEPARATOR . "{$name}.{$ext}";
                    if (file_exists($fullPath) && filesize($fullPath) > 50) {
                        return realpath($fullPath) ?: $fullPath;
                    }
                }
            }

            // Cek jika imagePath di database merupakan nama file langsung yang ada di NAS
            if ($imagePath && !str_starts_with($imagePath, 'http')) {
                $baseImg = basename($imagePath);
                $directExtPath = $extDirTrimmed . DIRECTORY_SEPARATOR . $baseImg;
                if (file_exists($directExtPath) && filesize($directExtPath) > 50) {
                    return realpath($directExtPath) ?: $directExtPath;
                }
            }
        }

        // 2. Cek folder cermin lokal public/uploads/components/
        $candidateMirrorNames = array_values(array_unique(array_filter([$cleanPn, $rawPn])));
        foreach ($candidateMirrorNames as $name) {
            foreach ($candidateExts as $ext) {
                $localRel = "uploads/components/{$name}.{$ext}";
                $localFull = $this->resolvePublicPath($localRel);
                if (file_exists($localFull) && filesize($localFull) > 50) {
                    return realpath($localFull) ?: $localFull;
                }
            }
        }

        // 3. Cek image_path di database jika berupa file lokal valid di webroot
        if ($imagePath && !str_starts_with($imagePath, 'http')) {
            $fullDbPath = $this->resolvePublicPath($imagePath);
            if (file_exists($fullDbPath) && filesize($fullDbPath) > 50) {
                return realpath($fullDbPath) ?: $fullDbPath;
            }
        }

        return null;
    }

    /**
     * Delete existing physical photos for a part when replaced.
     */
    public function cleanExistingFilesForPart(string $cleanPn, string $exceptExt = ''): void
    {
        $extDir = $this->getExternalDirectory();
        $mirrorDir = $this->getMirrorDirectory();
        $candidateExts = $this->getCandidateExtensions();
        $exceptExtLower = strtolower(trim($exceptExt, '. '));

        foreach ($candidateExts as $ext) {
            if (strtolower($ext) === $exceptExtLower) {
                continue;
            }

            // External dir cleanup
            if (file_exists($extDir)) {
                $p1 = rtrim($extDir, '\\/') . DIRECTORY_SEPARATOR . "{$cleanPn}.{$ext}";
                if (file_exists($p1)) {
                    @unlink($p1);
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

        $extDir = $this->getExternalDirectory();
        if (!file_exists($extDir)) {
            @mkdir($extDir, 0777, true);
        }
    }
}
