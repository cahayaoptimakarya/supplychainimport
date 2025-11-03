<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Item;
use App\Models\Shipment;
use App\Models\Warehouse;
use App\Models\WarehouseReceipt;
use App\Models\ReceiptItem;
use App\Services\FifoAllocator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Support\Str;

class WarehouseReceiptController extends Controller
{
    public function index()
    {
        return view('admin.procurement.receipts.index');
    }

    public function data(Request $request)
    {
        $rows = WarehouseReceipt::with(['shipment', 'warehouse', 'items'])
            ->latest('id')
            ->get()
            ->map(function ($r) {
                $qty = (float) $r->items->sum('qty_received');
                $koli = (float) $r->items->sum('koli_received');
                return [
                    'id' => $r->id,
                    'code' => $r->code,
                    'shipment' => $r->shipment ? ($r->shipment->container_no ?: ('#'.$r->shipment->id)) : '-',
                    'warehouse' => optional($r->warehouse)->name,
                    'received_at' => optional($r->received_at)->format('Y-m-d H:i'),
                    'status' => $r->status,
                    'qty_total' => $qty,
                    'koli_total' => $koli,
                ];
            });
        return response()->json(['data' => $rows]);
    }

    public function create()
    {
        $warehouses = Warehouse::orderBy('name')->get();
        // shipments available for receiving (any status, user decides)
        $shipments = Shipment::with('items')->orderByDesc('id')->get();
        return view('admin.procurement.receipts.create', compact('warehouses', 'shipments'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'shipment_id' => ['required', 'exists:shipments,id'],
            'warehouse_id' => ['required', 'exists:warehouses,id'],
            'received_at' => ['required', 'date'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.item_id' => ['required', 'exists:items,id'],
            'items.*.qty_received' => ['required', 'numeric', 'min:0'],
            'items.*.koli_received' => ['nullable', 'numeric', 'min:0'],
        ]);

        DB::transaction(function () use ($validated) {
            $receipt = WarehouseReceipt::create([
                'shipment_id' => $validated['shipment_id'],
                'warehouse_id' => $validated['warehouse_id'],
                'code' => 'WR-'.now()->format('ymd').'-'.strtoupper(Str::random(4)),
                'received_at' => $validated['received_at'],
                'status' => 'posted',
            ]);
            foreach ($validated['items'] as $row) {
                $qty = (float) $row['qty_received'];
                if ($qty <= 0) continue;
                $ri = ReceiptItem::create([
                    'warehouse_receipt_id' => $receipt->id,
                    'item_id' => $row['item_id'],
                    'qty_received' => $qty,
                    'koli_received' => $row['koli_received'] ?? null,
                ]);
                FifoAllocator::allocateReceiptItem($ri);
            }
        });

        return redirect()->route('admin.procurement.receipts.index')->with('success', 'Penerimaan gudang berhasil diposting dan dialokasikan ke PO');
    }

    public function edit(WarehouseReceipt $receipt)
    {
        $warehouses = Warehouse::orderBy('name')->get();
        $shipments = Shipment::orderByDesc('id')->get();
        $receipt->load('items');
        return view('admin.procurement.receipts.edit', compact('receipt','warehouses','shipments'));
    }

    public function update(Request $request, WarehouseReceipt $receipt)
    {
        $validated = $request->validate([
            'shipment_id' => ['required', 'exists:shipments,id'],
            'warehouse_id' => ['required', 'exists:warehouses,id'],
            'received_at' => ['required', 'date'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.id' => ['nullable','integer'],
            'items.*.item_id' => ['required', 'exists:items,id'],
            'items.*.qty_received' => ['required', 'numeric', 'min:0'],
            'items.*.koli_received' => ['nullable', 'numeric', 'min:0'],
        ]);

        DB::transaction(function () use ($validated, $receipt) {
            $receipt->update([
                'shipment_id' => $validated['shipment_id'],
                'warehouse_id' => $validated['warehouse_id'],
                'received_at' => $validated['received_at'],
            ]);

            $keep = [];
            foreach ($validated['items'] as $row) {
                $qty = (float) ($row['qty_received'] ?? 0);
                if (!empty($row['id'])) {
                    $ri = ReceiptItem::where('warehouse_receipt_id', $receipt->id)->where('id', $row['id'])->firstOrFail();
                    $ri->update([
                        'item_id' => $row['item_id'],
                        'qty_received' => $qty,
                        'koli_received' => $row['koli_received'] ?? null,
                    ]);
                } else {
                    $ri = ReceiptItem::create([
                        'warehouse_receipt_id' => $receipt->id,
                        'item_id' => $row['item_id'],
                        'qty_received' => $qty,
                        'koli_received' => $row['koli_received'] ?? null,
                    ]);
                }
                // Reset allocations for this item then re-allocate
                $ri->allocations()->delete();
                FifoAllocator::allocateReceiptItem($ri);
                $keep[] = $ri->id;
            }
            ReceiptItem::where('warehouse_receipt_id', $receipt->id)->whereNotIn('id', $keep)->delete();
        });

        return redirect()->route('admin.procurement.receipts.index')->with('success', 'Penerimaan gudang berhasil diperbarui');
    }

    public function destroy(WarehouseReceipt $receipt)
    {
        $receipt->delete();
        return redirect()->route('admin.procurement.receipts.index')->with('success', 'Penerimaan gudang berhasil dihapus');
    }
}
