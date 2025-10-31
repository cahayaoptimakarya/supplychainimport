<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Supplier;
use App\Models\SupplierCategory;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class SupplierController extends Controller
{
    public function index()
    {
        return view('admin.masterdata.suppliers.index');
    }

    public function data(Request $request)
    {
        $rows = Supplier::with('category')->latest()->get()->map(function ($s) {
            return [
                'id' => $s->id,
                'name' => $s->name,
                'email' => $s->email,
                'phone' => $s->phone,
                'address' => $s->address,
                'category' => optional($s->category)->name,
            ];
        });

        return response()->json(['data' => $rows]);
    }

    public function create()
    {
        $categories = SupplierCategory::orderBy('name')->get();
        return view('admin.masterdata.suppliers.create', compact('categories'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255', 'unique:suppliers,email'],
            'phone' => ['nullable', 'string', 'max:255'],
            'address' => ['nullable', 'string'],
            'supplier_category_id' => ['required', 'exists:supplier_categories,id'],
        ]);

        Supplier::create($validated);

        return redirect()->route('admin.masterdata.suppliers.index')->with('success', 'Supplier berhasil dibuat');
    }

    public function edit(Supplier $supplier)
    {
        $categories = SupplierCategory::orderBy('name')->get();
        return view('admin.masterdata.suppliers.edit', compact('supplier', 'categories'));
    }

    public function update(Request $request, Supplier $supplier)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255', Rule::unique('suppliers', 'email')->ignore($supplier->id)],
            'phone' => ['nullable', 'string', 'max:255'],
            'address' => ['nullable', 'string'],
            'supplier_category_id' => ['required', 'exists:supplier_categories,id'],
        ]);

        $supplier->update($validated);

        return redirect()->route('admin.masterdata.suppliers.index')->with('success', 'Supplier berhasil diperbarui');
    }

    public function destroy(Supplier $supplier)
    {
        $supplier->delete();
        return redirect()->route('admin.masterdata.suppliers.index')->with('success', 'Supplier berhasil dihapus');
    }
}

