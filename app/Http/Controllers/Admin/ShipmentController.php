<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Item;
use App\Models\Shipment;
use App\Models\ShipmentItem;
use App\Models\Supplier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ShipmentController extends Controller
{
    public function index()
    {
        return view('admin.procurement.shipments.index');
    }

    public function data(Request $request)
    {
        $rows = Shipment::with(['supplier', 'items'])
            ->latest('id')
            ->get()
            ->map(function ($s) {
                $koliExpected = (float) $s->items->sum('koli_expected');
                return [
                    'id' => $s->id,
                    'supplier' => optional($s->supplier)->name,
                    'container_no' => $s->container_no,
                    'pl_no' => $s->pl_no,
                    'etd' => optional($s->etd)->format('Y-m-d'),
                    'eta' => optional($s->eta)->format('Y-m-d'),
                    'status' => $s->status,
                    'items_count' => $s->items->count(),
                    'koli_expected_total' => $koliExpected,
                ];
            });
        return response()->json(['data' => $rows]);
    }

    public function create()
    {
        $suppliers = Supplier::orderBy('name')->get();
        $items = Item::orderBy('name')->get();
        return view('admin.procurement.shipments.create', compact('suppliers', 'items'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'supplier_id' => ['nullable', 'exists:suppliers,id'],
            'container_no' => ['nullable', 'string', 'max:255'],
            'pl_no' => ['nullable', 'string', 'max:255'],
            'etd' => ['nullable', 'date'],
            'eta' => ['nullable', 'date'],
            'status' => ['required', 'string'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.item_id' => ['required', 'exists:items,id'],
            'items.*.qty_expected' => ['required', 'numeric', 'min:0.0001'],
            'items.*.koli_expected' => ['nullable', 'numeric', 'min:0'],
        ]);

        DB::transaction(function () use ($validated) {
            $shipment = Shipment::create([
                'supplier_id' => $validated['supplier_id'] ?? null,
                'container_no' => $validated['container_no'] ?? null,
                'pl_no' => $validated['pl_no'] ?? null,
                'etd' => $validated['etd'] ?? null,
                'eta' => $validated['eta'] ?? null,
                'status' => $validated['status'] ?? 'planned',
            ]);
            foreach ($validated['items'] as $row) {
                ShipmentItem::create([
                    'shipment_id' => $shipment->id,
                    'item_id' => $row['item_id'],
                    'qty_expected' => $row['qty_expected'],
                    'koli_expected' => $row['koli_expected'] ?? 0,
                ]);
            }
        });

        return redirect()->route('admin.procurement.shipments.index')->with('success', 'Shipment berhasil dibuat');
    }

    public function edit(Shipment $shipment)
    {
        $suppliers = Supplier::orderBy('name')->get();
        $items = Item::orderBy('name')->get();
        $shipment->load('items');
        return view('admin.procurement.shipments.edit', compact('shipment', 'suppliers', 'items'));
    }

    public function update(Request $request, Shipment $shipment)
    {
        $validated = $request->validate([
            'supplier_id' => ['nullable', 'exists:suppliers,id'],
            'container_no' => ['nullable', 'string', 'max:255'],
            'pl_no' => ['nullable', 'string', 'max:255'],
            'etd' => ['nullable', 'date'],
            'eta' => ['nullable', 'date'],
            'status' => ['required', 'string'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.id' => ['nullable', 'integer'],
            'items.*.item_id' => ['required', 'exists:items,id'],
            'items.*.qty_expected' => ['required', 'numeric', 'min:0.0001'],
            'items.*.koli_expected' => ['nullable', 'numeric', 'min:0'],
        ]);

        DB::transaction(function () use ($validated, $shipment) {
            $shipment->update([
                'supplier_id' => $validated['supplier_id'] ?? null,
                'container_no' => $validated['container_no'] ?? null,
                'pl_no' => $validated['pl_no'] ?? null,
                'etd' => $validated['etd'] ?? null,
                'eta' => $validated['eta'] ?? null,
                'status' => $validated['status'] ?? 'planned',
            ]);

            $keep = [];
            foreach ($validated['items'] as $row) {
                if (!empty($row['id'])) {
                    $si = ShipmentItem::where('shipment_id', $shipment->id)->where('id', $row['id'])->firstOrFail();
                    $si->update([
                        'item_id' => $row['item_id'],
                        'qty_expected' => $row['qty_expected'],
                        'koli_expected' => $row['koli_expected'] ?? 0,
                    ]);
                    $keep[] = $si->id;
                } else {
                    $si = ShipmentItem::create([
                        'shipment_id' => $shipment->id,
                        'item_id' => $row['item_id'],
                        'qty_expected' => $row['qty_expected'],
                        'koli_expected' => $row['koli_expected'] ?? 0,
                    ]);
                    $keep[] = $si->id;
                }
            }
            ShipmentItem::where('shipment_id', $shipment->id)->whereNotIn('id', $keep)->delete();
        });

        return redirect()->route('admin.procurement.shipments.index')->with('success', 'Shipment berhasil diperbarui');
    }

    public function destroy(Shipment $shipment)
    {
        $shipment->delete();
        return redirect()->route('admin.procurement.shipments.index')->with('success', 'Shipment berhasil dihapus');
    }
}
