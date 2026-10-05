<?php

namespace App\Http\Controllers;

use App\Models\Component;
use App\Models\Location;
use App\Models\StockBalance;
use App\Models\Transaction;
use App\Models\TransactionItem;
use App\Models\Warehouse;
use App\Models\WorkStation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class WorkStationSupplyController extends Controller
{
    /**
     * Show form to dispatch / supply components from warehouse to a work station.
     */
    public function create(Request $request)
    {
        WorkStation::ensureDefaultWorkStationsExist();
        Warehouse::ensureDefaultWarehouseExists();
        Location::ensureDefaultLocationExists();

        $workStations = WorkStation::where('status', 'active')->orderBy('id')->get();
        $components = Component::where('is_active', true)->with(['stockBalances.location'])->orderBy('name')->get();
        $locations = Location::orderBy('zone_code')->orderBy('rack_number')->get();

        $selectedWorkStationId = $request->get('work_station_id');
        $selectedComponentId = $request->get('component_id');

        return view('transactions.work_station_supply', compact(
            'workStations',
            'components',
            'locations',
            'selectedWorkStationId',
            'selectedComponentId'
        ));
    }

    /**
     * Store supply transaction and deduct stock from warehouse racks.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'work_station_id' => 'required|exists:work_stations,id',
            'spk_number' => 'required|string|max:100',
            'recipient_name' => 'required|string|max:100',
            'transaction_date' => 'required|date',
            'notes' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.component_id' => 'required|exists:components,id',
            'items.*.from_location_id' => 'required|exists:locations,id',
            'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.notes' => 'nullable|string',
        ]);

        $workStation = WorkStation::findOrFail($validated['work_station_id']);

        // Check stock availability before proceeding
        foreach ($validated['items'] as $item) {
            $balance = StockBalance::where('component_id', $item['component_id'])
                ->where('location_id', $item['from_location_id'])
                ->first();

            $availableQty = $balance ? (float) $balance->quantity : 0;
            if ($availableQty < (float) $item['quantity']) {
                $comp = Component::find($item['component_id']);
                $loc = Location::find($item['from_location_id']);
                return back()->withInput()->withErrors([
                    'stock' => "Stok komponen {$comp->name} pada {$loc->full_location_code} tidak mencukupi (Tersedia: {$availableQty}, Diminta: {$item['quantity']}).",
                ]);
            }
        }

        return DB::transaction(function () use ($validated, $workStation) {
            $txNumber = 'TRX-SUPPLY-' . date('Ym') . '-' . strtoupper(Str::random(4));

            $transaction = Transaction::create([
                'transaction_number' => $txNumber,
                'type' => 'outbound',
                'spk_number' => $validated['spk_number'],
                'work_station_id' => $workStation->id,
                'recipient_name' => $validated['recipient_name'],
                'reference_document' => "SUPPLY-" . $workStation->code,
                'transaction_date' => $validated['transaction_date'],
                'notes' => "Supply komponen ke {$workStation->name} (Penerima: {$validated['recipient_name']}). " . ($validated['notes'] ?? ''),
                'user_id' => auth()->id(),
                'status' => 'completed',
            ]);

            foreach ($validated['items'] as $item) {
                $componentId = $item['component_id'];
                $fromLocId = $item['from_location_id'];
                $qty = (float) $item['quantity'];

                TransactionItem::create([
                    'transaction_id' => $transaction->id,
                    'component_id' => $componentId,
                    'from_location_id' => $fromLocId,
                    'to_location_id' => null,
                    'quantity' => $qty,
                    'notes' => "Tujuan: {$workStation->name} | " . ($item['notes'] ?? ''),
                ]);

                // Deduct from origin StockBalance
                $balance = StockBalance::where('component_id', $componentId)
                    ->where('location_id', $fromLocId)
                    ->first();

                if ($balance) {
                    $balance->quantity = max(0, $balance->quantity - $qty);
                    $balance->save();
                }
            }

            return redirect()->route('transactions.show', $transaction)
                ->with('success', "Supply komponen ke {$workStation->name} (SPK: {$validated['spk_number']}) berhasil diproses! Stok gudang telah dipotong.");
        });
    }
}
