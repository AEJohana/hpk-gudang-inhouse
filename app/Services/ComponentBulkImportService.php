<?php

namespace App\Services;

use App\Models\Component;
use App\Models\Location;
use App\Models\StockBalance;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use OpenSpout\Reader\XLSX\Reader as XLSXReader;
use OpenSpout\Reader\CSV\Reader as CSVReader;

class ComponentBulkImportService
{
    /**
     * Parse the uploaded file (XLSX or CSV) and generate an in-memory preview with row statuses.
     */
    public function parseAndPreview(string $filePath, string $extension, string $duplicateAction = 'skip'): array
    {
        $rows = [];
        $headerFound = false;
        $headerMap = [];

        $isCsv = strtolower($extension) === 'csv';
        $reader = $isCsv ? new CSVReader() : new XLSXReader();
        $reader->open($filePath);

        $validCategories = [
            'subframe' => 'subframe',
            'assy' => 'assy',
            'accessories' => 'accessories',
            'pto' => 'pto',
            'potong_chassis' => 'potong_chassis',
            'spo' => 'spo',
        ];

        $validUoms = ['PCS', 'SET', 'BATANG', 'LEMBAR', 'KG', 'LITER', 'BOX', 'METER'];

        // Preload existing locations
        $locationsByCode = Location::where('rack_code', '!=', 'HOLDING')
            ->get()
            ->keyBy(function ($item) {
                return strtoupper(trim($item->rack_code));
            });

        // Preload existing part numbers
        $existingParts = Component::pluck('id', 'part_number')
            ->mapWithKeys(function ($id, $pn) {
                return [strtoupper(trim($pn)) => $id];
            })
            ->toArray();

        $seenPartNumbersInFile = [];
        $rowCounter = 0;

        foreach ($reader->getSheetIterator() as $sheet) {
            // Only read Sheet 1 (INPUT DATA KOMPONEN or first sheet)
            $sheetName = strtoupper(trim($sheet->getName()));
            if (!$isCsv && $sheet->getIndex() > 0 && !str_contains($sheetName, 'INPUT')) {
                continue;
            }

            foreach ($sheet->getRowIterator() as $row) {
                $cells = array_map(function ($val) {
                    return is_string($val) ? trim($val) : $val;
                }, $row->toArray());

                // Skip completely empty rows
                if (empty(array_filter($cells, fn($v) => !is_null($v) && $v !== ''))) {
                    continue;
                }

                // Detect Header Row
                if (!$headerFound) {
                    $firstCell = strtoupper((string) ($cells[0] ?? ''));
                    if (str_contains($firstCell, 'KODE') || str_contains($firstCell, 'PART_NUMBER') || str_contains($firstCell, 'PART NUMBER')) {
                        $headerFound = true;
                        $headerMap = $this->mapHeaders($cells);
                    }
                    continue;
                }

                // If header already found, process data row
                $rowCounter++;
                $pn = trim((string) ($cells[$headerMap['part_number'] ?? 0] ?? ''));
                $name = trim((string) ($cells[$headerMap['name'] ?? 1] ?? ''));
                $catRaw = strtolower(trim((string) ($cells[$headerMap['category'] ?? 2] ?? '')));
                $uomRaw = strtoupper(trim((string) ($cells[$headerMap['uom'] ?? 3] ?? '')));
                $length = trim((string) ($cells[$headerMap['length_mm'] ?? 4] ?? ''));
                $width = trim((string) ($cells[$headerMap['width_or_diameter_mm'] ?? 5] ?? ''));
                $thickness = trim((string) ($cells[$headerMap['thickness_or_height_mm'] ?? 6] ?? ''));
                $spec = trim((string) ($cells[$headerMap['specification'] ?? 7] ?? ''));
                $minStockRaw = $cells[$headerMap['minimum_stock'] ?? 8] ?? null;
                $initStockRaw = $cells[$headerMap['initial_stock'] ?? 9] ?? null;
                $locCodeRaw = strtoupper(trim((string) ($cells[$headerMap['location_code'] ?? 10] ?? '')));
                $photoRaw = trim((string) ($cells[$headerMap['image_url'] ?? 11] ?? ''));

                // Normalizations
                $category = $validCategories[$catRaw] ?? null;
                $uom = in_array($uomRaw, $validUoms) ? $uomRaw : (!empty($uomRaw) ? $uomRaw : 'PCS');
                $minStock = is_numeric($minStockRaw) ? max(0, (int) $minStockRaw) : 0;
                $initStock = is_numeric($initStockRaw) ? max(0, (float) $initStockRaw) : 0;
                
                $matchedLocation = $locationsByCode->get($locCodeRaw);
                $locationId = $matchedLocation ? $matchedLocation->id : null;
                $locationText = $matchedLocation ? $matchedLocation->rack_code : ($locCodeRaw ?: '-');

                // Determine Status
                $status = 'new';
                $statusLabel = 'Baru (Siap Ditambah)';
                $errorMessage = null;

                if (empty($pn)) {
                    $status = 'error';
                    $statusLabel = 'Error';
                    $errorMessage = 'Kode Komponen wajib diisi.';
                } elseif (empty($name)) {
                    $status = 'error';
                    $statusLabel = 'Error';
                    $errorMessage = 'Nama Komponen wajib diisi.';
                } elseif (isset($seenPartNumbersInFile[strtoupper($pn)])) {
                    $status = 'error';
                    $statusLabel = 'Error';
                    $errorMessage = "Kode Part {$pn} duplikat di dalam file Excel.";
                } else {
                    $seenPartNumbersInFile[strtoupper($pn)] = true;

                    // Check DB
                    if (isset($existingParts[strtoupper($pn)])) {
                        if ($duplicateAction === 'update') {
                            $status = 'duplicate_update';
                            $statusLabel = 'Duplikat (Akan Diupdate)';
                        } else {
                            $status = 'duplicate_skip';
                            $statusLabel = 'Duplikat (Akan Dilewati)';
                        }
                    }
                }

                $rows[] = [
                    'row_number' => $rowCounter,
                    'part_number' => $pn,
                    'name' => $name,
                    'category' => $category,
                    'category_label' => $category ? ucfirst($category) : '-',
                    'uom' => $uom,
                    'length_mm' => $length ?: null,
                    'width_or_diameter_mm' => $width ?: null,
                    'thickness_or_height_mm' => $thickness ?: null,
                    'dimension_text' => $this->formatDimension($length, $width, $thickness),
                    'specification' => $spec ?: null,
                    'minimum_stock' => $minStock,
                    'initial_stock' => $initStock,
                    'location_code' => $locCodeRaw,
                    'location_id' => $locationId,
                    'location_text' => $locationText,
                    'image_url' => $photoRaw ?: null,
                    'status' => $status,
                    'status_label' => $statusLabel,
                    'error_message' => $errorMessage,
                ];
            }

            // Only process first matching sheet
            break;
        }

        $reader->close();

        // Calculate summary
        $newCount = count(array_filter($rows, fn($r) => $r['status'] === 'new'));
        $skipCount = count(array_filter($rows, fn($r) => $r['status'] === 'duplicate_skip'));
        $updateCount = count(array_filter($rows, fn($r) => $r['status'] === 'duplicate_update'));
        $errorCount = count(array_filter($rows, fn($r) => $r['status'] === 'error'));

        // Cache the parsed rows for confirmation step (valid for 2 hours)
        $cacheToken = 'comp_import_' . Str::random(32);
        Cache::put($cacheToken, [
            'rows' => $rows,
            'duplicate_action' => $duplicateAction,
        ], now()->addHours(2));

        return [
            'cache_token' => $cacheToken,
            'preview_token' => $cacheToken,
            'total_rows' => count($rows),
            'new_count' => $newCount,
            'duplicate_skip_count' => $skipCount,
            'duplicate_update_count' => $updateCount,
            'error_count' => $errorCount,
            'duplicate_action' => $duplicateAction,
            'summary' => [
                'total_rows' => count($rows),
                'valid_rows' => $newCount + ($duplicateAction === 'update' ? $updateCount : 0),
                'new_items' => $newCount,
                'duplicate_skip' => $skipCount,
                'duplicate_update' => $updateCount,
                'errors' => $errorCount,
            ],
            'rows' => $rows,
        ];
    }

