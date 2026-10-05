<?php

namespace App\Http\Controllers;

use App\Models\Location;
use App\Models\Warehouse;
use App\Models\Zone;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LocationController extends Controller
{
    /**
     * Display the warehouse 2D Lego map & zone list.
     */
    public function index(Request $request)
    {
        $warehouses = Warehouse::where('is_active', true)->orderBy('id')->get();
        
        $warehouseId = $request->get('warehouse_id');
        $activeWarehouse = $warehouseId ? $warehouses->firstWhere('id', $warehouseId) : $warehouses->first();
        
        if (!$activeWarehouse) {
            $activeWarehouse = Warehouse::first();
        }

        $query = Location::with(['stockBalances.component.componentCategory', 'zone']);
        if ($activeWarehouse) {
            $query->where('warehouse_id', $activeWarehouse->id);
        }
        $locations = $query->get();

        // Group by Area/Zone
        $zones = [];
        $groupedByZone = $locations->groupBy('zone_code');

        foreach ($groupedByZone as $code => $zoneLocs) {
            $firstLoc = $zoneLocs->first();
            $zones[$code] = [
                'code' => (string) $code,
                'name' => $firstLoc->zone_name ?: ($firstLoc->zone?->name ?: "Area {$code}"),
                'description' => $firstLoc->zone?->description ?: "Area Penyimpanan Gudang {$code}",
                'theme' => match($code) {
                    '1' => 'blue',
                    '2' => 'emerald',
                    'A' => 'indigo',
                    'B' => 'emerald',
                    'C' => 'amber',
                    'D' => 'purple',
                    'E' => 'cyan',
                    'F' => 'rose',
                    default => 'blue'
                },
                'locations' => $zoneLocs,
            ];
        }

        $totalCapacity = (int) $locations->sum('max_capacity');
        $totalStored = (int) $locations->sum(fn ($l) => $l->total_stored_quantity);
        $occupancyAvg = $totalCapacity > 0 ? (int) round(($totalStored / $totalCapacity) * 100) : 0;

        $stats = [
            'total_locations' => $locations->count(),
            'total_racks' => $locations->where('is_pallet', false)->count(),
            'total_pallets' => $locations->where('is_pallet', true)->count(),
            'total_capacity' => $totalCapacity,
            'total_stored' => $totalStored,
            'occupancy_avg' => $occupancyAvg,
            'near_full_racks' => $locations->filter(fn ($l) => $l->occupancy_rate >= 80)->count(),
        ];

        $locationsJson = $locations->map(function ($loc) {
            return [
                'id' => $loc->id,
                'rack_number' => $loc->display_rack_name, // "Rak 3" or "Pallet 1"
                'raw_rack_number' => $loc->rack_number,
                'storage_type' => $loc->storage_type ?: ($loc->is_pallet ? 'pallet' : 'rack'),
                'is_pallet' => (bool) $loc->is_pallet,
                'type_label' => $loc->display_type_label,
                'rack_code' => $loc->clean_rack_code, // "R3" or "PLT1"
                'full_rack_code' => $loc->full_rack_code, // "1-R3" or "1-PLT1"
                'zone_code' => $loc->zone_code,
                'zone_name' => $loc->zone_name,
                'aisle' => $loc->aisle,
                'bin_level' => $loc->bin_level,
                'total_levels' => (int) ($loc->total_levels ?: ($loc->is_pallet ? 1 : 4)),
                'slots_per_level' => (int) ($loc->slots_per_level ?: ($loc->is_pallet ? 2 : 6)),
                'level_slots_config' => $loc->level_slots_config,
                'description' => $loc->description,
                'max_capacity' => (int) $loc->max_capacity,
                'total_quantity' => (float) $loc->total_stored_quantity,
                'occupancy_rate' => (int) $loc->occupancy_rate,
                'grid_x' => (int) ($loc->grid_x ?? 1),
                'grid_y' => (int) ($loc->grid_y ?? 1),
                'grid_w' => (int) ($loc->grid_w ?? 1),
                'grid_h' => (int) ($loc->grid_h ?? 1),
                'color' => $loc->color ?? ($loc->is_pallet ? 'amber' : 'blue'),
                'detail_url' => route('locations.show', $loc),
                'slot_matrix' => $loc->slot_matrix,
                'components_count' => $loc->stockBalances->count(),
                'components' => $loc->stockBalances->map(function ($sb) {
                    return [
                        'id' => $sb->component_id,
                        'name' => $sb->component->name ?? '-',
                        'part_number' => $sb->component->part_number ?? '-',
                        'item_code' => $sb->component->part_number ?? '-',
                        'shelf_level' => $sb->shelf_level ?: 'L1',
                        'slot_number' => $sb->slot_number ?: '01',
                        'specific_location_code' => $sb->computed_location_code,
                        'detail_location_label' => $sb->detail_location_label,
                        'quantity' => (float) $sb->quantity,
                        'uom' => $sb->component->uom ?? 'PCS',
                        'batch' => $sb->batch_lot_number ?: '-',
                        'category' => $sb->component->category_label ?? $sb->component->category ?? '-',
                        'url' => route('components.show', $sb->component_id),
                    ];
                })->values(),
            ];
        })->values();

        $activeWarehouseJson = $activeWarehouse ? [
            'id' => $activeWarehouse->id,
            'name' => $activeWarehouse->name,
            'code' => $activeWarehouse->code,
            'description' => $activeWarehouse->description,
            'grid_columns' => (int) ($activeWarehouse->grid_columns ?: 16),
            'grid_rows' => (int) ($activeWarehouse->grid_rows ?: 12),
            'width_meters' => (float) ($activeWarehouse->width_meters ?: 24.0),
            'length_meters' => (float) ($activeWarehouse->length_meters ?: 32.0),
            'total_area_sqm' => (float) $activeWarehouse->total_area_sqm,
            'dimension_label' => $activeWarehouse->dimension_label,
            'grid_label' => $activeWarehouse->grid_label,
        ] : null;

        return view('locations.index', compact('warehouses', 'activeWarehouse', 'activeWarehouseJson', 'zones', 'locations', 'locationsJson', 'stats'));
    }

    /**
     * Update physical dimensions and 2D grid resolution for a warehouse building.
     */
    public function updateWarehouseArea(Request $request, Warehouse $warehouse): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'nullable|string|max:120',
            'grid_columns' => 'required|integer|min:6|max:40',
            'grid_rows' => 'required|integer|min:4|max:30',
            'width_meters' => 'nullable|numeric|min:1|max:500',
            'length_meters' => 'nullable|numeric|min:1|max:500',
            'description' => 'nullable|string|max:255',
        ]);

        $warehouse->update([
            'name' => $validated['name'] ?? $warehouse->name,
            'grid_columns' => $validated['grid_columns'],
            'grid_rows' => $validated['grid_rows'],
            'width_meters' => $validated['width_meters'] ?? $warehouse->width_meters,
            'length_meters' => $validated['length_meters'] ?? $warehouse->length_meters,
            'description' => $validated['description'] ?? $warehouse->description,
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Ukuran dan konfigurasi luas area gedung berhasil diperbarui.',
            'warehouse' => [
                'id' => $warehouse->id,
                'name' => $warehouse->name,
                'code' => $warehouse->code,
                'description' => $warehouse->description,
                'grid_columns' => (int) $warehouse->grid_columns,
                'grid_rows' => (int) $warehouse->grid_rows,
                'width_meters' => (float) $warehouse->width_meters,
                'length_meters' => (float) $warehouse->length_meters,
                'total_area_sqm' => (float) $warehouse->total_area_sqm,
                'dimension_label' => $warehouse->dimension_label,
                'grid_label' => $warehouse->grid_label,
            ],
        ]);
    }

    /**
     * Create a new Rack or Pallet location on the 2D warehouse map.
     */
    public function createLocation(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'warehouse_id' => 'required|integer|exists:warehouses,id',
            'storage_type' => 'required|in:rack,pallet',
            'grid_x' => 'nullable|integer|min:1|max:40',
            'grid_y' => 'nullable|integer|min:1|max:30',
            'rack_number' => 'nullable|string|max:50',
            'zone_id' => 'nullable|integer|exists:zones,id',
            'zone' => 'nullable|string|max:50',
            'levels' => 'nullable|integer|min:1|max:20',
            'slots_per_level' => 'nullable|integer|min:1|max:50',
            'max_weight_kg' => 'nullable|numeric|min:0',
            'grid_w' => 'nullable|integer|min:1|max:8',
            'grid_h' => 'nullable|integer|min:1|max:6',
        ]);

        $warehouse = Warehouse::findOrFail($validated['warehouse_id']);
        $isPallet = $validated['storage_type'] === 'pallet';

        // Count existing to determine sequential naming
        $existingCount = Location::where('warehouse_id', $warehouse->id)
            ->where('storage_type', $validated['storage_type'])
            ->count();
        $nextNum = $existingCount + 1;

        $prefix = $isPallet ? 'Pallet' : 'Rak';
        $rackNumber = !empty($validated['rack_number']) ? $validated['rack_number'] : "{$prefix} {$nextNum}";
        $code = $isPallet ? "PLT{$nextNum}" : "R{$nextNum}";

        $firstZone = null;
        if (!empty($validated['zone_id'])) {
            $firstZone = Zone::find($validated['zone_id']);
        } elseif (!empty($validated['zone'])) {
            $firstZone = Zone::where('warehouse_id', $warehouse->id)
                ->where(function ($q) use ($validated) {
                    $q->where('name', $validated['zone'])->orWhere('code', $validated['zone']);
                })->first();
        }
        if (!$firstZone) {
            $firstZone = Zone::where('warehouse_id', $warehouse->id)->first();
        }

        $zoneCode = $firstZone?->code ?: '1';
        $zoneName = $firstZone?->name ?: ($validated['zone'] ?? 'Area 1');
        $gridX = $validated['grid_x'] ?? 1;
        $gridY = $validated['grid_y'] ?? 1;
        $gridW = $validated['grid_w'] ?? 1;
        $gridH = $validated['grid_h'] ?? 1;
        $levels = $validated['levels'] ?? ($isPallet ? 1 : 4);
        $slots = $validated['slots_per_level'] ?? ($isPallet ? 2 : 6);

        $location = Location::create([
            'warehouse_id' => $warehouse->id,
            'zone_id' => $firstZone?->id,
            'zone_code' => $zoneCode,
            'zone_name' => $zoneName,
            'aisle' => $isPallet ? "Pallet Area {$nextNum}" : "Lorong {$nextNum}",
            'rack_number' => $rackNumber,
            'storage_type' => $validated['storage_type'],
            'rack_code' => $code,
            'bin_level' => $isPallet ? 'Floor Pallet' : ($levels > 1 ? "Lantai 1-{$levels}" : 'Lantai 1'),
            'total_levels' => $levels,
            'slots_per_level' => $slots,
            'description' => $isPallet ? "Area Penyimpanan Lantai Pallet {$nextNum}" : "Rak Penyimpanan Komponen Karoseri {$nextNum}",
            'max_capacity' => $isPallet ? 30 : ($levels * $slots * 2),
            'grid_x' => $gridX,
            'grid_y' => $gridY,
            'grid_w' => $gridW,
            'grid_h' => $gridH,
            'color' => $isPallet ? 'amber' : 'blue',
        ]);

        return response()->json([
            'status' => 'success',
            'message' => ($isPallet ? "Pallet {$nextNum}" : "Rak {$nextNum}") . ' berhasil ditambahkan ke denah.',
            'location' => [
                'id' => $location->id,
                'rack_number' => $location->display_rack_name,
                'raw_rack_number' => $location->rack_number,
                'storage_type' => $location->storage_type,
                'is_pallet' => (bool) $location->is_pallet,
                'type_label' => $location->display_type_label,
                'display_type_label' => $location->display_type_label,
                'rack_code' => $location->clean_rack_code,
                'full_rack_code' => $location->full_rack_code,
                'zone_code' => $location->zone_code,
                'zone_name' => $location->zone_name,
                'aisle' => $location->aisle,
                'bin_level' => $location->bin_level,
                'total_levels' => (int) $location->total_levels,
                'slots_per_level' => (int) $location->slots_per_level,
                'description' => $location->description,
                'max_capacity' => (int) $location->max_capacity,
                'total_quantity' => 0,
                'occupancy_rate' => 0,
                'grid_x' => (int) $location->grid_x,
                'grid_y' => (int) $location->grid_y,
                'grid_w' => (int) $location->grid_w,
                'grid_h' => (int) $location->grid_h,
                'color' => $location->color,
                'detail_url' => route('locations.show', $location),
                'slot_matrix' => $location->slot_matrix,
                'components_count' => 0,
                'components' => [],
            ],
        ]);
    }

    /**
     * Update location shape (grid_w, grid_h) and shelf structure (total_levels, slots_per_level).
     */
    public function updateLocationConfig(Request $request, Location $location): JsonResponse
    {
        $validated = $request->validate([
            'grid_w' => 'required|integer|min:1|max:8',
            'grid_h' => 'required|integer|min:1|max:6',
            'total_levels' => 'nullable|integer|min:1|max:10',
            'slots_per_level' => 'nullable|integer|min:1|max:20',
            'level_slots_config' => 'nullable|array',
            'description' => 'nullable|string|max:255',
            'color' => 'nullable|string|max:30',
        ]);

        $updateData = [
            'grid_w' => $validated['grid_w'],
            'grid_h' => $validated['grid_h'],
        ];

        if (isset($validated['description'])) {
            $updateData['description'] = $validated['description'];
        }

        if (isset($validated['color'])) {
            $updateData['color'] = $validated['color'];
        }

        if ($request->has('level_slots_config')) {
            $updateData['level_slots_config'] = $validated['level_slots_config'];
        }

        if (isset($validated['total_levels'])) {
            $updateData['total_levels'] = $validated['total_levels'];
            if (!$location->is_pallet) {
                $updateData['bin_level'] = $validated['total_levels'] > 1 
                    ? "Lantai 1-{$validated['total_levels']}" 
                    : 'Lantai 1';
            }
        }

        if (isset($validated['slots_per_level'])) {
            $updateData['slots_per_level'] = $validated['slots_per_level'];
        }

        if (!empty($updateData['level_slots_config']) && is_array($updateData['level_slots_config'])) {
            $totalSlots = array_sum($updateData['level_slots_config']);
            $updateData['max_capacity'] = max(20, $totalSlots * 2);
        } elseif (isset($updateData['total_levels']) && isset($updateData['slots_per_level'])) {
            $updateData['max_capacity'] = $updateData['total_levels'] * $updateData['slots_per_level'] * 2;
        }

        $location->update($updateData);

        // Reload relationships
        $location->load(['stockBalances.component.componentCategory', 'zone']);

        return response()->json([
            'status' => 'success',
            'message' => "Bentuk dan konfigurasi {$location->display_rack_name} berhasil diperbarui.",
            'location' => [
                'id' => $location->id,
                'rack_number' => $location->display_rack_name,
                'raw_rack_number' => $location->rack_number,
                'storage_type' => $location->storage_type,
                'is_pallet' => (bool) $location->is_pallet,
                'type_label' => $location->display_type_label,
                'display_type_label' => $location->display_type_label,
                'rack_code' => $location->clean_rack_code,
                'full_rack_code' => $location->full_rack_code,
                'zone_code' => $location->zone_code,
                'zone_name' => $location->zone_name,
                'aisle' => $location->aisle,
                'bin_level' => $location->bin_level,
                'total_levels' => (int) $location->total_levels,
                'slots_per_level' => (int) $location->slots_per_level,
                'level_slots_config' => $location->level_slots_config,
                'description' => $location->description,
                'max_capacity' => (int) $location->max_capacity,
                'total_quantity' => (float) $location->total_stored_quantity,
                'occupancy_rate' => (int) $location->occupancy_rate,
                'grid_x' => (int) $location->grid_x,
                'grid_y' => (int) $location->grid_y,
                'grid_w' => (int) $location->grid_w,
                'grid_h' => (int) $location->grid_h,
                'color' => $location->color,
                'detail_url' => route('locations.show', $location),
                'slot_matrix' => $location->slot_matrix,
                'components_count' => $location->stockBalances->count(),
                'components' => $location->stockBalances->map(function ($sb) {
                    return [
                        'id' => $sb->component_id,
                        'name' => $sb->component->name ?? '-',
                        'part_number' => $sb->component->part_number ?? '-',
                        'item_code' => $sb->component->part_number ?? '-',
                        'shelf_level' => $sb->shelf_level ?: 'L1',
                        'slot_number' => $sb->slot_number ?: '01',
                        'specific_location_code' => $sb->computed_location_code,
                        'detail_location_label' => $sb->detail_location_label,
                        'quantity' => (float) $sb->quantity,
                        'uom' => $sb->component->uom ?? 'PCS',
                        'batch' => $sb->batch_lot_number ?: '-',
                        'category' => $sb->component->category_label ?? $sb->component->category ?? '-',
                        'url' => route('components.show', $sb->component_id),
                    ];
                })->values(),
            ],
        ]);
    }

    /**
     * Save updated 2D Lego grid coordinates from interactive drag-and-drop.
     */
    public function saveLayout(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'layout' => 'required|array',
            'layout.*.id' => 'required|integer|exists:locations,id',
            'layout.*.grid_x' => 'required|integer|min:1|max:40',
            'layout.*.grid_y' => 'required|integer|min:1|max:30',
            'layout.*.grid_w' => 'nullable|integer|min:1|max:8',
            'layout.*.grid_h' => 'nullable|integer|min:1|max:6',
        ]);

        DB::transaction(function () use ($validated) {
            foreach ($validated['layout'] as $item) {
                Location::where('id', $item['id'])->update([
                    'grid_x' => $item['grid_x'],
                    'grid_y' => $item['grid_y'],
                    'grid_w' => $item['grid_w'] ?? 1,
                    'grid_h' => $item['grid_h'] ?? 1,
                ]);
            }
        });

        return response()->json([
            'status' => 'success',
            'message' => 'Tata letak denah rak & pallet berhasil disimpan ke database.',
        ]);
    }

    /**
     * Reset 2D layout coordinates back to factory defaults.
     */
    public function resetLayout(Request $request): JsonResponse
    {
        $warehouseId = $request->get('warehouse_id');

        $defaults = [
            'Rak 1' => ['grid_x' => 2, 'grid_y' => 2, 'grid_w' => 1, 'grid_h' => 1, 'color' => 'indigo'],
            'Rak 2' => ['grid_x' => 6, 'grid_y' => 2, 'grid_w' => 1, 'grid_h' => 1, 'color' => 'indigo'],
            'Rak 3' => ['grid_x' => 4, 'grid_y' => 5, 'grid_w' => 1, 'grid_h' => 1, 'color' => 'blue'],
            'Rak 4' => ['grid_x' => 7, 'grid_y' => 5, 'grid_w' => 1, 'grid_h' => 1, 'color' => 'amber'],
            'Rak 5' => ['grid_x' => 10, 'grid_y' => 5, 'grid_w' => 1, 'grid_h' => 1, 'color' => 'emerald'],
            'Rak 6' => ['grid_x' => 14, 'grid_y' => 2, 'grid_w' => 1, 'grid_h' => 1, 'color' => 'purple'],
            'Rak 7' => ['grid_x' => 4, 'grid_y' => 11, 'grid_w' => 1, 'grid_h' => 1, 'color' => 'cyan'],
            'Rak 8' => ['grid_x' => 14, 'grid_y' => 10, 'grid_w' => 1, 'grid_h' => 1, 'color' => 'rose'],
            'Pallet 1' => ['grid_x' => 2, 'grid_y' => 8, 'grid_w' => 1, 'grid_h' => 1, 'color' => 'amber'],
            'Pallet 2' => ['grid_x' => 5, 'grid_y' => 8, 'grid_w' => 1, 'grid_h' => 1, 'color' => 'amber'],
            'Pallet 3' => ['grid_x' => 8, 'grid_y' => 8, 'grid_w' => 1, 'grid_h' => 1, 'color' => 'amber'],
        ];

        DB::transaction(function () use ($defaults) {
            foreach ($defaults as $rackNumber => $coords) {
                Location::where('rack_number', $rackNumber)->update($coords);
            }
        });

        $query = Location::select(['id', 'rack_number', 'storage_type', 'grid_x', 'grid_y', 'grid_w', 'grid_h', 'color']);
        if ($warehouseId) {
            $query->where('warehouse_id', $warehouseId);
        }
        $updatedLocations = $query->get();

        return response()->json([
            'status' => 'success',
            'message' => 'Tata letak denah rak & pallet berhasil dikembalikan ke standar rancangan pabrik.',
            'locations' => $updatedLocations,
        ]);
    }

    /**
     * Get quick drawer detail for a rack or pallet location with slots and elevation matrix.
     */
    public function quickDetail(Location $location): JsonResponse
    {
        $location->load(['stockBalances.component.componentCategory', 'zone']);

        return response()->json([
            'id' => $location->id,
            'rack_number' => $location->display_rack_name, // "Rak 3" or "Pallet 1"
            'raw_rack_number' => $location->rack_number,
            'storage_type' => $location->storage_type ?: ($location->is_pallet ? 'pallet' : 'rack'),
            'is_pallet' => (bool) $location->is_pallet,
            'type_label' => $location->display_type_label,
            'display_type_label' => $location->display_type_label,
            'rack_code' => $location->clean_rack_code, // "R3" or "PLT1"
            'full_rack_code' => $location->full_rack_code, // "1-R3" or "1-PLT1"
            'aisle' => $location->aisle,
            'bin_level' => $location->bin_level,
            'zone_code' => $location->zone_code,
            'zone_name' => $location->zone_name,
            'total_levels' => (int) ($location->total_levels ?: ($location->is_pallet ? 1 : 4)),
            'slots_per_level' => (int) ($location->slots_per_level ?: ($location->is_pallet ? 2 : 6)),
            'level_slots_config' => $location->level_slots_config,
            'description' => $location->description,
            'max_capacity' => (int) $location->max_capacity,
            'total_quantity' => (float) $location->total_stored_quantity,
            'occupancy_rate' => (int) $location->occupancy_rate,
            'color' => $location->color,
            'grid_x' => $location->grid_x,
            'grid_y' => $location->grid_y,
            'grid_w' => $location->grid_w,
            'grid_h' => $location->grid_h,
            'detail_url' => route('locations.show', $location),
            'slot_matrix' => $location->slot_matrix,
            'components' => $location->stockBalances->map(function ($sb) {
                return [
                    'id' => $sb->component_id,
                    'item_code' => $sb->component->part_number ?? '-',
                    'name' => $sb->component->name ?? '-',
                    'part_number' => $sb->component->part_number ?? '-',
                    'shelf_level' => $sb->shelf_level ?: 'L1',
                    'slot_number' => $sb->slot_number ?: '01',
                    'specific_location_code' => $sb->computed_location_code, // e.g. "1-R3-L3-05" or "1-PLT1-01"
                    'detail_location_label' => $sb->detail_location_label,
                    'quantity' => (float) $sb->quantity,
                    'uom' => $sb->component->uom ?? 'PCS',
                    'batch' => $sb->batch_lot_number ?: '-',
                    'category' => $sb->component->category_label ?? $sb->component->category ?? '-',
                    'status' => $sb->component->is_active ? 'active' : 'inactive',
                    'image_url' => $sb->component->image_url ?? null,
                    'url' => route('components.show', $sb->component_id),
                    'pick_url' => route('transactions.create', ['component_id' => $sb->component_id]),
                ];
            })->values(),
        ]);
    }

    /**
     * Show single location page.
     */
    public function show(Location $location)
    {
        $location->load(['stockBalances.component.componentCategory', 'zone']);
        return view('locations.show', compact('location'));
    }
}
