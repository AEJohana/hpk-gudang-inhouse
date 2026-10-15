<?php

namespace App\Http\Controllers;

use App\Models\Component;
use App\Models\Location;
use App\Models\StockBalance;
use App\Models\Transaction;
use App\Models\TransactionItem;
use App\Models\Warehouse;
use App\Models\WorkRequest;
use App\Models\WorkRequestLog;
use App\Models\WorkStation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class WorkStationSupplyController extends Controller
{
    /**
     * Material Handling Command Center (4 Tab Logistik Lapangan).
     */
    public function index(Request $request)
    {
        // 1. Tugas Pickup Mesin: WR yang selesai proses mesin dan menunggu diambil MH
        $tasksPickup = WorkRequest::with(['component', 'steps.machine', 'requestedBy'])
            ->where(function ($q) {
                $q->where('status', 'like', '%(Out)%');
            })
            ->latest('updated_at')
            ->get();

        // 2. Transit Antar Mesin: WR yang sedang diangkut MH menuju mesin/stasiun berikutnya
        $tasksTransit = WorkRequest::with(['component', 'steps.machine', 'requestedBy'])
            ->where('status', 'like', '%MH Pickup%')
            ->latest('updated_at')
            ->get();

        // 3. Siap Distribusi ke Pengaju / Gudang: WR yang siap diantar atau sedang menuju pengaju
        $tasksDistribution = WorkRequest::with(['component', 'targetLocation', 'requestedBy'])
            ->where(function ($q) {
                $q->where('status', 'like', '%Siap Diambil%')
                  ->orWhere('status', 'like', '%Menuju Pengaju%')
                  ->orWhere('status', 'ready_for_warehouse');
            })
            ->latest('updated_at')
            ->get();

        // 4. Riwayat Serah Terima & Selesai
        $tasksHistory = WorkRequest::with(['component', 'targetLocation', 'receivedBy'])
            ->where(function ($q) {
                $q->where('status', 'like', '%Sudah Diambil%')
                  ->orWhere('status', 'received')
                  ->orWhere('is_final', true);
            })
            ->latest('received_at')
            ->latest('updated_at')
            ->take(20)
            ->get();

        // Logs aktivitas MH terbaru
        $recentLogs = WorkRequestLog::with(['workRequest.component'])
            ->whereIn('action_type', ['process', 'defect', 'final_receive'])
            ->latest('id')
            ->take(15)
            ->get();

        $pickupCount = $tasksPickup->count();
        $transitCount = $tasksTransit->count();
        $distributionCount = $tasksDistribution->count();
        $historyCount = $tasksHistory->count();

        return view('work_station_supplies.index', compact(
            'tasksPickup',
            'tasksTransit',
            'tasksDistribution',
            'tasksHistory',
            'recentLogs',
            'pickupCount',
            'transitCount',
            'distributionCount',
            'historyCount'
        ));
    }

    /**
     * API Scanner Lookup: Cari Work Request berdasarkan scan barcode / QR code.
     */
    public function scanLookup(Request $request)
    {
        $code = trim($request->get('code', ''));
        if (empty($code)) {
            return response()->json(['success' => false, 'message' => 'Kode scan kosong.'], 400);
        }

        // Cari berdasarkan wri_number persis, like wri_number, atau part_number komponen
        $wr = WorkRequest::with(['component', 'targetLocation', 'steps.machine'])
            ->where('wri_number', $code)
            ->orWhere('wri_number', 'like', "%{$code}%")
            ->orWhereHas('component', function ($q) use ($code) {
                $q->where('part_number', $code)
                  ->orWhere('name', 'like', "%{$code}%");
            })
            ->latest('id')
            ->first();

        if (!$wr) {
            return response()->json(['success' => false, 'message' => "Tiket WRI atau Komponen untuk kode '{$code}' tidak ditemukan."], 404);
        }

        $nextStatus = $wr->getNextStatusLabel();

        return response()->json([
            'success' => true,
            'work_request' => [
                'id' => $wr->id,
                'wri_number' => $wr->wri_number,
                'component_name' => $wr->component->name,
                'part_number' => $wr->component->part_number,
                'uom' => $wr->component->uom,
                'quantity_requested' => (float) $wr->quantity_requested,
                'quantity_received' => (float) $wr->quantity_received,
                'quantity_defect' => (float) $wr->quantity_defect,
                'status' => $wr->status,
                'next_status' => $nextStatus,
                'routing_text' => $wr->routing_text,
                'target_location' => $wr->target_specific_location_code,
                'spk_reference' => $wr->spk_reference ?? '-',
                'show_url' => route('work-requests.show', $wr),
            ]
        ]);
    }

    /**
     * Fast Quick Action dari Material Handling Command Center
     */
    public function quickAction(Request $request)
    {
        $validated = $request->validate([
            'work_request_id' => 'required|exists:work_requests,id',
            'action_type' => 'required|in:process,defect,quick_advance',
            'process_qty' => 'nullable|numeric|min:0.01',
            'defect_qty' => 'nullable|numeric|min:0.01',
            'defect_reason' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
        ]);

        $wr = WorkRequest::findOrFail($validated['work_request_id']);

        // Delegasikan ke WorkRequestController processAction
        $wrController = app(WorkRequestController::class);
        return $wrController->processAction($request, $wr);
    }

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
