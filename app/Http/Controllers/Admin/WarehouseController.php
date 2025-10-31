<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Warehouse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class WarehouseController extends Controller
{
    public function index()
    {
        return view('admin.masterdata.warehouses.index');
    }

    public function data(Request $request)
    {
        $rows = Warehouse::latest()->get()->map(function ($w) {
            return [
                'id' => $w->id,
                'name' => $w->name,
                'address' => $w->address,
                'description' => $w->description,
            ];
        });

        return response()->json(['data' => $rows]);
    }

    public function create()
    {
        return view('admin.masterdata.warehouses.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:warehouses,name'],
            'address' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
        ]);

        Warehouse::create($validated);

        return redirect()->route('admin.masterdata.warehouses.index')->with('success', 'Warehouse berhasil dibuat');
    }

    public function edit(Warehouse $warehouse)
    {
        return view('admin.masterdata.warehouses.edit', compact('warehouse'));
    }

    public function update(Request $request, Warehouse $warehouse)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('warehouses', 'name')->ignore($warehouse->id)],
            'address' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
        ]);

        $warehouse->update($validated);

        return redirect()->route('admin.masterdata.warehouses.index')->with('success', 'Warehouse berhasil diperbarui');
    }

    public function destroy(Warehouse $warehouse)
    {
        $warehouse->delete();
        return redirect()->route('admin.masterdata.warehouses.index')->with('success', 'Warehouse berhasil dihapus');
    }
}

