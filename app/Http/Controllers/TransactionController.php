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
        $query = Transaction::with(['user', 'items.component', 'items.fromLocation', 'items.toLocation']);

        if ($request->filled('type')) {
            $query->where('type', $request->type);
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
        $defaultType = $request->get('type', 'outbound');
        $locations = Location::orderBy('zone_code')->orderBy('rack_number')->get();
        if ($locations->isEmpty()) {
            Location::ensureDefaultLocationExists();
            $locations = Location::orderBy('zone_code')->orderBy('rack_number')->get();
        }
        $components = Component::where('is_active', true)->orderBy('name')->get();

        return view('transactions.create', compact('defaultType', 'locations', 'components'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'type' => 'required|in:inbound,outbound,transfer,return',
            'spk_number' => 'nullable|string|max:100', // Opsional untuk saat ini
            'reference_document' => 'nullable|string|max:100',
            'transaction_date' => 'required|date',
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
                'spk_number' => $validated['spk_number'],
                'reference_document' => $validated['reference_document'],
                'transaction_date' => $validated['transaction_date'],
                'notes' => $validated['notes'],
                'user_id' => auth()->id(),
                'status' => 'completed',
            ]);

            foreach ($validated['items'] as $itemData) {
                $componentId = $itemData['component_id'];
                $qty = (float) $itemData['quantity'];
                $fromLocId = $itemData['from_location_id'] ?? null;
                $toLocId = $itemData['to_location_id'] ?? null;

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
        $transaction->load(['user', 'items.component', 'items.fromLocation', 'items.toLocation']);
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

        $transactions = Transaction::with(['user', 'items.component', 'items.fromLocation', 'items.toLocation'])
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
