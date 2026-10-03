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

        if ($request->filled('low_stock')) {
            $components = $query->get()->filter(function ($c) {
                return $c->is_low_stock;
            });
        } else {
            $components = $query->latest()->paginate(15)->withQueryString();
        }

        $categories = [
            'hydraulic' => 'Komponen Hidrolik & Presisi',
            'raw_material' => 'Raw Material Baja',
            'fastener' => 'Hardware & Fastener',
            'accessories' => 'Aksesoris Karoseri',
            'electrical' => 'Electrical & Lighting',
            'chemical_paint' => 'Chemical & Cat',
        ];

        return view('master_components.index', compact('components', 'categories'));
    }

    public function create()
    {
        $locations = Location::orderBy('zone_code')->orderBy('rack_number')->get();
        return view('master_components.create', compact('locations'));
    }

    public function store(Request $request)
    {

        $validated = $request->validate([
            'part_number' => 'nullable|string|max:50|unique:components,part_number',
            'name' => 'required|string|max:255',
            'category' => 'required|string',
            'uom' => 'required|string|max:20',
            'specification' => 'nullable|string',
            'minimum_stock' => 'required|integer|min:0',
            'maximum_stock' => 'required|integer|min:1',
            'default_location_id' => 'required|exists:locations,id',
            'initial_stock' => 'nullable|numeric|min:0',
            'image' => 'nullable|image|max:5120', // max 5MB
            'camera_snapshot' => 'nullable|string', // base64 string from camera
        ]);

        // Auto-generate part number if not provided
        if (empty($validated['part_number'])) {
            $prefix = match($validated['category']) {
                'hydraulic' => 'HYD',
                'raw_material' => 'RAW',
                'fastener' => 'FST',
                'accessories' => 'ACC',
                'electrical' => 'ELC',
                'chemical_paint' => 'CHM',
                default => 'PRT',
            };
            $randomNum = rand(100, 999);
            $validated['part_number'] = "{$prefix}-" . strtoupper(Str::random(3)) . "-{$randomNum}";
        }

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
            'specification' => $validated['specification'],
            'minimum_stock' => $validated['minimum_stock'],
            'maximum_stock' => $validated['maximum_stock'],
            'default_location_id' => $validated['default_location_id'],
            'image_path' => $imagePath,
            'qr_code_payload' => 'HPK-PART|' . $validated['part_number'],
            'is_active' => true,
        ]);

        // Create initial stock balance if provided
        $initialStock = (float) ($request->initial_stock ?? 0);
        StockBalance::create([
            'component_id' => $component->id,
            'location_id' => $validated['default_location_id'],
            'quantity' => $initialStock,
            'batch_lot_number' => 'LOT-' . date('Ym') . '-INIT',
        ]);

        return redirect()->route('components.show', $component)->with('success', "Komponen {$component->name} ({$component->part_number}) berhasil ditambahkan!");
    }

    public function show(Component $component)
    {
        $component->load(['defaultLocation', 'stockBalances.location', 'ecrs.requestedBy']);
        return view('master_components.show', compact('component'));
    }

    public function edit(Component $component)
    {
        $locations = Location::orderBy('zone_code')->orderBy('rack_number')->get();
        return view('master_components.edit', compact('component', 'locations'));
    }

    public function update(Request $request, Component $component)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'category' => 'required|string',
            'uom' => 'required|string|max:20',
            'specification' => 'nullable|string',
            'minimum_stock' => 'required|integer|min:0',
            'maximum_stock' => 'required|integer|min:1',
            'default_location_id' => 'required|exists:locations,id',
            'image' => 'nullable|image|max:5120',
            'camera_snapshot' => 'nullable|string',
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

        $component->update([
            'name' => $validated['name'],
            'category' => $validated['category'],
            'uom' => $validated['uom'],
            'specification' => $validated['specification'],
            'minimum_stock' => $validated['minimum_stock'],
            'maximum_stock' => $validated['maximum_stock'],
            'default_location_id' => $validated['default_location_id'],
            'image_path' => $imagePath,
        ]);

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
}
