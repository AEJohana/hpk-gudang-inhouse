<?php

namespace App\Http\Controllers;

use App\Models\Component;
use App\Models\CycleCount;
use App\Models\CycleCountItem;
use App\Models\Location;
use App\Models\StockBalance;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CycleCountController extends Controller
{
    public function index(Request $request)
    {
        $query = CycleCount::with(['conductedBy', 'approvedBy', 'items']);

        if ($request->filled('zone')) {
            $query->where('zone_target', $request->zone);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $cycleCounts = $query->latest()->paginate(15)->withQueryString();

        return view('cycle_counts.index', compact('cycleCounts'));
    }

    public function create()
    {
        $zones = [
            'A' => 'Zona A - Raw Material Baja',
            'B' => 'Zona B - Komponen Hidrolik & Presisi',
            'C' => 'Zona C - Hardware & Fastener',
            'D' => 'Zona D - Chemical & Cat',
            'E' => 'Zona E - Staging Perakitan Karoseri',
            'F' => 'Zona F - Karantina & Scrap Yard',
        ];

        return view('cycle_counts.create', compact('zones'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'zone_target' => 'required|in:A,B,C,D,E,F',
            'count_date' => 'required|date',
            'notes' => 'nullable|string',
        ]);

        return DB::transaction(function () use ($validated) {
            $countNumber = 'CC-' . date('Ym') . '-' . rand(100, 999);

            $cycleCount = CycleCount::create([
                'count_number' => $countNumber,
                'zone_target' => $validated['zone_target'],
                'count_date' => $validated['count_date'],
                'notes' => $validated['notes'],
                'status' => 'in_progress',
                'conducted_by' => auth()->id(),
            ]);

            // Find all locations and stock balances in this zone
            $locations = Location::where('zone_code', $validated['zone_target'])->pluck('id');
            $stockBalances = StockBalance::whereIn('location_id', $locations)->get();

            if ($stockBalances->isEmpty()) {
                // Also check if any component has default location here
                $components = Component::whereIn('default_location_id', $locations)->get();
                foreach ($components as $comp) {
                    CycleCountItem::create([
                        'cycle_count_id' => $cycleCount->id,
                        'component_id' => $comp->id,
                        'location_id' => $comp->default_location_id,
                        'system_qty' => 0,
                        'physical_qty' => 0,
                        'variance_qty' => 0,
                    ]);
                }
            } else {
                foreach ($stockBalances as $sb) {
                    CycleCountItem::create([
                        'cycle_count_id' => $cycleCount->id,
                        'component_id' => $sb->component_id,
                        'location_id' => $sb->location_id,
                        'system_qty' => $sb->quantity,
                        'physical_qty' => $sb->quantity, // default to system qty for easy verification
                        'variance_qty' => 0,
                    ]);
                }
            }

            return redirect()->route('cycle-counts.show', $cycleCount)
                ->with('success', "Lembar Cycle Count {$cycleCount->count_number} berhasil dibuat!");
        });
    }

    public function show(CycleCount $cycleCount)
    {
        $cycleCount->load(['conductedBy', 'approvedBy', 'items.component', 'items.location']);
        return view('cycle_counts.show', compact('cycleCount'));
    }

    public function submitCount(Request $request, CycleCount $cycleCount)
    {
        $validated = $request->validate([
            'items' => 'required|array',
            'items.*.physical_qty' => 'required|numeric|min:0',
            'items.*.variance_reason' => 'nullable|string',
        ]);

        foreach ($validated['items'] as $itemId => $itemData) {
            $item = CycleCountItem::where('cycle_count_id', $cycleCount->id)->where('id', $itemId)->first();
            if ($item) {
                $physicalQty = (float) $itemData['physical_qty'];
                $variance = $physicalQty - $item->system_qty;
                $item->update([
                    'physical_qty' => $physicalQty,
                    'variance_qty' => $variance,
                    'variance_reason' => $itemData['variance_reason'] ?? null,
                ]);
            }
        }

        $cycleCount->update([
            'status' => 'pending_review',
        ]);

        return redirect()->route('cycle-counts.show', $cycleCount)
            ->with('success', 'Hasil penghitungan fisik berhasil disimpan dan diajukan untuk review Supervisor!');
    }

    public function reconcile(Request $request, CycleCount $cycleCount)
    {
        return DB::transaction(function () use ($cycleCount) {
            foreach ($cycleCount->items as $item) {
                // Adjust stock balance to match physical qty
                $balance = StockBalance::firstOrCreate(
                    ['component_id' => $item->component_id, 'location_id' => $item->location_id],
                    ['quantity' => 0]
                );
                $balance->quantity = $item->physical_qty;
                $balance->save();
            }

            $cycleCount->update([
                'status' => 'reconciled',
                'approved_by' => auth()->id(),
                'reconciled_at' => now(),
            ]);

            return redirect()->route('cycle-counts.show', $cycleCount)
                ->with('success', "Rekonsiliasi Stok Opname {$cycleCount->count_number} berhasil disetujui! Saldo stok telah diperbarui.");
        });
    }
}
