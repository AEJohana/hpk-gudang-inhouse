<?php

namespace App\Http\Controllers;

use App\Models\Component;
use App\Models\Location;
use App\Models\StockBalance;
use App\Services\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ComponentController extends Controller
{
    public function index(Request $request)
    {
        $query = Component::with(['defaultLocation', 'stockBalances.location']);

        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('part_number', 'like', "%{$s}%")
                  ->orWhere('name', 'like', "%{$s}%")
                  ->orWhere('specification', 'like', "%{$s}%");
            });
        }

        if ($request->filled('category')) {
            $query->where('category', $request->category);
        }

        if ($request->filled('uncategorized')) {
            $query->whereNull('category');
        }

        if ($request->filled('unallocated')) {
            $query->whereNull('default_location_id');
        }

        if ($request->filled('low_stock')) {
            $query->where('minimum_stock', '>', 0)
                ->whereRaw('(SELECT COALESCE(SUM(quantity), 0) FROM stock_balances WHERE stock_balances.component_id = components.id) <= components.minimum_stock');
        }

        $components = $query->latest()->paginate(15)->withQueryString();

        $unallocatedCount = Cache::remember('components_unallocated_count', 30, fn() => Component::whereNull('default_location_id')->count());
        $uncategorizedCount = Cache::remember('components_uncategorized_count', 30, fn() => Component::whereNull('category')->count());

        $categories = [
            'subframe' => 'Subframe',
            'assy' => 'Assy',
            'accessories' => 'Aksesoris',
            'pto' => 'PTO',
            'potong_chassis' => 'Potong Chassis',
            'spo' => 'SPO',
        ];

        return view('master_components.index', compact('components', 'categories', 'unallocatedCount', 'uncategorizedCount'));
    }

    public function create()
    {
        $locations = Location::where('rack_code', '!=', 'HOLDING')
            ->orderBy('zone_code')
            ->orderBy('rack_number')
            ->get();

        if ($locations->isEmpty()) {
            Location::ensureDefaultLocationExists();
            $locations = Location::where('rack_code', '!=', 'HOLDING')
                ->orderBy('zone_code')
                ->orderBy('rack_number')
                ->get();
        }

        return view('master_components.create', compact('locations'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'part_number' => 'required|string|max:50|unique:components,part_number',
            'name' => 'required|string|max:255',
            'category' => 'nullable|string',
            'uom' => 'required|string|max:20',
            'length_mm' => 'nullable|string|max:100',
            'width_or_diameter_mm' => 'nullable|string|max:100',
            'thickness_or_height_mm' => 'nullable|string|max:100',
            'specification' => 'nullable|string',
            'minimum_stock' => 'nullable|integer|min:0',
            'default_location_id' => 'nullable|exists:locations,id',
            'initial_stock' => 'nullable|numeric|min:0',
            'image' => 'nullable|file|mimes:jpg,jpeg,png,webp,svg,pdf|max:15360', // max 15MB gambar atau dokumen CAD PDF
            'camera_snapshot' => 'nullable|string', // base64 string from camera
        ], [
            'part_number.required' => 'Kode Komponen wajib diisi sesuai pengesahan Engineering HPK.',
            'part_number.unique' => 'Kode Komponen ini sudah terdaftar di sistem.',
            'name.required' => 'Nama lengkap komponen wajib diisi sesuai standar Engineering.',
            'image.mimes' => 'Format file harus berupa gambar (JPG, PNG, WEBP, SVG) atau dokumen CAD (PDF).',
            'image.max' => 'Ukuran file gambar teknik / foto maksimal 15MB.',
        ]);

        // Handle file upload or camera snapshot via unified storage service (D:\Ali Magang\KOMPONEN)
        $imagePath = null;
        /** @var \App\Services\ComponentPhotoStorageService $storageService */
        $storageService = app(\App\Services\ComponentPhotoStorageService::class);

        if ($request->hasFile('image')) {
            $imagePath = $storageService->storeUploadedFile($request->file('image'), $validated['part_number']);
        } elseif (!empty($request->camera_snapshot)) {
            $imagePath = $storageService->storeBase64($request->camera_snapshot, $validated['part_number']);
        }

        $component = Component::create([
            'part_number' => $validated['part_number'],
            'name' => $validated['name'],
            'category' => $validated['category'],
            'uom' => $validated['uom'],
            'length_mm' => $validated['length_mm'] ?? null,
            'width_or_diameter_mm' => $validated['width_or_diameter_mm'] ?? null,
            'thickness_or_height_mm' => $validated['thickness_or_height_mm'] ?? null,
            'specification' => $validated['specification'] ?? null,
            'minimum_stock' => $validated['minimum_stock'] ?? 0,
            'maximum_stock' => null,
            'default_location_id' => !empty($validated['default_location_id']) ? $validated['default_location_id'] : null,
            'image_path' => $imagePath,
            'qr_code_payload' => 'HPK-PART|' . $validated['part_number'],
            'is_active' => true,
        ]);

        // Create initial stock balance if provided
        $initialStock = (float) ($request->initial_stock ?? 0);
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

        ActivityLogger::component(
            'created',
            "Menambahkan master komponen baru: {$component->name} [{$component->part_number}].",
            $component,
            ['part_number' => $component->part_number, 'category' => $component->category]
        );

        return redirect()->route('components.show', $component)->with('success', "Komponen {$component->name} ({$component->part_number}) berhasil ditambahkan!");
    }

    public function show(Component $component)
    {
        $component->load(['defaultLocation', 'stockBalances.location', 'ecrs.requestedBy']);
        return view('master_components.show', [
            'component' => $component,
            'componentItem' => $component,
            'masterComponent' => $component,
        ]);
    }

    public function edit(Component $component)
    {
        $locations = Location::where('rack_code', '!=', 'HOLDING')
            ->orderBy('zone_code')
            ->orderBy('rack_number')
            ->get();

        $categories = [
            'subframe' => 'Subframe',
            'assy' => 'Assy',
            'accessories' => 'Aksesoris',
            'pto' => 'PTO',
            'potong_chassis' => 'Potong Chassis',
            'spo' => 'SPO',
        ];

        return view('master_components.edit', [
            'component' => $component,
            'componentItem' => $component,
            'masterComponent' => $component,
            'locations' => $locations,
            'categories' => $categories,
        ]);
    }

    public function update(Request $request, Component $component)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'category' => 'nullable|string',
            'uom' => 'required|string|max:20',
            'length_mm' => 'nullable|string|max:100',
            'width_or_diameter_mm' => 'nullable|string|max:100',
            'thickness_or_height_mm' => 'nullable|string|max:100',
            'specification' => 'nullable|string',
            'minimum_stock' => 'nullable|integer|min:0',
            'default_location_id' => 'nullable|exists:locations,id',
            'image' => 'nullable|file|mimes:jpg,jpeg,png,webp,svg,pdf|max:15360',
            'camera_snapshot' => 'nullable|string',
        ], [
            'name.required' => 'Nama lengkap komponen wajib diisi sesuai standar Engineering.',
            'image.mimes' => 'Format file harus berupa gambar (JPG, PNG, WEBP, SVG) atau dokumen CAD (PDF).',
            'image.max' => 'Ukuran file gambar teknik / foto maksimal 15MB.',
        ]);

        $imagePath = $component->image_path;
        /** @var \App\Services\ComponentPhotoStorageService $storageService */
        $storageService = app(\App\Services\ComponentPhotoStorageService::class);

        if ($request->hasFile('image')) {
            $imagePath = $storageService->storeUploadedFile($request->file('image'), $component->part_number);
        } elseif (!empty($request->camera_snapshot)) {
            $newPath = $storageService->storeBase64($request->camera_snapshot, $component->part_number);
            if ($newPath) {
                $imagePath = $newPath;
            }
        }

        $oldLocationId = $component->default_location_id;
        $newLocationId = !empty($validated['default_location_id']) ? $validated['default_location_id'] : null;

        $component->update([
            'name' => $validated['name'],
            'category' => $validated['category'],
            'uom' => $validated['uom'],
            'length_mm' => $validated['length_mm'] ?? null,
            'width_or_diameter_mm' => $validated['width_or_diameter_mm'] ?? null,
            'thickness_or_height_mm' => $validated['thickness_or_height_mm'] ?? null,
            'specification' => $validated['specification'] ?? null,
            'minimum_stock' => $validated['minimum_stock'] ?? 0,
            'default_location_id' => $newLocationId,
            'image_path' => $imagePath,
        ]);

        // If component was previously unassigned and now has a physical rack,
        // move any stock from HOLDING location to the newly selected rack
        if (!$oldLocationId && $newLocationId) {
            $holdingLoc = Location::where('rack_code', 'HOLDING')->first();
            if ($holdingLoc) {
                StockBalance::where('component_id', $component->id)
                    ->where('location_id', $holdingLoc->id)
                    ->update(['location_id' => $newLocationId]);
            }
        }

        ActivityLogger::component(
            'updated',
            "Memperbarui data komponen {$component->name} [{$component->part_number}].",
            $component,
            ['part_number' => $component->part_number]
        );

        return redirect()->route('components.show', $component)->with('success', 'Data komponen berhasil diperbarui!');
    }

    public function printQrLabel(Component $component)
    {
        return view('master_components.qr-label', compact('component'));
    }

    /**
     * Stream foto komponen dari folder lokal D:\ atau NAS secara aman ke browser klien (HP/Laptop)
     */
    public function photoStream(Component $component)
    {
        $filePath = $component->local_photo_file_path;

        if ($filePath && file_exists($filePath)) {
            $ext = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
            $mimeType = match($ext) {
                'png' => 'image/png',
                'webp' => 'image/webp',
                'svg' => 'image/svg+xml',
                'pdf' => 'application/pdf',
                default => 'image/jpeg',
            };

            $headers = [
                'Content-Type' => $mimeType,
                'Cache-Control' => 'public, max-age=86400',
            ];

            if ($ext === 'pdf') {
                $cleanPn = preg_replace('/[^a-zA-Z0-9_-]/', '-', $component->part_number);
                $headers['Content-Disposition'] = 'inline; filename="drawing-' . $cleanPn . '.pdf"';
            }

            $fileSize = @filesize($filePath);
            if ($fileSize) {
                $headers['Content-Length'] = $fileSize;
            }

            return response()->stream(function () use ($filePath) {
                $stream = @fopen($filePath, 'rb');
                if ($stream) {
                    fpassthru($stream);
                    fclose($stream);
                }
            }, 200, $headers);
        }

        // Fallback: jika berupa URL eksternal (Google Drive), hanya redirect jika fallback diizinkan (.env)
        $enableGdriveFallback = config('services.components.enable_gdrive_fallback', false);
        if ($enableGdriveFallback && $component->image_path && (str_starts_with($component->image_path, 'http://') || str_starts_with($component->image_path, 'https://'))) {
            return redirect()->away($component->image_url);
        }

        // Default placeholder jika file tidak ditemukan fisik (Pure Local Mode)
        $placeholder = public_path('images/placeholder_part.svg');
        if (file_exists($placeholder)) {
            return response()->file($placeholder, [
                'Content-Type' => 'image/svg+xml',
                'Cache-Control' => 'public, max-age=86400',
            ]);
        }

        return abort(404);
    }

    /**
     * Search API for Camera Scanner, Barcode Gun, and Autocomplete Input
     */
    public function searchApi(Request $request)
    {
        $code = trim($request->get('q', ''));
        
        // Strip common prefix if scanned as legacy payload
        $cleanCode = str_replace('HPK-PART|', '', $code);

        // Parse composite QR payload: "Nama Komponen ; Kode Komponen ; Kode Rak" or "Kode ; Nama ; Rak"
        $tokens = [];
        $searchCandidates = [$code, $cleanCode];
        if (str_contains($cleanCode, ';')) {
            $tokens = array_values(array_filter(array_map('trim', explode(';', $cleanCode))));
            $searchCandidates = array_unique(array_merge($searchCandidates, $tokens));
        }

        $component = Component::with(['defaultLocation', 'stockBalances.location'])
            ->whereIn('part_number', $searchCandidates)
            ->orWhere(function ($q) use ($searchCandidates) {
                foreach ($searchCandidates as $candidate) {
                    if (strlen($candidate) >= 3) {
                        $q->orWhere('name', 'like', "%{$candidate}%");
                    }
                }
            })
            ->first();

        if ($component) {
            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $component->id,
                    'part_number' => $component->part_number,
                    'name' => $component->name,
                    'category' => $component->category,
                    'category_label' => $component->category_label,
                    'uom' => $component->uom,
                    'specification' => $component->specification,
                    'total_stock' => $component->total_stock,
                    'default_location' => $component->defaultLocation ? $component->defaultLocation->full_location_code : 'Belum Ditentukan',
                    'default_location_id' => $component->default_location_id,
                    'dimension_formatted' => $component->dimension_formatted,
                    'image_url' => $component->image_url,
                    'is_pdf_drawing' => $component->is_pdf_drawing,
                    'is_low_stock' => $component->is_low_stock,
                ]
            ]);
        }

        // Try fuzzy matching list
        $list = Component::where('name', 'like', "%{$code}%")
            ->orWhere('part_number', 'like', "%{$code}%")
            ->take(8)
            ->get()
            ->map(function ($c) {
                return [
                    'id' => $c->id,
                    'part_number' => $c->part_number,
                    'name' => $c->name,
                    'category_label' => $c->category_label,
                    'uom' => $c->uom,
                    'total_stock' => $c->total_stock,
                    'image_url' => $c->image_url,
                    'is_pdf_drawing' => $c->is_pdf_drawing,
                ];
            });

        return response()->json([
            'success' => false,
            'message' => 'Komponen tidak ditemukan secara spesifik',
            'suggestions' => $list
        ]);
    }

    /**
     * Quick register a new component via AJAX (e.g. from Membuat WR modal)
     */
    public function quickStore(Request $request)
    {
        if (!auth()->user()->can('master-komponen.create') && !auth()->user()->can('work-request.create')) {
            return response()->json([
                'success' => false,
                'message' => 'Anda tidak memiliki wewenang untuk mendaftarkan komponen baru.',
            ], 403);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'part_number' => 'nullable|string|max:50|unique:components,part_number',
            'category' => 'nullable|string|max:50',
            'uom' => 'nullable|string|max:20',
            'length_mm' => 'nullable|string|max:100',
            'width_or_diameter_mm' => 'nullable|string|max:100',
            'thickness_or_height_mm' => 'nullable|string|max:100',
            'specification' => 'nullable|string',
            'image' => 'nullable|file|mimes:jpg,jpeg,png,webp,svg,pdf|max:15360',
            'camera_snapshot' => 'nullable|string',
        ]);

        $partNumber = !empty($validated['part_number']) 
            ? trim($validated['part_number']) 
            : 'NEW-' . date('Ymd') . '-' . strtoupper(Str::random(4));

        $rawCategory = !empty($validated['category']) ? strtolower(trim($validated['category'])) : 'subframe';
        $categoryModel = \App\Models\ComponentCategory::where('code', $rawCategory)
            ->orWhere('name', 'like', "%{$rawCategory}%")
            ->first();
        $categoryCode = $categoryModel?->code ?? $rawCategory;
        $categoryId = $categoryModel?->id;

        $imagePath = null;
        /** @var \App\Services\ComponentPhotoStorageService $storageService */
        $storageService = app(\App\Services\ComponentPhotoStorageService::class);

        if ($request->hasFile('image')) {
            $imagePath = $storageService->storeUploadedFile($request->file('image'), $partNumber);
        } elseif (!empty($request->camera_snapshot)) {
            $imagePath = $storageService->storeBase64($request->camera_snapshot, $partNumber);
        }

        $component = Component::create([
            'part_number' => $partNumber,
            'name' => $validated['name'],
            'category' => $categoryCode,
            'component_category_id' => $categoryId,
            'uom' => $validated['uom'] ?: 'PCS',
            'length_mm' => $validated['length_mm'] ?? null,
            'width_or_diameter_mm' => $validated['width_or_diameter_mm'] ?? null,
            'thickness_or_height_mm' => $validated['thickness_or_height_mm'] ?? null,
            'specification' => $validated['specification'] ?? 'Komponen baru didaftarkan via form Work Request',
            'minimum_stock' => 0,
            'maximum_stock' => null,
            'default_location_id' => null,
            'image_path' => $imagePath,
            'qr_code_payload' => 'HPK-PART|' . $partNumber,
            'is_active' => true,
        ]);

        ActivityLogger::component(
            'created',
            "Mendaftarkan komponen cepat via WR: {$component->name} [{$component->part_number}].",
            $component,
            ['part_number' => $component->part_number, 'category' => $component->category]
        );

        return response()->json([
            'success' => true,
            'message' => "Komponen {$component->name} ({$component->part_number}) berhasil didaftarkan!",
            'component' => [
                'id' => $component->id,
                'part_number' => $component->part_number,
                'name' => $component->name,
                'category' => $component->category,
                'uom' => $component->uom,
                'specification' => $component->specification,
                'image_url' => $component->image_url,
                'is_pdf_drawing' => $component->is_pdf_drawing,
                'length_mm' => $component->length_mm,
                'width_or_diameter_mm' => $component->width_or_diameter_mm,
                'thickness_or_height_mm' => $component->thickness_or_height_mm,
                'dimension_formatted' => $component->dimension_formatted,
                'total_stock' => 0,
                'minimum_stock' => 0,
                'rack_code' => 'Belum di Rak',
            ]
        ]);
    }
}
