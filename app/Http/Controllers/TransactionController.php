<?php

namespace App\Http\Controllers;

use App\Models\Component;
use App\Models\Location;
use App\Models\StockBalance;
use App\Models\Transaction;
use App\Models\TransactionItem;
use App\Services\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class TransactionController extends Controller
{
    public function index(Request $request)
    {
        $query = Transaction::with(['user', 'items.component', 'items.fromLocation', 'items.toLocation', 'workRequest']);

        if ($request->filled('type')) {
            $type = $request->type;
            if ($type === 'inbound_wr') {
                $query->where('type', 'inbound')
                      ->where(function ($q) {
                          $q->whereNotNull('work_request_id')
                            ->orWhere('reference_document', 'like', 'WRI%')
                            ->orWhere('transaction_number', 'like', '%IN-WRI%');
                      });
            } elseif ($type === 'inbound_manual') {
                $query->where('type', 'inbound')
                      ->whereNull('work_request_id')
                      ->where(function ($q) {
                          $q->where('reference_document', 'not like', 'WRI%')
                            ->orWhereNull('reference_document');
                      })
                      ->where('transaction_number', 'not like', '%IN-WRI%');
            } else {
                $query->where('type', $type);
            }
        }

        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('transaction_number', 'like', "%{$s}%")
                  ->orWhere('spk_number', 'like', "%{$s}%")
                  ->orWhere('reference_document', 'like', "%{$s}%")
                  ->orWhere('notes', 'like', "%{$s}%");
            });
        }

        $transactions = $query->latest('transaction_date')->latest('id')->paginate(15)->withQueryString();

        return view('transactions.index', compact('transactions'));
    }

    public function create(Request $request)
    {
        $defaultType = $request->get('type', 'inbound');
        $locations = Location::orderBy('zone_code')->orderBy('rack_number')->get();
        if ($locations->isEmpty()) {
            Location::ensureDefaultLocationExists();
            $locations = Location::orderBy('zone_code')->orderBy('rack_number')->get();
        }

        // Ultra-fast query builder aggregation (eliminates 5000 disk I/O file_exists calls and 613 N+1 queries)
        $components = DB::table('components')
            ->leftJoin('stock_balances', 'components.id', '=', 'stock_balances.component_id')
            ->where('components.is_active', true)
            ->groupBy('components.id', 'components.part_number', 'components.name', 'components.uom', 'components.minimum_stock', 'components.default_location_id')
            ->select(
                'components.id',
                'components.part_number',
                'components.name',
                'components.uom',
                'components.minimum_stock',
                'components.default_location_id',
                DB::raw('COALESCE(SUM(stock_balances.quantity), 0) as total_stock')
            )
            ->orderBy('components.name')
            ->get();

        return view('transactions.create', compact('defaultType', 'locations', 'components'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'type' => 'required|in:inbound,outbound,transfer,return',
            'spk_number' => 'nullable|string|max:100',
            'reference_document' => 'nullable|string|max:100',
            'transaction_date' => 'nullable|date',
            'notes' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.component_id' => 'required|exists:components,id',
            'items.*.from_location_id' => 'nullable|exists:locations,id',
            'items.*.to_location_id' => 'nullable|exists:locations,id',
            'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.notes' => 'nullable|string',
        ]);

        return DB::transaction(function () use ($validated, $request) {
            $prefix = match($validated['type']) {
                'inbound' => 'TRX-IN',
                'outbound' => 'TRX-OUT',
                'transfer' => 'TRX-TRF',
                'return' => 'TRX-RET',
                default => 'TRX',
            };
            $txNumber = $prefix . '-' . date('Ym') . '-' . strtoupper(Str::random(4));

            $transaction = Transaction::create([
                'transaction_number' => $txNumber,
                'type' => $validated['type'],
                'spk_number' => $validated['spk_number'] ?? null,
                'reference_document' => $validated['reference_document'] ?? null,
                'transaction_date' => now()->toDateString(),
                'notes' => $validated['notes'] ?? null,
                'user_id' => auth()->id(),
                'status' => 'completed',
            ]);

            foreach ($validated['items'] as $itemData) {
                $componentId = $itemData['component_id'];
                $qty = (float) $itemData['quantity'];
                
                // Sanitize location based on transaction type:
                // Inbound comes from supplier/external (no from_location rack).
                // Outbound goes to line/customer (no to_location rack).
                $fromLocId = $validated['type'] === 'inbound' ? null : ($itemData['from_location_id'] ?? null);
                $toLocId = $validated['type'] === 'outbound' ? null : ($itemData['to_location_id'] ?? null);

                TransactionItem::create([
                    'transaction_id' => $transaction->id,
                    'component_id' => $componentId,
                    'from_location_id' => $fromLocId,
                    'to_location_id' => $toLocId,
                    'quantity' => $qty,
                    'notes' => $itemData['notes'] ?? null,
                ]);

                // Update Stock Balances based on transaction type
                if ($validated['type'] === 'inbound') {
                    // Add stock to destination location
                    $balance = StockBalance::firstOrCreate(
                        ['component_id' => $componentId, 'location_id' => $toLocId],
                        ['quantity' => 0]
                    );
                    $balance->quantity += $qty;
                    $balance->save();
                } elseif ($validated['type'] === 'outbound') {
                    // Subtract stock from origin location
                    $balance = StockBalance::firstOrCreate(
                        ['component_id' => $componentId, 'location_id' => $fromLocId],
                        ['quantity' => 0]
                    );
                    $balance->quantity = max(0, $balance->quantity - $qty);
                    $balance->save();
                } elseif ($validated['type'] === 'transfer') {
                    // Subtract from origin, add to destination
                    if ($fromLocId) {
                        $fromBalance = StockBalance::firstOrCreate(
                            ['component_id' => $componentId, 'location_id' => $fromLocId],
                            ['quantity' => 0]
                        );
                        $fromBalance->quantity = max(0, $fromBalance->quantity - $qty);
                        $fromBalance->save();
                    }
                    if ($toLocId) {
                        $toBalance = StockBalance::firstOrCreate(
                            ['component_id' => $componentId, 'location_id' => $toLocId],
                            ['quantity' => 0]
                        );
                        $toBalance->quantity += $qty;
                        $toBalance->save();
                    }
                } elseif ($validated['type'] === 'return') {
                    // Return adds stock to destination location
                    $balance = StockBalance::firstOrCreate(
                        ['component_id' => $componentId, 'location_id' => $toLocId],
                        ['quantity' => 0]
                    );
                    $balance->quantity += $qty;
                    $balance->save();
                }
            }

            ActivityLogger::transaction(
                $validated['type'],
                "Mencatat transaksi {$validated['type']} {$transaction->transaction_number} (SPK: " . ($transaction->spk_number ?: '-') . ") sejumlah " . count($validated['items']) . " item.",
                $transaction,
                ['type' => $validated['type'], 'spk_number' => $transaction->spk_number, 'item_count' => count($validated['items'])]
            );

            return redirect()->route('transactions.show', $transaction)
                ->with('success', "Transaksi {$transaction->transaction_number} berhasil diproses!");
        });
    }

    public function show(Transaction $transaction)
    {
        $transaction->load(['user', 'items.component', 'items.fromLocation', 'items.toLocation', 'workRequest']);
        return view('transactions.show', compact('transaction'));
    }

    /**
     * Cetak massal slip mutasi gudang (Hemat Kertas: 2 slip per lembar HVS/A4)
     */
    public function bulkPrint(Request $request)
    {
        $rawIds = $request->input('ids');
        if (is_string($rawIds)) {
            $ids = array_filter(explode(',', $rawIds));
        } elseif (is_array($rawIds)) {
            $ids = array_filter($rawIds);
        } else {
            $ids = [];
        }

        if (empty($ids)) {
            return redirect()->route('transactions.index')->with('error', 'Silakan pilih minimal 1 transaksi untuk dicetak.');
        }

        $transactions = Transaction::with(['user', 'items.component', 'items.fromLocation', 'items.toLocation', 'workRequest'])
            ->whereIn('id', $ids)
            ->latest('transaction_date')
            ->latest('id')
            ->get();

        if ($transactions->isEmpty()) {
            return redirect()->route('transactions.index')->with('error', 'Tidak ditemukan transaksi yang valid untuk dicetak.');
        }

        ActivityLogger::transaction(
            'printed',
            "Mencetak massal slip mutasi gudang: " . count($transactions) . " dokumen.",
            null,
            ['count' => count($transactions), 'transaction_numbers' => $transactions->pluck('transaction_number')->all()]
        );

        return view('transactions.print_sheet', compact('transactions'));
    }
}
