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
use App\Services\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class WorkStationSupplyController extends Controller
{
    /**
     * Material Handling - Task Gudang (Struktur 2 Tab Sesuai Sistem Legacy).
     */
    public function index(Request $request)
    {
        // Ambil semua tiket WRI aktif yang sudah disetujui (siap diproses logistik)
        $workRequests = WorkRequest::with(['component.defaultLocation', 'targetLocation', 'steps.machine', 'requestedBy', 'logs'])
            ->where('approval_pengaju', 'Diterima')
            ->where('approval_penerima', 'Diterima')
            ->latest('updated_at')
            ->get();

        $transferWaiting = [];
        $transferTransit = [];
        $transferHistory = [];

        $finalWaiting = [];
        $finalTransit = [];
        $finalHistory = [];

        foreach ($workRequests as $item) {
            $steps = $item->getRoutingStepsList();
            $lastStep = !empty($steps) ? $steps[count($steps) - 1] : '';

            // ==========================================
            // TAB 2: SIAP DIAMBIL (DISTRIBUSI KE PENGAJU)
            // ==========================================
            $totalSiap = $item->getTotalProcessedForStatus('Siap diambil');
            if ($totalSiap == 0 && $lastStep !== '') {
                $totalSiap = $item->getTotalProcessedForStatus("Proses: {$lastStep} (Out)");
            }
            if ($totalSiap == 0 && (stripos($item->status, 'Siap Diambil') !== false || $item->status === 'ready_for_warehouse')) {
                $totalSiap = (float) $item->quantity_requested;
            }
            $totalFinalPickup = $item->getTotalProcessedForStatus('Menuju Pengaju');
            $totalSudah = (float) $item->quantity_received ?: $item->getTotalProcessedForStatus('Sudah diambil');

            $menungguAmbilFinal = max(0, $totalSiap - $totalFinalPickup);
            $sedangDibawaFinal = max(0, $totalFinalPickup - $totalSudah);

            // Sub-tab 1: Siap Diambil
            if ($menungguAmbilFinal > 0) {
                $finalWaiting[] = [
                    'id' => $item->id,
                    'wri_number' => $item->wri_number,
                    'component_name' => $item->component?->name ?? 'Komponen',
                    'part_number' => $item->component?->part_number ?? '-',
                    'dimension' => $item->component?->dimension_formatted ?? '-',
                    'qty' => $menungguAmbilFinal,
                    'location' => $lastStep ?: '-',
                    'destination' => $item->dept_pengaju ?: 'Gudang',
                    'tgl' => $item->created_at->format('d/m/Y'),
                    'tgl_raw' => $item->created_at->format('Y-m-d'),
                    'current_status' => 'Siap Diambil',
                    'target_status' => 'Menuju Pengaju',
                    'routing' => $item->routing_text,
                    'target_location' => $item->target_specific_location_code,
                    'image_url' => $item->component?->image_url ?? asset('images/logo_hpk.webp'),
                    'item' => $item,
                ];
            }

            // Sub-tab 2: Dalam Perjalanan (Final Transit)
            if ($sedangDibawaFinal > 0) {
                $lastPickupLog = $item->logs->filter(function ($l) {
                    return stripos($l->status_label, 'Menuju Pengaju') !== false;
                })->last();

                $finalTransit[] = [
                    'id' => $item->id,
                    'wri_number' => $item->wri_number,
                    'component_name' => $item->component?->name ?? 'Komponen',
                    'part_number' => $item->component?->part_number ?? '-',
                    'dimension' => $item->component?->dimension_formatted ?? '-',
                    'qty' => $sedangDibawaFinal,
                    'location' => $lastStep ?: '-',
                    'destination' => $item->dept_pengaju ?: 'Gudang',
                    'tgl' => $item->created_at->format('d/m/Y'),
                    'tgl_raw' => $item->created_at->format('Y-m-d'),
                    'pickup_time' => $lastPickupLog ? $lastPickupLog->created_at->format('d/m H:i') : '-',
                    'current_status' => 'Menuju Pengaju',
                    'routing' => $item->routing_text,
                    'target_location' => $item->target_specific_location_code,
                    'image_url' => $item->component?->image_url ?? asset('images/logo_hpk.webp'),
                    'item' => $item,
                ];
            }

            // Sub-tab 3: Riwayat Selesai
            if ($totalSudah > 0) {
                $lastReceiveLog = $item->logs->filter(function ($l) {
                    return stripos($l->status_label, 'Sudah Diambil') !== false;
                })->last();

                $finalHistory[] = [
                    'id' => $item->id,
                    'wri_number' => $item->wri_number,
                    'component_name' => $item->component?->name ?? 'Komponen',
                    'part_number' => $item->component?->part_number ?? '-',
                    'dimension' => $item->component?->dimension_formatted ?? '-',
                    'qty' => $totalSudah,
                    'location' => $lastStep ?: '-',
                    'destination' => $item->dept_pengaju ?: 'Gudang',
                    'tgl' => $item->created_at->format('d/m/Y'),
                    'tgl_raw' => $item->created_at->format('Y-m-d'),
                    'received_time' => $lastReceiveLog ? $lastReceiveLog->created_at->format('d/m H:i') : '-',
                    'status' => "Selesai ({$totalSudah} pcs)",
                    'routing' => $item->routing_text,
                    'target_location' => $item->target_specific_location_code,
                    'image_url' => $item->component?->image_url ?? asset('images/logo_hpk.webp'),
                    'item' => $item,
                ];
            }

            // ==========================================
            // TAB 1: ANTAR MESIN (TRANSFER ANTAR TAHAPAN)
            // ==========================================
            foreach ($steps as $idx => $step) {
                // Step terakhir langsung masuk ke Siap Diambil, bukan Antar Mesin
                if ($idx === count($steps) - 1) {
                    continue;
                }

                $nextStep = $steps[$idx + 1] ?? 'Gudang Akhir';
                $totalOut = $item->getTotalProcessedForStatus("Proses: {$step} (Out)");
                $totalPickup = $item->getTotalProcessedForStatus("MH Pickup: {$step}");
                $totalNextIn = $item->getTotalProcessedForStatus("Proses: {$nextStep} (In)");

                $menungguAmbil = max(0, $totalOut - $totalPickup);
                $sedangDibawa = max(0, $totalPickup - $totalNextIn);

                // Sub-tab 1: Menunggu Ambil
                if ($menungguAmbil > 0) {
                    $transferWaiting[] = [
                        'id' => $item->id,
                        'wri_number' => $item->wri_number,
                        'component_name' => $item->component?->name ?? 'Komponen',
                        'part_number' => $item->component?->part_number ?? '-',
                        'dimension' => $item->component?->dimension_formatted ?? '-',
                        'qty' => $menungguAmbil,
                        'origin' => $step,
                        'destination' => $nextStep,
                        'tgl' => $item->created_at->format('d/m/Y'),
                        'tgl_raw' => $item->created_at->format('Y-m-d'),
                        'current_status' => "Proses: {$step} (Out)",
                        'target_status' => "MH Pickup: {$step}",
                        'routing' => $item->routing_text,
                        'target_location' => $item->target_specific_location_code,
                        'image_url' => $item->component?->image_url ?? asset('images/logo_hpk.webp'),
                        'item' => $item,
                    ];
                }

                // Sub-tab 2: Dalam Perjalanan (Transit)
                if ($sedangDibawa > 0) {
                    $lastPickupLog = $item->logs->filter(function ($l) use ($step) {
                        return stripos($l->status_label, "MH Pickup: {$step}") !== false;
                    })->last();

                    $transferTransit[] = [
                        'id' => $item->id,
                        'wri_number' => $item->wri_number,
                        'component_name' => $item->component?->name ?? 'Komponen',
                        'part_number' => $item->component?->part_number ?? '-',
                        'dimension' => $item->component?->dimension_formatted ?? '-',
                        'qty' => $sedangDibawa,
                        'origin' => $step,
                        'destination' => $nextStep,
                        'tgl' => $item->created_at->format('d/m/Y'),
                        'tgl_raw' => $item->created_at->format('Y-m-d'),
                        'pickup_time' => $lastPickupLog ? $lastPickupLog->created_at->format('d/m H:i') : '-',
                        'current_status' => "MH Pickup: {$step}",
                        'routing' => $item->routing_text,
                        'target_location' => $item->target_specific_location_code,
                        'image_url' => $item->component?->image_url ?? asset('images/logo_hpk.webp'),
                        'item' => $item,
                    ];
                }

                // Sub-tab 3: Riwayat Transfer
                if ($totalPickup > 0) {
                    $transferHistory[] = [
                        'id' => $item->id,
                        'wri_number' => $item->wri_number,
                        'component_name' => $item->component?->name ?? 'Komponen',
                        'part_number' => $item->component?->part_number ?? '-',
                        'dimension' => $item->component?->dimension_formatted ?? '-',
                        'qty' => $totalPickup,
                        'origin' => $step,
                        'destination' => $nextStep,
                        'tgl' => $item->created_at->format('d/m/Y'),
                        'tgl_raw' => $item->created_at->format('Y-m-d'),
                        'status' => ($totalNextIn >= $totalPickup) ? "Terkirim ({$totalPickup} pcs)" : "Di-pickup MH ({$totalPickup} pcs)",
                        'routing' => $item->routing_text,
                        'target_location' => $item->target_specific_location_code,
                        'image_url' => $item->component?->image_url ?? asset('images/logo_hpk.webp'),
                        'item' => $item,
                    ];
                }
            }
        }

        // List unik untuk dropdown filter
        $filterOrigins = collect($transferWaiting)->pluck('origin')->merge(collect($transferTransit)->pluck('origin'))->unique()->values()->all();
        $filterDestinations = collect($transferWaiting)->pluck('destination')->merge(collect($transferTransit)->pluck('destination'))->unique()->values()->all();
        $filterFinalLocations = collect($finalWaiting)->pluck('location')->merge(collect($finalTransit)->pluck('location'))->unique()->values()->all();

        return view('work_station_supplies.index', compact(
            'transferWaiting',
            'transferTransit',
            'transferHistory',
            'finalWaiting',
            'finalTransit',
            'finalHistory',
            'filterOrigins',
            'filterDestinations',
            'filterFinalLocations'
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

        // Cari berdasarkan wri_number persis, like wri_number, atau part_number komponen (termasuk format komposit QR: Nama;Kode;Rak)
        $cleanCode = str_replace('HPK-PART|', '', $code);
        $searchCandidates = [$code, $cleanCode];
        if (str_contains($cleanCode, ';')) {
            $tokens = array_values(array_filter(array_map('trim', explode(';', $cleanCode))));
            $searchCandidates = array_unique(array_merge($searchCandidates, $tokens));
        }

        $wr = WorkRequest::with(['component', 'targetLocation', 'steps.machine'])
            ->whereIn('wri_number', $searchCandidates)
            ->orWhere(function ($q) use ($searchCandidates) {
                foreach ($searchCandidates as $c) {
                    $q->orWhere('wri_number', 'like', "%{$c}%");
                }
            })
            ->orWhereHas('component', function ($q) use ($searchCandidates) {
                $q->whereIn('part_number', $searchCandidates)
                  ->orWhere(function ($sub) use ($searchCandidates) {
                      foreach ($searchCandidates as $c) {
                          if (strlen($c) >= 3) {
                              $sub->orWhere('name', 'like', "%{$c}%");
                          }
                      }
                  });
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
            'target_status' => 'nullable|string|max:100',
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

            ActivityLogger::materialHandling(
                'supply',
                "Supply material ke stasiun {$workStation->name} (SPK: {$validated['spk_number']}) dengan transaksi {$transaction->transaction_number}.",
                $transaction,
                ['spk_number' => $validated['spk_number'], 'recipient' => $validated['recipient_name'], 'item_count' => count($validated['items'])]
            );

            return redirect()->route('transactions.show', $transaction)
                ->with('success', "Supply komponen ke {$workStation->name} (SPK: {$validated['spk_number']}) berhasil diproses! Stok gudang telah dipotong.");
        });
    }
}
