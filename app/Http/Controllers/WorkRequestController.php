<?php

namespace App\Http\Controllers;

use App\Models\Component;
use App\Models\Location;
use App\Models\Machine;
use App\Models\StockBalance;
use App\Models\Transaction;
use App\Models\TransactionItem;
use App\Models\Warehouse;
use App\Models\WorkRequest;
use App\Models\WorkRequestStep;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class WorkRequestController extends Controller
{
    /**
     * Display a listing of Work Requests Inhouse.
     */
    public function index(Request $request)
    {
        $query = WorkRequest::with(['component', 'requestedBy', 'targetLocation', 'steps.machine']);

        if ($request->filled('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('wri_number', 'like', "%{$s}%")
                  ->orWhere('spk_reference', 'like', "%{$s}%")
                  ->orWhereHas('component', function ($cq) use ($s) {
                      $cq->where('name', 'like', "%{$s}%")
                         ->orWhere('part_number', 'like', "%{$s}%");
                  });
            });
        }

        $workRequests = $query->latest('id')->paginate(12)->withQueryString();

        // Summary Statistics
        $totalWri = WorkRequest::count();
        $inProductionCount = WorkRequest::where('status', 'in_production')->count();
        $readyForWarehouseCount = WorkRequest::where('status', 'ready_for_warehouse')->count();
        $receivedCount = WorkRequest::where('status', 'received')->count();

        // Active Machines
        $activeMachines = Machine::withCount(['workRequestSteps as active_steps_count' => function ($q) {
            $q->where('status', 'in_progress');
        }])->get();

        return view('work_requests.index', compact(
            'workRequests',
            'totalWri',
            'inProductionCount',
            'readyForWarehouseCount',
            'receivedCount',
            'activeMachines'
        ));
    }

    /**
     * Show form to create a new Work Request Inhouse.
     */
    public function create()
    {
        $components = Component::where('is_active', true)->orderBy('name')->get();
        $machines = Machine::orderBy('id')->get();
        $locations = Location::orderBy('zone_code')->orderBy('rack_number')->get();
        $warehouses = Warehouse::all();

        return view('work_requests.create', compact('components', 'machines', 'locations', 'warehouses'));
    }

    /**
     * Store a newly created Work Request Inhouse.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'component_id' => 'required|exists:components,id',
            'quantity_requested' => 'required|numeric|min:0.1',
            'target_warehouse_id' => 'nullable|exists:warehouses,id',
            'target_location_id' => 'nullable|exists:locations,id',
            'target_shelf_level' => 'nullable|string|max:10',
            'target_slot_number' => 'nullable|string|max:10',
            'priority' => 'required|in:normal,high,urgent_line_stop',
            'due_date' => 'nullable|date',
            'spk_reference' => 'nullable|string|max:100',
            'notes' => 'nullable|string',
            'steps' => 'required|array|min:1',
            'steps.*.machine_id' => 'required|exists:machines,id',
            'steps.*.process_name' => 'required|string|max:100',
            'steps.*.notes' => 'nullable|string',
        ]);

        return DB::transaction(function () use ($validated) {
            // Generate WRI Number: WRI-YYYYMM-XXXX
            $prefix = 'WRI-' . date('Ym') . '-';
            $latestWri = WorkRequest::where('wri_number', 'like', "{$prefix}%")
                ->orderBy('id', 'desc')
                ->first();

            $nextNum = 1;
            if ($latestWri) {
                $lastSeq = (int) substr($latestWri->wri_number, -4);
                $nextNum = $lastSeq + 1;
            }
            $wriNumber = $prefix . str_pad($nextNum, 4, '0', STR_PAD_LEFT);

            // Auto-fallback location to component default if not provided
            $component = Component::find($validated['component_id']);
            $targetLocationId = $validated['target_location_id'] ?: ($component->default_location_id ?? null);
            $targetShelf = $validated['target_shelf_level'] ?: ($component->default_shelf_level ?? 'L1');
            $targetSlot = $validated['target_slot_number'] ?: ($component->default_slot_number ?? '01');

            $workRequest = WorkRequest::create([
                'wri_number' => $wriNumber,
                'component_id' => $component->id,
                'requested_by_user_id' => auth()->id(),
                'target_warehouse_id' => $validated['target_warehouse_id'] ?? null,
                'target_location_id' => $targetLocationId,
                'target_shelf_level' => $targetShelf,
                'target_slot_number' => $targetSlot,
                'quantity_requested' => $validated['quantity_requested'],
                'quantity_produced' => 0,
                'quantity_received' => 0,
                'priority' => $validated['priority'],
                'status' => 'in_production', // directly active for machine shop
                'due_date' => $validated['due_date'] ?? null,
                'spk_reference' => $validated['spk_reference'] ?? null,
                'notes' => $validated['notes'] ?? null,
            ]);

            // Create machine routing steps
            foreach ($validated['steps'] as $idx => $stepData) {
                $stepNumber = $idx + 1;
                $isFirst = ($stepNumber === 1);

                WorkRequestStep::create([
                    'work_request_id' => $workRequest->id,
                    'step_number' => $stepNumber,
                    'machine_id' => $stepData['machine_id'],
                    'process_name' => $stepData['process_name'],
                    'status' => $isFirst ? 'in_progress' : 'pending',
                    'started_at' => $isFirst ? now() : null,
                    'notes' => $stepData['notes'] ?? null,
                ]);
            }

            return redirect()->route('work-requests.show', $workRequest)
                ->with('success', "Work Request {$workRequest->wri_number} berhasil diterbitkan ke Machine Center!");
        });
    }

    /**
     * Display a specific Work Request detail.
     */
    public function show(WorkRequest $workRequest)
    {
        $workRequest->load([
            'component.defaultLocation',
            'requestedBy',
            'receivedBy',
            'targetWarehouse',
            'targetLocation',
            'steps.machine',
            'transactions.items',
        ]);

        $locations = Location::orderBy('zone_code')->orderBy('rack_number')->get();

        return view('work_requests.show', compact('workRequest', 'locations'));
    }

    /**
     * Advance / update a machine step status.
     */
    public function advanceStep(Request $request, WorkRequest $workRequest, WorkRequestStep $step)
    {
        $validated = $request->validate([
            'action' => 'required|in:start,complete,skip',
            'operator_name' => 'nullable|string|max:100',
            'notes' => 'nullable|string',
        ]);

        $operator = $validated['operator_name'] ?: auth()->user()->name;

        DB::transaction(function () use ($workRequest, $step, $validated, $operator) {
            if ($validated['action'] === 'start') {
                $step->update([
                    'status' => 'in_progress',
                    'started_at' => now(),
                    'operator_name' => $operator,
                ]);
                $workRequest->update(['status' => 'in_production']);
            } elseif ($validated['action'] === 'complete') {
                $step->update([
                    'status' => 'completed',
                    'completed_at' => now(),
                    'operator_name' => $operator,
                    'notes' => $validated['notes'] ?: $step->notes,
                ]);

                // Check for next pending step to automatically initiate
                $nextStep = WorkRequestStep::where('work_request_id', $workRequest->id)
                    ->where('step_number', '>', $step->step_number)
                    ->orderBy('step_number', 'asc')
                    ->first();

                if ($nextStep && $nextStep->status === 'pending') {
                    $nextStep->update([
                        'status' => 'in_progress',
                        'started_at' => now(),
                    ]);
                }

                // Check if all steps are now completed
                $remainingSteps = WorkRequestStep::where('work_request_id', $workRequest->id)
                    ->whereIn('status', ['pending', 'in_progress'])
                    ->count();

                if ($remainingSteps === 0) {
                    $workRequest->update([
                        'status' => 'ready_for_warehouse',
                        'quantity_produced' => $workRequest->quantity_requested,
                    ]);
                }
            } elseif ($validated['action'] === 'skip') {
                $step->update([
                    'status' => 'skipped',
                    'completed_at' => now(),
                    'operator_name' => $operator,
                    'notes' => 'Dilewati: ' . ($validated['notes'] ?? ''),
                ]);
            }
        });

        return redirect()->route('work-requests.show', $workRequest)
            ->with('success', "Proses mesin {$step->process_name} berhasil diperbarui.");
    }

    /**
     * Receive goods into warehouse (Putaway) and auto update stock.
     */
    public function receive(Request $request, WorkRequest $workRequest)
    {
        $validated = $request->validate([
            'quantity_received' => 'required|numeric|min:0.01',
            'target_location_id' => 'required|exists:locations,id',
            'target_shelf_level' => 'nullable|string|max:10',
            'target_slot_number' => 'nullable|string|max:10',
            'batch_lot_number' => 'nullable|string|max:50',
            'notes' => 'nullable|string',
        ]);

        return DB::transaction(function () use ($workRequest, $validated) {
            $qty = (float) $validated['quantity_received'];
            $locationId = (int) $validated['target_location_id'];
            $location = Location::findOrFail($locationId);

            $shelfLevel = $validated['target_shelf_level'] ?: ($workRequest->target_shelf_level ?: 'L1');
            $slotNumber = $validated['target_slot_number'] 
                ? str_pad(preg_replace('/[^0-9]/', '', $validated['target_slot_number']), 2, '0', STR_PAD_LEFT)
                : ($workRequest->target_slot_number ?: '01');

            $specificCode = "{$location->zone_code}-{$location->clean_rack_code}-{$shelfLevel}-{$slotNumber}";
            $batchLot = !empty($validated['batch_lot_number']) 
                ? $validated['batch_lot_number'] 
                : 'LOT-' . date('Ym') . '-WRI' . $workRequest->id;

            // 1. Add Stock to StockBalance
            $balance = StockBalance::firstOrCreate(
                ['component_id' => $workRequest->component_id, 'location_id' => $locationId],
                ['quantity' => 0]
            );

            $balance->quantity += $qty;
            $balance->shelf_level = $shelfLevel;
            $balance->slot_number = $slotNumber;
            $balance->specific_location_code = $specificCode;
            $balance->batch_lot_number = $batchLot;
            $balance->save();

            // 2. Create Inbound Transaction
            $txNumber = 'TRX-IN-WRI-' . date('Ym') . '-' . strtoupper(Str::random(4));
            $transaction = Transaction::create([
                'transaction_number' => $txNumber,
                'type' => 'inbound',
                'spk_number' => $workRequest->spk_reference,
                'reference_document' => $workRequest->wri_number,
                'work_request_id' => $workRequest->id,
                'transaction_date' => now()->toDateString(),
                'notes' => "Penerimaan Inhouse dari Machine Center ({$workRequest->wri_number}) ke lokasi {$specificCode}. " . ($validated['notes'] ?? ''),
                'user_id' => auth()->id(),
                'status' => 'completed',
            ]);

            TransactionItem::create([
                'transaction_id' => $transaction->id,
                'component_id' => $workRequest->component_id,
                'to_location_id' => $locationId,
                'quantity' => $qty,
                'notes' => "Batch: {$batchLot} | Slot: {$specificCode}",
            ]);

            // 3. Mark Work Request as Received
            $workRequest->update([
                'status' => 'received',
                'target_location_id' => $locationId,
                'target_shelf_level' => $shelfLevel,
                'target_slot_number' => $slotNumber,
                'quantity_received' => $qty,
                'received_at' => now(),
                'received_by_user_id' => auth()->id(),
            ]);

            return redirect()->route('work-requests.show', $workRequest)
                ->with('success', "Komponen {$workRequest->component->name} sebanyak {$qty} {$workRequest->component->uom} berhasil diterima di Gudang pada slot {$specificCode} dan stok telah terupdate!");
        });
    }
}
