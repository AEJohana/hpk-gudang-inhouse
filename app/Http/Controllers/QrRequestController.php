<?php

namespace App\Http\Controllers;

use App\Models\Component;
use App\Models\QrRequest;
use App\Services\ActivityLogger;
use Illuminate\Http\Request;

class QrRequestController extends Controller
{
    public function index(Request $request)
    {
        $status = $request->get('status');
        $currentStatus = in_array($status, ['pending', 'printed', 'all'], true) ? $status : 'pending';

        $counts = QrRequest::selectRaw("
            COUNT(*) as total,
            COUNT(CASE WHEN status = 'pending' THEN 1 END) as pending,
            COUNT(CASE WHEN status = 'printed' THEN 1 END) as printed
        ")->first() ?? (object) ['total' => 0, 'pending' => 0, 'printed' => 0];

        $query = QrRequest::with(['component.defaultLocation', 'requestedBy']);

        if ($currentStatus !== 'all') {
            $query->where('status', $currentStatus);
        }

        if ($request->filled('q')) {
            $searchTerm = '%' . trim($request->q) . '%';
            $query->where(function ($q) use ($searchTerm) {
                $q->where('request_number', 'like', $searchTerm)
                    ->orWhereHas('component', function ($cq) use ($searchTerm) {
                        $cq->where('part_number', 'like', $searchTerm)
                            ->orWhere('name', 'like', $searchTerm);
                    });
            });
        }

        $qrRequests = $query->latest()->paginate(15)->withQueryString();

        return view('qr_requests.index', compact('qrRequests', 'currentStatus', 'counts'));
    }

    public function create(Request $request)
    {
        $components = Component::where('is_active', true)
            ->with(['defaultLocation', 'componentCategory', 'uomRelation', 'stockBalances'])
            ->orderBy('name')
            ->get();
        $selectedComponentId = (string) $request->get('component_id', '');

        $componentsJson = $components->map(function ($comp) {
            return [
                'id' => (string) $comp->id,
                'part_number' => $comp->part_number,
                'name' => $comp->name,
                'category' => $comp->category_label,
                'uom' => $comp->uom ?: 'Pcs',
                'location' => $comp->default_full_location_code !== '-' 
                    ? $comp->default_full_location_code 
                    : ($comp->defaultLocation ? $comp->defaultLocation->full_location_code : 'Belum diatur'),
                'dimension' => $comp->dimension_formatted,
                'total_stock' => (float) $comp->total_stock,
                'image_url' => $comp->image_url,
                'is_pdf' => (bool) $comp->is_pdf_drawing,
                'specs' => $comp->description ?: '',
            ];
        });

        return view('qr_requests.create', compact('components', 'selectedComponentId', 'componentsJson'));
    }

    public function store(Request $request)
    {
        // Mendukung input multi-item keranjang (cart_data JSON string atau array items)
        $items = [];
        if ($request->filled('cart_data')) {
            $decoded = json_decode($request->cart_data, true);
            if (is_array($decoded)) {
                $items = $decoded;
            }
        } elseif ($request->has('items') && is_array($request->items)) {
            $items = $request->items;
        } elseif ($request->filled('component_id')) {
            // Fallback untuk single-item legacy
            $items = [[
                'component_id' => $request->component_id,
                'label_type' => $request->label_type,
                'print_qty' => $request->print_qty,
                'notes' => $request->notes,
            ]];
        }

        if (empty($items)) {
            return back()->withErrors(['cart' => 'Keranjang permintaan cetak masih kosong.'])->withInput();
        }

        $validatedData = validator(['items' => $items], [
            'items' => 'required|array|min:1',
            'items.*.component_id' => 'required|exists:components,id',
            'items.*.label_type' => 'required|in:rack,card,item,box',
            'items.*.print_qty' => 'required|integer|min:1|max:5000',
            'items.*.notes' => 'nullable|string|max:255',
        ])->validate();

        $createdRequests = [];
        \Illuminate\Support\Facades\DB::transaction(function () use ($validatedData, &$createdRequests) {
            foreach ($validatedData['items'] as $item) {
                // Generate nomor tiket unik
                $randomSuffix = rand(100, 999) . strtoupper(\Illuminate\Support\Str::random(2));
                $requestNumber = 'QR-REQ-' . date('Ym') . '-' . $randomSuffix;

                $qrRequest = QrRequest::create([
                    'request_number' => $requestNumber,
                    'component_id' => $item['component_id'],
                    'label_type' => in_array($item['label_type'], ['card', 'item']) ? 'card' : $item['label_type'],
                    'print_qty' => $item['print_qty'],
                    'notes' => $item['notes'] ?? null,
                    'status' => 'pending',
                    'requested_by' => auth()->id() ?: 1,
                ]);

                ActivityLogger::qr(
                    'created',
                    "Membuat permintaan label QR {$qrRequest->request_number} untuk part [{$qrRequest->component?->part_number}] ({$qrRequest->print_qty} label {$qrRequest->label_type}).",
                    $qrRequest,
                    ['label_type' => $qrRequest->label_type, 'print_qty' => $qrRequest->print_qty]
                );

                $createdRequests[] = $qrRequest;
            }
        });

        $count = count($createdRequests);
        $message = $count > 1 
            ? "Berhasil mengajukan {$count} permintaan cetak label QR ke dalam antrean!" 
            : "Permintaan label QR {$createdRequests[0]->request_number} berhasil dibuat!";

        return redirect()->route('qr-requests.index')->with('success', $message);
    }

    public function printThermal(QrRequest $qrRequest)
    {
        $qrRequest->load(['component.defaultLocation']);
        $qrRequest->update([
            'status' => 'printed',
            'printed_at' => now(),
        ]);

        ActivityLogger::qr(
            'printed',
            "Mencetak label thermal 70x45mm untuk request {$qrRequest->request_number} [{$qrRequest->component?->part_number}].",
            $qrRequest
        );

        return view('qr_requests.print_thermal', compact('qrRequest'));
    }

    public function printSheet(QrRequest $qrRequest)
    {
        $qrRequest->load(['component.defaultLocation']);
        $qrRequest->update([
            'status' => 'printed',
            'printed_at' => now(),
        ]);

        ActivityLogger::qr(
            'printed',
            "Mencetak label lembar A4 untuk request {$qrRequest->request_number} [{$qrRequest->component?->part_number}].",
            $qrRequest
        );

        return view('qr_requests.print_sheet', compact('qrRequest'));
    }
}
