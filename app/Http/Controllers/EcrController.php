<?php

namespace App\Http\Controllers;

use App\Models\Component;
use App\Models\Ecr;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class EcrController extends Controller
{
    public function index(Request $request)
    {
        $query = Ecr::with(['component', 'requestedBy', 'approvedBy']);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('ecr_number', 'like', "%{$s}%")
                  ->orWhere('title', 'like', "%{$s}%")
                  ->orWhere('reason', 'like', "%{$s}%");
            });
        }

        $ecrs = $query->latest()->paginate(15)->withQueryString();

        return view('ecrs.index', compact('ecrs'));
    }

    public function create(Request $request)
    {
        $components = Component::where('is_active', true)->orderBy('name')->get();
        $selectedComponentId = $request->get('component_id');

        return view('ecrs.create', compact('components', 'selectedComponentId'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'component_id' => 'required|exists:components,id',
            'title' => 'required|string|max:255',
            'revision_type' => 'required|in:spec_change,part_replacement,discontinue',
            'old_specification' => 'nullable|string',
            'new_specification' => 'nullable|string',
            'reason' => 'required|string',
            'stock_policy' => 'required|in:run_out,immediate_scrap,rework',
            'document' => 'nullable|file|mimes:pdf,jpg,png,dwg,zip|max:10240',
        ]);

        $documentPath = null;
        if ($request->hasFile('document')) {
            $file = $request->file('document');
            $fileName = 'ecr_' . time() . '_' . Str::slug($validated['title']) . '.' . $file->getClientOriginalExtension();
            $file->move(public_path('uploads/ecr_docs'), $fileName);
            $documentPath = 'uploads/ecr_docs/' . $fileName;
        }

        $ecrNumber = 'ECR-' . date('Ym') . '-' . rand(100, 999);

        $ecr = Ecr::create([
            'ecr_number' => $ecrNumber,
            'component_id' => $validated['component_id'],
            'title' => $validated['title'],
            'revision_type' => $validated['revision_type'],
            'old_specification' => $validated['old_specification'],
            'new_specification' => $validated['new_specification'],
            'reason' => $validated['reason'],
            'document_path' => $documentPath,
            'stock_policy' => $validated['stock_policy'],
            'status' => 'submitted',
            'requested_by' => auth()->id(),
        ]);

        return redirect()->route('ecrs.show', $ecr)->with('success', "ECR {$ecr->ecr_number} berhasil diajukan dan menunggu persetujuan!");
    }

    public function show(Ecr $ecr)
    {
        $ecr->load(['component.defaultLocation', 'requestedBy', 'approvedBy']);
        return view('ecrs.show', compact('ecr'));
    }

    public function approve(Request $request, Ecr $ecr)
    {
        $request->validate([
            'approval_notes' => 'nullable|string',
        ]);

        $ecr->update([
            'status' => 'approved',
            'approved_by' => auth()->id(),
            'approved_at' => now(),
            'approval_notes' => $request->approval_notes,
        ]);

        // If revision updates the specification of the component, update component specification
        if ($ecr->revision_type === 'spec_change' && !empty($ecr->new_specification)) {
            $ecr->component->update([
                'specification' => $ecr->new_specification,
            ]);
        }

        return redirect()->route('ecrs.show', $ecr)->with('success', "ECR {$ecr->ecr_number} telah disetujui!");
    }

    public function reject(Request $request, Ecr $ecr)
    {
        $request->validate([
            'approval_notes' => 'required|string',
        ]);

        $ecr->update([
            'status' => 'rejected',
            'approved_by' => auth()->id(),
            'approved_at' => now(),
            'approval_notes' => $request->approval_notes,
        ]);

        return redirect()->route('ecrs.show', $ecr)->with('error', "ECR {$ecr->ecr_number} telah ditolak!");
    }
}
