<?php

namespace App\Http\Controllers;

use App\Models\Component;
use App\Models\Location;
use App\Models\StockBalance;
use Illuminate\Http\Request;
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
            $components = $query->get()->filter(function ($c) {
                return $c->is_low_stock;
            });
        } else {
            $components = $query->latest()->paginate(15)->withQueryString();
        }

        $unallocatedCount = Component::whereNull('default_location_id')->count();
        $uncategorizedCount = Component::whereNull('category')->count();

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
            'image' => 'nullable|image|max:5120', // max 5MB
            'camera_snapshot' => 'nullable|string', // base64 string from camera
        ], [
            'part_number.required' => 'Kode Komponen wajib diisi sesuai pengesahan Engineering HPK.',
            'part_number.unique' => 'Kode Komponen ini sudah terdaftar di sistem.',
            'name.required' => 'Nama lengkap komponen wajib diisi sesuai standar Engineering.',
        ]);

        // Handle image upload or camera snapshot
        $imagePath = null;
        if ($request->hasFile('image')) {
            $file = $request->file('image');
            $fileName = 'comp_' . time() . '_' . Str::slug($validated['part_number']) . '.' . $file->getClientOriginalExtension();
            $file->move(public_path('uploads/components'), $fileName);
            $imagePath = 'uploads/components/' . $fileName;
        } elseif (!empty($request->camera_snapshot)) {
            // Process base64 from live camera snapshot
            $image_parts = explode(";base64,", $request->camera_snapshot);
            if (count($image_parts) === 2) {
                $image_base64 = base64_decode($image_parts[1]);
                $fileName = 'comp_cam_' . time() . '_' . Str::slug($validated['part_number']) . '.jpg';
                if (!file_exists(public_path('uploads/components'))) {
                    mkdir(public_path('uploads/components'), 0777, true);
                }
                file_put_contents(public_path('uploads/components/' . $fileName), $image_base64);
                $imagePath = 'uploads/components/' . $fileName;
            }
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
            'image' => 'nullable|image|max:5120',
            'camera_snapshot' => 'nullable|string',
        ], [
            'name.required' => 'Nama lengkap komponen wajib diisi sesuai standar Engineering.',
        ]);

        $imagePath = $component->image_path;

        if ($request->hasFile('image')) {
            $file = $request->file('image');
            $fileName = 'comp_' . time() . '_' . Str::slug($component->part_number) . '.' . $file->getClientOriginalExtension();
            $file->move(public_path('uploads/components'), $fileName);
            $imagePath = 'uploads/components/' . $fileName;
        } elseif (!empty($request->camera_snapshot)) {
            $image_parts = explode(";base64,", $request->camera_snapshot);
            if (count($image_parts) === 2) {
                $image_base64 = base64_decode($image_parts[1]);
                $fileName = 'comp_cam_' . time() . '_' . Str::slug($component->part_number) . '.jpg';
                if (!file_exists(public_path('uploads/components'))) {
                    mkdir(public_path('uploads/components'), 0777, true);
                }
                file_put_contents(public_path('uploads/components/' . $fileName), $image_base64);
                $imagePath = 'uploads/components/' . $fileName;
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

        return redirect()->route('components.show', $component)->with('success', 'Data komponen berhasil diperbarui!');
    }

    public function printQrLabel(Component $component)
    {
        return view('master_components.qr-label', compact('component'));
    }

    /**
     * Search API for Camera Scanner, Barcode Gun, and Autocomplete Input
     */
    public function searchApi(Request $request)
    {
        $code = trim($request->get('q', ''));
        
        // Strip common prefix if scanned as full payload
        $cleanCode = str_replace('HPK-PART|', '', $code);

        $component = Component::with(['defaultLocation', 'stockBalances.location'])
            ->where('part_number', $cleanCode)
            ->orWhere('part_number', $code)
            ->orWhere('name', 'like', "%{$code}%")
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
            'image' => 'nullable|image|max:5120',
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
        if ($request->hasFile('image')) {
            $file = $request->file('image');
            $fileName = 'comp_' . time() . '_' . Str::slug($partNumber) . '.' . $file->getClientOriginalExtension();
            if (!file_exists(public_path('uploads/components'))) {
                mkdir(public_path('uploads/components'), 0777, true);
            }
            $file->move(public_path('uploads/components'), $fileName);
            $imagePath = 'uploads/components/' . $fileName;
        } elseif (!empty($request->camera_snapshot)) {
            $image_parts = explode(";base64,", $request->camera_snapshot);
            if (count($image_parts) === 2) {
                $image_base64 = base64_decode($image_parts[1]);
                $fileName = 'comp_cam_' . time() . '_' . Str::slug($partNumber) . '.jpg';
                if (!file_exists(public_path('uploads/components'))) {
                    mkdir(public_path('uploads/components'), 0777, true);
                }
                file_put_contents(public_path('uploads/components/' . $fileName), $image_base64);
                $imagePath = 'uploads/components/' . $fileName;
            }
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
