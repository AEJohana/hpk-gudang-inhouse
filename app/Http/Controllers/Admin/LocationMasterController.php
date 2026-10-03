<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class LocationMasterController extends Controller
{
    public function index(Request $request)
    {
        $warehouses = \App\Models\Warehouse::with(['zones' => function($q) {
            $q->withCount('locations');
        }])->get();

        $query = \App\Models\Location::with(['warehouse', 'zone']);

        if ($request->filled('warehouse_id')) {
            $query->where('warehouse_id', $request->warehouse_id);
        }

        if ($request->filled('zone_id')) {
            $query->where('zone_id', $request->zone_id);
        }

        $locations = $query->orderBy('zone_code')->orderBy('aisle')->orderBy('rack_number')->orderBy('bin_level')->paginate(50)->withQueryString();

        return view('admin.locations.index', compact('warehouses', 'locations'));
    }
}
