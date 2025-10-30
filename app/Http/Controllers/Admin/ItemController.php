<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Uom;
use App\Models\Item;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\Rule;

class ItemController extends Controller
{
    public function index()
    {
        return view('admin.masterdata.items.index');
    }

    public function data(Request $request)
    {
        $items = Item::with(['category','uom'])->latest()->get()->map(function ($item) {
            return [
                'id' => $item->id,
                'name' => $item->name,
                'sku' => $item->sku,
                'category' => optional($item->category)->name,
                'uom' => optional($item->uom)->name,
                'cnt' => $item->cnt,
                'description' => $item->description,
            ];
        });

        return response()->json(['data' => $items]);
    }

    public function create()
    {
        $categories = Category::orderBy('name')->get();
        $uoms = Uom::orderBy('name')->get();
        return view('admin.masterdata.items.create', compact('categories','uoms'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'sku' => ['required', 'string', 'max:255', 'unique:items,sku'],
            'cnt' => ['required', 'string', 'max:255'],
            'category_id' => ['required', 'exists:categories,id'],
            'uom_id' => ['required', 'exists:uoms,id'],
            'description' => ['nullable', 'string'],
        ]);

        Item::create($validated);

        return redirect()
            ->route('admin.masterdata.items.index')
            ->with('success', 'Item berhasil dibuat');
    }

    public function edit(Item $item)
    {
        $categories = Category::orderBy('name')->get();
        $uoms = Uom::orderBy('name')->get();
        return view('admin.masterdata.items.edit', compact('item', 'categories','uoms'));
    }

    public function update(Request $request, Item $item)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'sku' => ['required', 'string', 'max:255', Rule::unique('items', 'sku')->ignore($item->id)],
            'cnt' => ['required', 'string', 'max:255'],
            'category_id' => ['required', 'exists:categories,id'],
            'uom_id' => ['required', 'exists:uoms,id'],
            'description' => ['nullable', 'string'],
        ]);

        $item->update($validated);

        return redirect()
            ->route('admin.masterdata.items.index')
            ->with('success', 'Item berhasil diperbarui');
    }

    public function destroy(Item $item)
    {
        $item->delete();
        return redirect()
            ->route('admin.masterdata.items.index')
            ->with('success', 'Item berhasil dihapus');
    }
}
