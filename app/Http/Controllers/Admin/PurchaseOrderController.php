<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Item;
use App\Models\PoLine;
use App\Models\PurchaseOrder;
use App\Models\Supplier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PurchaseOrderController extends Controller
{
    public function index()
    {
        return view('admin.procurement.purchase-orders.index');
    }

    public function data(Request $request)
    {
        $pos = PurchaseOrder::with(['supplier', 'lines'])
            ->latest('order_date')
            ->get()
            ->map(function ($po) {
                $ordered = (float) $po->lines->sum('qty_ordered');
                $koliOrdered = (float) $po->lines->sum('koli_ordered');
                $fulfilled = 0.0;
                foreach ($po->lines as $l) { $fulfilled += (float) $l->fulfilled_qty; }
                $open = max(0.0, $ordered - $fulfilled);
                return [
                    'id' => $po->id,
                    'ref_no' => $po->ref_no,
                    'supplier' => optional($po->supplier)->name,
                    'order_date' => optional($po->order_date)->format('Y-m-d'),
                    'qty_ordered' => $ordered,
                    'koli_ordered' => $koliOrdered,
                    'qty_fulfilled' => $fulfilled,
                    'qty_open' => $open,
                    'status' => $open <= 0 ? 'fulfilled' : ($fulfilled > 0 ? 'partial' : 'open'),
                ];
            });

        return response()->json(['data' => $pos]);
    }

    public function create()
    {
        $suppliers = Supplier::orderBy('name')->get();
        $items = Item::orderBy('name')->get();
        return view('admin.procurement.purchase-orders.create', compact('suppliers', 'items'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'supplier_id' => ['required', 'exists:suppliers,id'],
            'order_date' => ['required', 'date'],
            'ref_no' => ['nullable', 'string', 'max:255'],
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.item_id' => ['required', 'exists:items,id'],
            'lines.*.qty_ordered' => ['required', 'numeric', 'min:0.0001'],
            'lines.*.koli_ordered' => ['nullable', 'numeric', 'min:0'],
            'lines.*.notes' => ['nullable', 'string'],
        ]);

        DB::transaction(function () use ($validated) {
            $po = PurchaseOrder::create([
                'supplier_id' => $validated['supplier_id'],
                'order_date' => $validated['order_date'],
                'ref_no' => $validated['ref_no'] ?? null,
                'status' => 'open',
            ]);
            foreach ($validated['lines'] as $line) {
                PoLine::create([
                    'purchase_order_id' => $po->id,
                    'item_id' => $line['item_id'],
                    'qty_ordered' => $line['qty_ordered'],
                    'koli_ordered' => $line['koli_ordered'] ?? 0,
                    'notes' => $line['notes'] ?? null,
                ]);
            }
        });

        return redirect()->route('admin.procurement.purchase-orders.index')->with('success', 'PO berhasil dibuat');
    }

    public function edit(PurchaseOrder $purchase_order)
    {
        $suppliers = Supplier::orderBy('name')->get();
        $items = Item::orderBy('name')->get();
        $purchase_order->load('lines');
        return view('admin.procurement.purchase-orders.edit', [
            'po' => $purchase_order,
            'suppliers' => $suppliers,
            'items' => $items,
        ]);
    }

    public function update(Request $request, PurchaseOrder $purchase_order)
    {
        $validated = $request->validate([
            'supplier_id' => ['required', 'exists:suppliers,id'],
            'order_date' => ['required', 'date'],
            'ref_no' => ['nullable', 'string', 'max:255'],
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.id' => ['nullable', 'integer'],
            'lines.*.item_id' => ['required', 'exists:items,id'],
            'lines.*.qty_ordered' => ['required', 'numeric', 'min:0.0001'],
            'lines.*.koli_ordered' => ['nullable', 'numeric', 'min:0'],
            'lines.*.notes' => ['nullable', 'string'],
        ]);

        DB::transaction(function () use ($validated, $purchase_order) {
            $purchase_order->update([
                'supplier_id' => $validated['supplier_id'],
                'order_date' => $validated['order_date'],
                'ref_no' => $validated['ref_no'] ?? null,
            ]);

            $keepIds = [];
            foreach ($validated['lines'] as $line) {
                if (!empty($line['id'])) {
                    $pl = PoLine::where('purchase_order_id', $purchase_order->id)->where('id', $line['id'])->firstOrFail();
                    $pl->update([
                        'item_id' => $line['item_id'],
                        'qty_ordered' => $line['qty_ordered'],
                        'koli_ordered' => $line['koli_ordered'] ?? 0,
                        'notes' => $line['notes'] ?? null,
                    ]);
                    $keepIds[] = $pl->id;
                } else {
                    $pl = PoLine::create([
                        'purchase_order_id' => $purchase_order->id,
                        'item_id' => $line['item_id'],
                        'qty_ordered' => $line['qty_ordered'],
                        'koli_ordered' => $line['koli_ordered'] ?? 0,
                        'notes' => $line['notes'] ?? null,
                    ]);
                    $keepIds[] = $pl->id;
                }
            }
            PoLine::where('purchase_order_id', $purchase_order->id)
                ->whereNotIn('id', $keepIds)
                ->delete();
        });

        return redirect()->route('admin.procurement.purchase-orders.index')->with('success', 'PO berhasil diperbarui');
    }

    public function destroy(PurchaseOrder $purchase_order)
    {
        $purchase_order->delete();
        return redirect()->route('admin.procurement.purchase-orders.index')->with('success', 'PO berhasil dihapus');
    }
}