    /**
     * Execute the confirmed import into MySQL database within a clean DB transaction.
     */
    public function confirmAndStore(string $cacheToken, string $duplicateAction = 'skip'): array
    {
        $cachedData = Cache::get($cacheToken);
        if (!$cachedData || empty($cachedData['rows'])) {
            throw new \RuntimeException('Sesi data pratinjau impor telah kedaluwarsa atau tidak ditemukan. Silakan upload ulang file Excel Anda.');
        }

        $rows = $cachedData['rows'];
        $insertedCount = 0;
        $updatedCount = 0;
        $skippedCount = 0;
        $photosAttached = 0;

        DB::beginTransaction();
        try {
            foreach ($rows as $row) {
                // Ignore rows with critical errors
                if ($row['status'] === 'error') {
                    continue;
                }

                $pn = $row['part_number'];

                if ($row['status'] === 'duplicate_skip') {
                    $skippedCount++;
                    continue;
                }

                // Download photo if Google Drive / Web URL provided
                $imagePath = null;
                if (!empty($row['image_url'])) {
                    $imagePath = $this->downloadPhotoIfUrl($row['image_url'], $pn);
                    if ($imagePath) {
                        $photosAttached++;
                    }
                }

                if ($row['status'] === 'duplicate_update') {
                    $component = Component::where('part_number', $pn)->first();
                    if ($component) {
                        $updateData = [
                            'name' => $row['name'],
                            'category' => $row['category'] ?: $component->category,
                            'uom' => $row['uom'] ?: $component->uom,
                            'length_mm' => $row['length_mm'] ?? $component->length_mm,
                            'width_or_diameter_mm' => $row['width_or_diameter_mm'] ?? $component->width_or_diameter_mm,
                            'thickness_or_height_mm' => $row['thickness_or_height_mm'] ?? $component->thickness_or_height_mm,
                            'specification' => $row['specification'] ?? $component->specification,
                            'minimum_stock' => $row['minimum_stock'] ?? $component->minimum_stock,
                            'default_location_id' => $row['location_id'] ?: $component->default_location_id,
                        ];

                        if ($imagePath && empty($component->image_path)) {
                            $updateData['image_path'] = $imagePath;
                        }

                        $component->update($updateData);
                        $updatedCount++;
                    }
                    continue;
                }

                // Create NEW Component
                $component = Component::create([
                    'part_number' => $pn,
                    'name' => $row['name'],
                    'category' => $row['category'],
                    'uom' => $row['uom'],
                    'length_mm' => $row['length_mm'],
                    'width_or_diameter_mm' => $row['width_or_diameter_mm'],
                    'thickness_or_height_mm' => $row['thickness_or_height_mm'],
                    'specification' => $row['specification'],
                    'minimum_stock' => $row['minimum_stock'] ?? 0,
                    'maximum_stock' => null,
                    'default_location_id' => $row['location_id'],
                    'image_path' => $imagePath,
                    'qr_code_payload' => 'HPK-PART|' . $pn,
                    'is_active' => true,
                ]);

                // Create initial stock balance if provided
                $initialStock = (float) ($row['initial_stock'] ?? 0);
                if ($initialStock > 0) {
                    $targetLocationId = $component->default_location_id;
                    if (!$targetLocationId) {
                        $holdingLoc = Location::ensureUnassignedLocationExists();
                        $targetLocationId = $holdingLoc->id;
                    }

                    StockBalance::create([
                        'component_id' => $component->id,
                        'location_id' => $targetLocationId,
                        'quantity' => $initialStock,
                        'batch_lot_number' => 'LOT-' . date('Ym') . '-INIT',
                    ]);
                }

                $insertedCount++;
            }

            DB::commit();
            Cache::forget($cacheToken);

            return [
                'success' => true,
                'total_processed' => $insertedCount + $updatedCount + $skippedCount,
                'inserted' => $insertedCount,
                'updated' => $updatedCount,
                'skipped' => $skippedCount,
                'inserted_count' => $insertedCount,
                'updated_count' => $updatedCount,
                'skipped_count' => $skippedCount,
                'photos_attached' => $photosAttached,
                'message' => "Impor selesai! Berhasil menambahkan {$insertedCount} komponen baru, memperbarui {$updatedCount} komponen, dan melewati {$skippedCount} komponen duplikat.",
            ];
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Bulk import component failed: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            throw $e;
        }
    }

