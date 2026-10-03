<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Uom;
use Illuminate\Http\Request;

class UomController extends Controller
{
    public function index()
    {
        $uoms = Uom::orderBy('name')->get();
        return view('admin.uoms.index', compact('uoms'));
    }

    public function create()
    {
        return view('admin.uoms.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'code' => 'required|string|unique:uoms,code',
            'name' => 'required|string|max:255',
            'is_active' => 'boolean',
        ]);

        $validated['is_active'] = $request->has('is_active');

        Uom::create($validated);

        return redirect()->route('admin.uoms.index')->with('success', 'UoM berhasil ditambahkan.');
    }

    public function edit(Uom $uom)
    {
        return view('admin.uoms.edit', compact('uom'));
    }

    public function update(Request $request, Uom $uom)
    {
        $validated = $request->validate([
            'code' => 'required|string|unique:uoms,code,'.$uom->id,
            'name' => 'required|string|max:255',
            'is_active' => 'boolean',
        ]);

        $validated['is_active'] = $request->has('is_active');

        $uom->update($validated);

        return redirect()->route('admin.uoms.index')->with('success', 'UoM berhasil diperbarui.');
    }

    public function destroy(Uom $uom)
    {
        if (\App\Models\Component::where('uom_id', $uom->id)->exists()) {
            return back()->with('error', 'UoM ini tidak dapat dihapus karena masih digunakan.');
        }

        $uom->delete();

        return redirect()->route('admin.uoms.index')->with('success', 'UoM berhasil dihapus.');
    }
}
