<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SupplierCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class SupplierCategoryController extends Controller
{
    public function index()
    {
        return view('admin.masterdata.supplier-categories.index');
    }

    public function data(Request $request)
    {
        $rows = SupplierCategory::latest()->get()->map(function ($cat) {
            return [
                'id' => $cat->id,
                'name' => $cat->name,
                'slug' => $cat->slug,
            ];
        });

        return response()->json(['data' => $rows]);
    }

    public function create()
    {
        return view('admin.masterdata.supplier-categories.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:supplier_categories,name'],
        ]);

        $slug = Str::slug($validated['name']);
        $base = $slug; $i = 1;
        while (SupplierCategory::where('slug', $slug)->exists()) { $slug = $base.'-'.$i++; }

        $category = SupplierCategory::create([
            'name' => $validated['name'],
            'slug' => $slug,
        ]);
        if ($request->expectsJson()) {
            return response()->json(['message' => 'Kategori supplier berhasil dibuat', 'data' => $category]);
        }
        return redirect()->to(route('admin.masterdata.suppliers.index') . '#tab_suppcats')->with('success', 'Kategori supplier berhasil dibuat');
    }

    public function edit(SupplierCategory $supplier_category)
    {
        // Route model binding: parameter name must match resource key
        $category = $supplier_category;
        return view('admin.masterdata.supplier-categories.edit', compact('category'));
    }

    public function update(Request $request, SupplierCategory $supplier_category)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('supplier_categories', 'name')->ignore($supplier_category->id)],
        ]);

        $slug = Str::slug($validated['name']);
        $base = $slug; $i = 1;
        while (SupplierCategory::where('slug', $slug)->where('id', '!=', $supplier_category->id)->exists()) { $slug = $base.'-'.$i++; }

        $supplier_category->update([
            'name' => $validated['name'],
            'slug' => $slug,
        ]);
        if ($request->expectsJson()) {
            return response()->json(['message' => 'Kategori supplier berhasil diperbarui', 'data' => $supplier_category]);
        }
        return redirect()->to(route('admin.masterdata.suppliers.index') . '#tab_suppcats')->with('success', 'Kategori supplier berhasil diperbarui');
    }

    public function destroy(Request $request, SupplierCategory $supplier_category)
    {
        $supplier_category->delete();
        if ($request->expectsJson()) {
            return response()->json(['message' => 'Kategori supplier berhasil dihapus']);
        }
        return redirect()->to(route('admin.masterdata.suppliers.index') . '#tab_suppcats')->with('success', 'Kategori supplier berhasil dihapus');
    }
}
