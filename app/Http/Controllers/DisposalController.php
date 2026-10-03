<?php

namespace App\Http\Controllers;

use App\Models\Component;
use App\Models\Disposal;
use App\Models\DisposalItem;
use App\Models\Location;
use App\Models\StockBalance;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class DisposalController extends Controller
{
    public function index(Request $request)
    {
        $query = Disposal::with(['requestedBy', 'approvedBy', 'items.component']);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('type')) {
            $query->where('disposal_type', $request->type);
        }

        $disposals = $query->latest()->paginate(15)->withQueryString();

        return view('disposals.index', compact('disposals'));
    }

    public function create(Request $request)
    {
        $components = Component::where('is_active', true)->orderBy('name')->get();
        $locations = Location::orderBy('zone_code')->orderBy('rack_number')->get();
        $selectedComponentId = $request->get('component_id');

        return view('disposals.create', compact('components', 'locations', 'selectedComponentId'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'disposal_type' => 'required|in:scrap_iron,damaged_part,expired_chemical,obsolete',
            'reason' => 'required|string',
            'estimated_weight_kg' => 'nullable|numeric|min:0',
            'estimated_salvage_value' => 'nullable|numeric|min:0',
            'items' => 'required|array|min:1',
            'items.*.component_id' => 'required|exists:components,id',
            'items.*.from_location_id' => 'required|exists:locations,id',
            'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.condition_description' => 'nullable|string',
            'items.*.photo' => 'nullable|image|max:5120',
        ]);

        return DB::transaction(function () use ($validated, $request) {
            $disposalNumber = 'DSP-' . date('Ym') . '-' . rand(100, 999);

            $disposal = Disposal::create([
                'disposal_number' => $disposalNumber,
                'disposal_type' => $validated['disposal_type'],
                'reason' => $validated['reason'],
                'estimated_weight_kg' => $validated['estimated_weight_kg'] ?? 0,
                'estimated_salvage_value' => $validated['estimated_salvage_value'] ?? 0,
                'status' => 'submitted',
                'requested_by' => auth()->id(),
            ]);

            foreach ($validated['items'] as $index => $itemData) {
                $photoPath = null;
                if ($request->hasFile("items.{$index}.photo")) {
                    $file = $request->file("items.{$index}.photo");
                    $fileName = 'dsp_' . time() . '_' . $index . '.' . $file->getClientOriginalExtension();
                    $file->move(public_path('uploads/disposals'), $fileName);
                    $photoPath = 'uploads/disposals/' . $fileName;
                }

                DisposalItem::create([
                    'disposal_id' => $disposal->id,
                    'component_id' => $itemData['component_id'],
                    'from_location_id' => $itemData['from_location_id'],
                    'quantity' => $itemData['quantity'],
                    'condition_description' => $itemData['condition_description'] ?? null,
                    'proof_photo_path' => $photoPath,
                ]);
            }

            return redirect()->route('disposals.show', $disposal)
                ->with('success', "Pengajuan disposal {$disposal->disposal_number} berhasil dibuat dan menunggu persetujuan!");
        });
    }

    public function show(Disposal $disposal)
    {
        $disposal->load(['requestedBy', 'approvedBy', 'items.component', 'items.fromLocation']);
        return view('disposals.show', compact('disposal'));
    }

    public function approve(Request $request, Disposal $disposal)
    {
        $request->validate([
            'approval_notes' => 'nullable|string',
        ]);

        $disposal->update([
            'status' => 'approved_manager',
            'approved_by' => auth()->id(),
            'approval_notes' => $request->approval_notes,
        ]);

        return redirect()->route('disposals.show', $disposal)->with('success', "Pengajuan disposal {$disposal->disposal_number} telah disetujui!");
    }

    public function complete(Request $request, Disposal $disposal)
    {
        return DB::transaction(function () use ($disposal) {
            // Deduct stock from the origin locations
            foreach ($disposal->items as $item) {
                $balance = StockBalance::where('component_id', $item->component_id)
                    ->where('location_id', $item->from_location_id)
                    ->first();

                if ($balance) {
                    $balance->quantity = max(0, $balance->quantity - $item->quantity);
                    $balance->save();
                }
            }

            $disposal->update([
                'status' => 'completed',
                'completed_at' => now(),
            ]);

            return redirect()->route('disposals.show', $disposal)->with('success', "Disposal {$disposal->disposal_number} telah selesai diproses dan stok telah dipotong!");
        });
    }
}
