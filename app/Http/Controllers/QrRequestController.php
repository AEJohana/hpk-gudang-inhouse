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
            ->with(['defaultLocation', 'categoryRelation', 'uomRelation', 'stockBalances'])
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
        $validated = $request->validate([
            'component_id' => 'required|exists:components,id',
            'label_type' => 'required|in:item,box,rack',
            'print_qty' => 'required|integer|min:1|max:500',
            'notes' => 'nullable|string|max:255',
        ]);

        $requestNumber = 'QR-REQ-' . date('Ym') . '-' . rand(100, 999);

        $qrRequest = QrRequest::create([
            'request_number' => $requestNumber,
            'component_id' => $validated['component_id'],
            'label_type' => $validated['label_type'],
            'print_qty' => $validated['print_qty'],
            'notes' => $validated['notes'],
            'status' => 'pending',
            'requested_by' => auth()->id(),
        ]);

        ActivityLogger::qr(
            'created',
            "Membuat permintaan label QR {$qrRequest->request_number} untuk part [{$qrRequest->component?->part_number}] ({$qrRequest->print_qty} label {$qrRequest->label_type}).",
            $qrRequest,
            ['label_type' => $qrRequest->label_type, 'print_qty' => $qrRequest->print_qty]
        );

        return redirect()->route('qr-requests.index')->with('success', "Permintaan label QR {$qrRequest->request_number} berhasil dibuat!");
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