    /**
     * Map Excel column headers dynamically by searching key keywords.
     */
    protected function mapHeaders(array $cells): array
    {
        $map = [
            'part_number' => 0,
            'name' => 1,
            'category' => 2,
            'uom' => 3,
            'length_mm' => 4,
            'width_or_diameter_mm' => 5,
            'thickness_or_height_mm' => 6,
            'specification' => 7,
            'minimum_stock' => 8,
            'initial_stock' => 9,
            'location_code' => 10,
            'image_url' => 11,
        ];

        foreach ($cells as $index => $text) {
            $t = strtoupper(trim((string) $text));
            if (str_contains($t, 'RAK') || str_contains($t, 'LOKASI')) {
                $map['location_code'] = $index;
            } elseif (str_contains($t, 'KODE') || str_contains($t, 'PART')) {
                $map['part_number'] = $index;
            } elseif (str_contains($t, 'NAMA')) {
                $map['name'] = $index;
            } elseif (str_contains($t, 'KATEGORI')) {
                $map['category'] = $index;
            } elseif (str_contains($t, 'SATUAN') || str_contains($t, 'UOM')) {
                $map['uom'] = $index;
            } elseif (str_contains($t, 'PANJANG')) {
                $map['length_mm'] = $index;
            } elseif (str_contains($t, 'LEBAR') || str_contains($t, 'DIAMETER')) {
                $map['width_or_diameter_mm'] = $index;
            } elseif (str_contains($t, 'TEBAL') || str_contains($t, 'TINGGI')) {
                $map['thickness_or_height_mm'] = $index;
            } elseif (str_contains($t, 'SPEK') || str_contains($t, 'SPESIFIKASI')) {
                $map['specification'] = $index;
            } elseif (str_contains($t, 'MINIMUM') || str_contains($t, 'MIN')) {
                $map['minimum_stock'] = $index;
            } elseif (str_contains($t, 'AWAL') || str_contains($t, 'SALDO')) {
                $map['initial_stock'] = $index;
            } elseif (str_contains($t, 'FOTO') || str_contains($t, 'IMAGE') || str_contains($t, 'GDRIVE')) {
                $map['image_url'] = $index;
            }
        }

        return $map;
    }

