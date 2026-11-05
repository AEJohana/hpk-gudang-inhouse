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
     * Get the primary external storage directory (e.g. Z:\... or D:\Ali Magang\KOMPONEN).
     */
    public function getExternalDirectory(): string
    {
        if (function_exists('config') && app()->has('config')) {
            return config('services.components.photo_dir') ?: env('COMPONENT_EXTERNAL_PHOTO_DIR', 'Z:\08.Project\04. HPK Digitalisasi Program\04. Gudang-Komponen\Komonpen Gudang In House');
        }
        return env('COMPONENT_EXTERNAL_PHOTO_DIR', 'Z:\08.Project\04. HPK Digitalisasi Program\04. Gudang-Komponen\Komonpen Gudang In House');
    }

    /**
     * Get the local webroot mirror directory (public/uploads/components).
     */
    public function getMirrorDirectory(): string
    {
        return public_path('uploads/components');
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

        // 1. Cek folder eksternal utama (Prioritas Utama, misal Drive Z:)
        $extDir = $this->getExternalDirectory();
        if (!empty($extDir) && file_exists($extDir)) {
            foreach (self::SUPPORTED_EXTENSIONS as $ext) {
                $fullPath = rtrim($extDir, '\\/') . DIRECTORY_SEPARATOR . "{$cleanPn}.{$ext}";
                if (file_exists($fullPath) && filesize($fullPath) > 50) {
                    return $fullPath;
                }
            }
        }

        // 1b. Fallback Cadangan Fisik Lokal (D:\Ali Magang\KOMPONEN jika folder Z tidak terjangkau)
        $localBackupDir = 'D:\Ali Magang\KOMPONEN';
        if ($extDir !== $localBackupDir && file_exists($localBackupDir)) {
            foreach (self::SUPPORTED_EXTENSIONS as $ext) {
                $backupPath = rtrim($localBackupDir, '\\/') . DIRECTORY_SEPARATOR . "{$cleanPn}.{$ext}";
                if (file_exists($backupPath) && filesize($backupPath) > 50) {
                    return $backupPath;
                }
            }
        }

        // 2. Cek folder cermin lokal public/uploads/components/
        foreach (self::SUPPORTED_EXTENSIONS as $ext) {
            $localRel = "uploads/components/{$cleanPn}.{$ext}";
            $localFull = public_path($localRel);
            if (file_exists($localFull) && filesize($localFull) > 50) {
                return $localFull;
            }
        }

        // 3. Cek image_path di database jika berupa file lokal valid
        if ($imagePath && !str_starts_with($imagePath, 'http')) {
            $fullDbPath = public_path($imagePath);
            if (file_exists($fullDbPath) && filesize($fullDbPath) > 50) {
                return $fullDbPath;
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

        foreach (self::SUPPORTED_EXTENSIONS as $ext) {
            if ($ext === $exceptExt) {
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
