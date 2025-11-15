<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Item;
use App\Models\Shipment;
use App\Models\Warehouse;
use App\Models\WarehouseReceipt;
use App\Models\ReceiptItem;
use App\Models\PoLine;
use App\Services\FifoAllocator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class WarehouseReceiptController extends Controller
{
    public function index()
    {
        return view('admin.procurement.receipts.index');
    }

    public function data(Request $request)
    {
        $draw = (int) $request->input('draw', 1);
        $start = (int) $request->input('start', 0);
        $length = (int) $request->input('length', 10);
        $search = (string) data_get($request->input('search', []), 'value', '');
        $status = $request->input('status');
        $dateFrom = $request->input('date_from');
        $dateTo = $request->input('date_to');

        $base = \DB::table('warehouse_receipts as wr')
            ->leftJoin('shipments as sh', 'sh.id', '=', 'wr.shipment_id')
            ->leftJoin('warehouses as wh', 'wh.id', '=', 'wr.warehouse_id')
            ->leftJoin('receipt_items as ri', 'ri.warehouse_receipt_id', '=', 'wr.id')
            ->groupBy('wr.id');

        $recordsTotal = \DB::table('warehouse_receipts')->count();

        $filtered = (clone $base)
            ->selectRaw('wr.id')
            ->when($search, function($q) use ($search){
                $like = '%'.$search.'%';
                $q->where(function($w) use ($like){
                    $w->where('wr.code','like',$like)
                      ->orWhere('sh.container_no','like',$like)
                      ->orWhere('wh.name','like',$like);
                });
            })
            ->when($status, fn($q)=> $q->where('wr.status', $status))
            ->when($dateFrom, fn($q)=> $q->where('wr.received_at','>=',$dateFrom))
            ->when($dateTo, fn($q)=> $q->where('wr.received_at','<=',$dateTo));
        $recordsFiltered = $filtered->get()->count();

        $dataQuery = (clone $base)
            ->selectRaw('wr.id, wr.code, wr.received_at, wr.status')
            ->selectRaw("COALESCE(sh.container_no, CONCAT('#', sh.id)) as shipment")
            ->selectRaw('wh.name as warehouse')
            ->selectRaw('COALESCE(SUM(ri.qty_received),0) as qty_total')
            ->selectRaw('COALESCE(SUM(ri.cnt_received),0) as cnt_total')
            ->when($search, function($q) use ($search){
                $like = '%'.$search.'%';
                $q->where(function($w) use ($like){
                    $w->where('wr.code','like',$like)
                      ->orWhere('sh.container_no','like',$like)
                      ->orWhere('wh.name','like',$like);
                });
            })
            ->when($status, fn($q)=> $q->where('wr.status', $status))
            ->when($dateFrom, fn($q)=> $q->where('wr.received_at','>=',$dateFrom))
            ->when($dateTo, fn($q)=> $q->where('wr.received_at','<=',$dateTo));

        // Ordering
        $orderReq = $request->input('order', []);
        $columnsReq = $request->input('columns', []);
        $columnsMap = [
            'id' => 'wr.id',
            'code' => 'wr.code',
            'shipment' => 'shipment',
            'warehouse' => 'warehouse',
            'received_at' => 'wr.received_at',
            'status' => 'wr.status',
            'qty_total' => 'qty_total',
            'cnt_total' => 'cnt_total',
        ];
        foreach ($orderReq as $ord) {
            $idx = (int) ($ord['column'] ?? 0);
            $dir = ($ord['dir'] ?? 'asc') === 'desc' ? 'desc' : 'asc';
            $colData = (string) data_get($columnsReq, $idx.'.data', 'wr.id');
            $col = $columnsMap[$colData] ?? 'wr.id';
            $dataQuery->orderByRaw("$col $dir");
        }
        if (empty($orderReq)) {
            $dataQuery->orderBy('wr.id','desc');
        }

        $rows = $dataQuery->skip($start)->take($length)->get()->map(function($r){
            return [
                'id' => $r->id,
                'code' => $r->code,
                'shipment' => $r->shipment ?? '-',
                'warehouse' => $r->warehouse,
                'received_at' => $r->received_at ? \Carbon\Carbon::parse($r->received_at)->format('Y-m-d H:i') : null,
                'status' => $r->status,
                'qty_total' => (int) $r->qty_total,
                'cnt_total' => $r->cnt_total,
            ];
        });

        return response()->json([
            'draw' => $draw,
            'recordsTotal' => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'data' => $rows,
        ]);
    }

    public function create()
    {
        $warehouses = Warehouse::orderBy('name')->get();
        $shipments = Shipment::with('items')
            ->whereDoesntHave('receipts')
            ->orderByDesc('id')
            ->get();
        $code = 'WR-'.now()->format('ymd').'-'.strtoupper(Str::random(4));
        return view('admin.procurement.receipts.create', compact('warehouses', 'shipments', 'code'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'shipment_id' => ['required', 'exists:shipments,id'],
            'warehouse_id' => ['required', 'exists:warehouses,id'],
            'received_at' => ['required', 'date'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.item_id' => ['required', 'exists:items,id'],
            'items.*.qty_received' => ['required', 'integer', 'min:0'],
            'items.*.cnt_received' => ['nullable', 'numeric', 'min:0'],
            'items.*.pcs_cnt' => ['nullable', 'string', 'max:100'],
            'items.*.description' => ['nullable', 'string', 'max:500'],
        ]);

        DB::transaction(function () use ($validated, $request) {
            $code = $request->input('code');
            if (!$code) { $code = 'WR-'.now()->format('ymd').'-'.strtoupper(Str::random(4)); }
            while (\App\Models\WarehouseReceipt::where('code', $code)->exists()) {
                $code = 'WR-'.now()->format('ymd').'-'.strtoupper(Str::random(4));
            }

            $receipt = WarehouseReceipt::create([
                'shipment_id' => $validated['shipment_id'],
                'warehouse_id' => $validated['warehouse_id'],
                'code' => $code,
                'received_at' => $validated['received_at'],
                'status' => 'posted',
            ]);
            foreach ($validated['items'] as $row) {
                $qty = (float) $row['qty_received'];
                if ($qty <= 0) continue;
                $cntReceived = $row['cnt_received'] ?? null;
                if ($cntReceived === '' || $cntReceived === null) {
                    $cntReceived = null;
                }
                $pcsCnt = array_key_exists('pcs_cnt', $row) ? trim((string) $row['pcs_cnt']) : null;
                if ($pcsCnt === '') {
                    $pcsCnt = null;
                }
                $ri = ReceiptItem::create([
                    'warehouse_receipt_id' => $receipt->id,
                    'item_id' => $row['item_id'],
                    'qty_received' => $qty,
                    'cnt_received' => $cntReceived,
                    'pcs_cnt' => $pcsCnt,
                    'description' => $row['description'] ?? null,
                ]);
                FifoAllocator::allocateReceiptItem($ri);
            }
        });

        return redirect()->route('admin.procurement.receipts.index')->with('success', 'Penerimaan gudang berhasil diposting dan dialokasikan ke PO');
    }

    public function edit(WarehouseReceipt $receipt)
    {
        $warehouses = Warehouse::orderBy('name')->get();
        $shipments = Shipment::with('items')
            ->where(function ($q) use ($receipt) {
                $q->whereDoesntHave('receipts')
                  ->orWhere('id', $receipt->shipment_id);
            })
            ->orderByDesc('id')
            ->get();
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
            'items.*.qty_received' => ['required', 'integer', 'min:0'],
            'items.*.cnt_received' => ['nullable', 'numeric', 'min:0'],
            'items.*.pcs_cnt' => ['nullable', 'string', 'max:100'],
            'items.*.description' => ['nullable', 'string', 'max:500'],
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
                $cntReceived = $row['cnt_received'] ?? null;
                if ($cntReceived === '' || $cntReceived === null) {
                    $cntReceived = null;
                }
                $pcsCnt = array_key_exists('pcs_cnt', $row) ? trim((string) $row['pcs_cnt']) : null;
                if ($pcsCnt === '') {
                    $pcsCnt = null;
                }
                $previousLineIds = [];
                if (!empty($row['id'])) {
                    $ri = ReceiptItem::where('warehouse_receipt_id', $receipt->id)->where('id', $row['id'])->firstOrFail();
                    $previousLineIds = $ri->allocations()->pluck('po_line_id')->all();
                    $ri->update([
                        'item_id' => $row['item_id'],
                        'qty_received' => $qty,
                        'cnt_received' => $cntReceived,
                        'pcs_cnt' => $pcsCnt,
                        'description' => $row['description'] ?? null,
                    ]);
                } else {
                    $ri = ReceiptItem::create([
                        'warehouse_receipt_id' => $receipt->id,
                        'item_id' => $row['item_id'],
                        'qty_received' => $qty,
                        'cnt_received' => $cntReceived,
                        'pcs_cnt' => $pcsCnt,
                        'description' => $row['description'] ?? null,
                    ]);
                }
                // Reset allocations for this item then re-allocate
                if (!empty($previousLineIds)) {
                    $ri->allocations()->delete();
                    PoLine::whereIn('id', $previousLineIds)->get()->each->refreshFulfillmentMetrics();
                } else {
                    $ri->allocations()->delete();
                }
                FifoAllocator::allocateReceiptItem($ri, $previousLineIds);
                $keep[] = $ri->id;
            }
            $itemsToDelete = ReceiptItem::where('warehouse_receipt_id', $receipt->id)
                ->whereNotIn('id', $keep)
                ->with('allocations')
                ->get();

            $linesToRefresh = [];
            foreach ($itemsToDelete as $item) {
                $linesToRefresh = array_merge($linesToRefresh, $item->allocations->pluck('po_line_id')->all());
                $item->allocations()->delete();
                $item->delete();
            }

            if (!empty($linesToRefresh)) {
                PoLine::whereIn('id', array_unique($linesToRefresh))->get()->each->refreshFulfillmentMetrics();
            }
        });

        return redirect()->route('admin.procurement.receipts.index')->with('success', 'Penerimaan gudang berhasil diperbarui');
    }

    public function destroy(WarehouseReceipt $receipt)
    {
        DB::transaction(function () use ($receipt) {
            $affectedLineIds = $receipt->items()
                ->with('allocations:po_line_id,receipt_item_id')
                ->get()
                ->flatMap(fn($item) => $item->allocations->pluck('po_line_id'))
                ->filter()
                ->unique()
                ->values()
                ->all();

            $receipt->delete();

            if (!empty($affectedLineIds)) {
                PoLine::whereIn('id', $affectedLineIds)->get()->each->refreshFulfillmentMetrics();
            }
        });
        return redirect()->route('admin.procurement.receipts.index')->with('success', 'Penerimaan gudang berhasil dihapus');
    }

    public function shipmentItems(Shipment $shipment)
    {
        dd($shipment);
        $items = $shipment->items()
            ->with('item:id,sku,name')
            ->get()
            ->map(function ($row) {
                $label = optional($row->item)->sku
                    ? ($row->item->sku . ' - ' . $row->item->name)
                    : optional($row->item)->name;
                return [
                    'item_id' => $row->item_id,
                    'item_label' => $label,
                    'qty_expected' => (float) $row->qty_expected,
                    'cnt_expected' => $row->cnt_expected,
                    'pcs_cnt' => $row->pcs_cnt,
                ];
            })
            ->values();

        return response()->json([
            'items' => $items,
        ]);
    }
}
