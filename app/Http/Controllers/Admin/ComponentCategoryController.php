<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ComponentCategory;
use Illuminate\Http\Request;

class ComponentCategoryController extends Controller
{
    public function index()
    {
        $categories = ComponentCategory::with('parent')->orderBy('order')->get();
        return view('admin.categories.index', compact('categories'));
    }

    public function create()
    {
        $parents = ComponentCategory::whereNull('parent_id')->orderBy('name')->get();
        return view('admin.categories.create', compact('parents'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'code' => 'required|string|unique:component_categories,code',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'parent_id' => 'nullable|exists:component_categories,id',
            'order' => 'integer',
            'is_active' => 'boolean',
            'has_expiry' => 'boolean',
            'default_min_stock' => 'integer|min:0',
        ]);

        $validated['is_active'] = $request->has('is_active');
        $validated['has_expiry'] = $request->has('has_expiry');

        ComponentCategory::create($validated);

        return redirect()->route('admin.component-categories.index')->with('success', 'Kategori Komponen berhasil ditambahkan.');
    }

    public function edit(ComponentCategory $componentCategory)
    {
        $parents = ComponentCategory::whereNull('parent_id')->where('id', '!=', $componentCategory->id)->orderBy('name')->get();
        return view('admin.categories.edit', compact('componentCategory', 'parents'));
    }

    public function update(Request $request, ComponentCategory $componentCategory)
    {
        $validated = $request->validate([
            'code' => 'required|string|unique:component_categories,code,'.$componentCategory->id,
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'parent_id' => 'nullable|exists:component_categories,id',
            'order' => 'integer',
            'is_active' => 'boolean',
            'has_expiry' => 'boolean',
            'default_min_stock' => 'integer|min:0',
        ]);

        $validated['is_active'] = $request->has('is_active');
        $validated['has_expiry'] = $request->has('has_expiry');

        $componentCategory->update($validated);

        return redirect()->route('admin.component-categories.index')->with('success', 'Kategori Komponen berhasil diperbarui.');
    }

    public function destroy(ComponentCategory $componentCategory)
    {
        if (\App\Models\Component::where('component_category_id', $componentCategory->id)->exists()) {
            return back()->with('error', 'Kategori ini tidak dapat dihapus karena masih digunakan oleh beberapa komponen.');
        }

        $componentCategory->delete();

        return redirect()->route('admin.component-categories.index')->with('success', 'Kategori Komponen berhasil dihapus.');
    }
}
