<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Uom;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class UomController extends Controller
{
    public function index()
    {
        return view('admin.masterdata.uom.index');
    }

    public function data(Request $request)
    {
        $uoms = Uom::orderBy('name')->get()->map(function ($uom) {
            return [
                'id' => $uom->id,
                'name' => $uom->name,
            ];
        });
        return response()->json(['data' => $uoms]);
    }

    public function create()
    {
        return view('admin.masterdata.uom.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100', 'unique:uoms,name'],
        ]);
        Uom::create($validated);
        return redirect()->route('admin.masterdata.uom.index')->with('success', 'UOM berhasil dibuat');
    }

    public function edit(Uom $uom)
    {
        return view('admin.masterdata.uom.edit', compact('uom'));
    }

    public function update(Request $request, Uom $uom)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100', Rule::unique('uoms', 'name')->ignore($uom->id)],
        ]);
        $uom->update($validated);
        return redirect()->route('admin.masterdata.uom.index')->with('success', 'UOM berhasil diperbarui');
    }

    public function destroy(Uom $uom)
    {
        $uom->delete();
        return redirect()->route('admin.masterdata.uom.index')->with('success', 'UOM berhasil dihapus');
    }
}