    /**
     * Helper to download Google Drive shared image or web image safely.
     */
    protected function downloadPhotoIfUrl(?string $url, string $partNumber): ?string
    {
        if (empty($url) || !filter_var($url, FILTER_VALIDATE_URL)) {
            return null;
        }

        try {
            $targetUrl = $url;

            // Detect Google Drive sharing URL
            if (preg_match('/(?:file\/d\/|id=|open\?id=)([a-zA-Z0-9_-]+)/i', $url, $matches)) {
                $fileId = $matches[1];
                // Google Drive high-resolution direct image stream endpoint
                $targetUrl = "https://lh3.googleusercontent.com/d/{$fileId}";
            }

            $response = Http::timeout(6)->withHeaders([
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) HPK-Gudang/1.0',
            ])->get($targetUrl);

            if ($response->successful() && strlen($response->body()) > 100) {
                $dir = public_path('uploads/components');
                if (!file_exists($dir)) {
                    mkdir($dir, 0777, true);
                }

                $ext = 'jpg';
                $contentType = strtolower($response->header('Content-Type') ?? '');
                if (str_contains($contentType, 'png')) $ext = 'png';
                elseif (str_contains($contentType, 'webp')) $ext = 'webp';

                $filename = 'comp_import_' . time() . '_' . Str::slug($partNumber) . '.' . $ext;
                file_put_contents($dir . '/' . $filename, $response->body());

                return 'uploads/components/' . $filename;
            }
        } catch (\Throwable $e) {
            Log::warning("Failed to download image from URL [{$url}] for part [{$partNumber}]: " . $e->getMessage());
        }

        return null;
    }

    /**
     * Format dimensions cleanly.
     */
    protected function formatDimension(?string $l, ?string $w, ?string $t): string
    {
        $parts = [];
        if (!empty($l)) $parts[] = "P: {$l}mm";
        if (!empty($w)) $parts[] = "L/D: {$w}mm";
        if (!empty($t)) $parts[] = "T: {$t}mm";
        return !empty($parts) ? implode(' x ', $parts) : '-';
    }
}
