<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Item;
use App\Models\PoLine;
use App\Models\PurchaseOrder;
use App\Services\ProcurementFulfillmentReport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PurchaseOrderController extends Controller
{
    public function index()
    {
        return view('admin.procurement.purchase-orders.index');
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

        $base = \DB::table('purchase_orders as po')
            ->leftJoin('po_lines as pl', 'pl.purchase_order_id', '=', 'po.id')
            ->groupBy('po.id');

        // recordsTotal (total rows before filtering)
        $recordsTotal = \DB::table('purchase_orders')->count();

        // Apply filters for recordsFiltered
        $filtered = (clone $base)
            ->selectRaw('po.id')
            ->when($search, function($q) use ($search) {
                $like = '%'.$search.'%';
                $q->where(function($w) use ($like){
                    $w->where('po.code', 'like', $like)
                      ->orWhere('po.ref_no', 'like', $like);
                });
            })
            ->when($dateFrom, fn($q)=> $q->whereDate('po.order_date', '>=', $dateFrom))
            ->when($dateTo, fn($q)=> $q->whereDate('po.order_date', '<=', $dateTo));

        // For status filtering we need aggregates; use having on computed fields
        $filtered = $filtered
            ->selectRaw(
                "
                COALESCE(SUM(pl.qty_ordered),0) as qty_ordered,
                COALESCE(SUM(pl.qty_fulfilled),0) as qty_fulfilled,
                COALESCE(SUM(pl.qty_remaining),0) as qty_open
                "
            );

        if ($status === 'fulfilled') {
            $filtered->havingRaw('qty_open <= 0');
        } elseif ($status === 'partial') {
            $filtered->havingRaw('qty_open > 0 AND qty_fulfilled > 0');
        } elseif ($status === 'open') {
            $filtered->havingRaw('qty_fulfilled = 0');
        }

        $recordsFiltered = $filtered->get()->count();

        // Main data query with pagination and ordering
        $dataQuery = (clone $base)
            ->selectRaw('po.id, po.code, po.ref_no, po.order_date')
            ->selectRaw('COUNT(DISTINCT pl.id) as lines_count')
            ->selectRaw('COALESCE(SUM(pl.qty_ordered),0) as qty_ordered')
            ->selectRaw('COALESCE(SUM(pl.cnt_ordered),0) as cnt_ordered')
            ->selectRaw('COALESCE(SUM(pl.qty_fulfilled),0) as qty_fulfilled')
            ->selectRaw('COALESCE(SUM(pl.qty_remaining),0) as qty_open')
            ->selectRaw("CASE WHEN COALESCE(SUM(pl.qty_remaining),0) <= 0 THEN 'fulfilled' WHEN COALESCE(SUM(pl.qty_fulfilled),0) > 0 THEN 'partial' ELSE 'open' END as status")
            ->when($search, function($q) use ($search) {
                $like = '%'.$search.'%';
                $q->where(function($w) use ($like){
                    $w->where('po.code', 'like', $like)
                      ->orWhere('po.ref_no', 'like', $like);
                });
            })
            ->when($dateFrom, fn($q)=> $q->whereDate('po.order_date', '>=', $dateFrom))
            ->when($dateTo, fn($q)=> $q->whereDate('po.order_date', '<=', $dateTo));

        if ($status === 'fulfilled') {
            $dataQuery->havingRaw('qty_open <= 0');
        } elseif ($status === 'partial') {
            $dataQuery->havingRaw('qty_open > 0 AND qty_fulfilled > 0');
        } elseif ($status === 'open') {
            $dataQuery->havingRaw('qty_fulfilled = 0');
        }

        // Ordering
        $orderReq = $request->input('order', []);
        $columnsReq = $request->input('columns', []);
        $columnsMap = [
            'id' => 'po.id',
            'code' => 'po.code',
            'ref_no' => 'po.ref_no',
            'order_date' => 'po.order_date',
            'lines_count' => 'lines_count',
            'qty_ordered' => 'qty_ordered',
            'cnt_ordered' => 'cnt_ordered',
            'qty_fulfilled' => 'qty_fulfilled',
            'qty_open' => 'qty_open',
            'status' => 'status',
        ];
        foreach ($orderReq as $ord) {
            $idx = (int) ($ord['column'] ?? 0);
            $dir = ($ord['dir'] ?? 'asc') === 'desc' ? 'desc' : 'asc';
            $colData = (string) data_get($columnsReq, $idx.'.data', 'order_date');
            $col = $columnsMap[$colData] ?? 'po.order_date';
            $dataQuery->orderByRaw("$col $dir");
        }
        if (empty($orderReq)) {
            $dataQuery->orderBy('po.order_date', 'desc');
        }

        $rows = $dataQuery->skip($start)->take($length)->get()->map(function($r){
            return [
                'id' => $r->id,
                'code' => $r->code,
                'ref_no' => $r->ref_no,
                'order_date' => $r->order_date ? \Carbon\Carbon::parse($r->order_date)->format('Y-m-d') : null,
                'lines_count' => (int) $r->lines_count,
                'qty_ordered' => (int) $r->qty_ordered,
                'cnt_ordered' => $r->cnt_ordered, // may be null
                'qty_fulfilled' => (int) $r->qty_fulfilled,
                'qty_open' => (int) $r->qty_open,
                'status' => $r->status,
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
        $items = Item::orderBy('name')->get();
        $code = 'PO-'.now()->format('ymd').'-'.strtoupper(Str::random(4));
        return view('admin.procurement.purchase-orders.create', compact('items', 'code'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'order_date' => ['required', 'date'],
            'ref_no' => ['nullable', 'string', 'max:255'],
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.item_id' => ['required', 'exists:items,id'],
            'lines.*.qty_ordered' => ['required', 'integer', 'min:1'],
            'lines.*.cnt_ordered' => ['nullable', 'numeric', 'min:0'],
            'lines.*.pcs_cnt' => ['nullable', 'string', 'max:100'],
            'lines.*.notes' => ['nullable', 'string'],
        ]);

        DB::transaction(function () use ($validated, $request) {
            // Prefer provided code from form; ensure uniqueness
            $code = $request->input('code');
            if (!$code) { $code = 'PO-'.now()->format('ymd').'-'.strtoupper(Str::random(4)); }
            while (PurchaseOrder::where('code', $code)->exists()) {
                $code = 'PO-'.now()->format('ymd').'-'.strtoupper(Str::random(4));
            }

            $po = PurchaseOrder::create([
                'code' => $code,
                'order_date' => $validated['order_date'],
                'ref_no' => $validated['ref_no'] ?? null,
                'status' => 'open',
            ]);
            foreach ($validated['lines'] as $line) {
                $cntOrdered = $line['cnt_ordered'] ?? null;
                if ($cntOrdered === '' || $cntOrdered === null) {
                    $cntOrdered = null;
                }
                $pcsCnt = array_key_exists('pcs_cnt', $line) ? trim((string) $line['pcs_cnt']) : null;
                if ($pcsCnt === '') {
                    $pcsCnt = null;
                }
                PoLine::create([
                    'purchase_order_id' => $po->id,
                    'item_id' => $line['item_id'],
                    'qty_ordered' => $line['qty_ordered'],
                    'cnt_ordered' => $cntOrdered,
                    'pcs_cnt' => $pcsCnt,
                    'notes' => $line['notes'] ?? null,
                ]);
            }
        });

        return redirect()->route('admin.procurement.purchase-orders.index')->with('success', 'PO berhasil dibuat');
    }

    public function edit(PurchaseOrder $purchase_order)
    {
        $items = Item::orderBy('name')->get();
        $purchase_order->load('lines');
        return view('admin.procurement.purchase-orders.edit', [
            'po' => $purchase_order,
            'items' => $items,
        ]);
    }

    public function update(Request $request, PurchaseOrder $purchase_order)
    {
        $validated = $request->validate([
            'order_date' => ['required', 'date'],
            'ref_no' => ['nullable', 'string', 'max:255'],
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.id' => ['nullable', 'integer'],
            'lines.*.item_id' => ['required', 'exists:items,id'],
            'lines.*.qty_ordered' => ['required', 'integer', 'min:1'],
            'lines.*.cnt_ordered' => ['nullable', 'numeric', 'min:0'],
            'lines.*.pcs_cnt' => ['nullable', 'string', 'max:100'],
            'lines.*.notes' => ['nullable', 'string'],
        ]);

        DB::transaction(function () use ($validated, $purchase_order) {
            $purchase_order->update([
                'order_date' => $validated['order_date'],
                'ref_no' => $validated['ref_no'] ?? null,
            ]);

            $keepIds = [];
            foreach ($validated['lines'] as $line) {
                $cntOrdered = $line['cnt_ordered'] ?? null;
                if ($cntOrdered === '' || $cntOrdered === null) {
                    $cntOrdered = null;
                }
                $pcsCnt = array_key_exists('pcs_cnt', $line) ? trim((string) $line['pcs_cnt']) : null;
                if ($pcsCnt === '') {
                    $pcsCnt = null;
                }
                if (!empty($line['id'])) {
                    $pl = PoLine::where('purchase_order_id', $purchase_order->id)->where('id', $line['id'])->firstOrFail();
                    $pl->update([
                        'item_id' => $line['item_id'],
                        'qty_ordered' => $line['qty_ordered'],
                        'cnt_ordered' => $cntOrdered,
                        'pcs_cnt' => $pcsCnt,
                        'notes' => $line['notes'] ?? null,
                    ]);
                    $keepIds[] = $pl->id;
                } else {
                    $pl = PoLine::create([
                        'purchase_order_id' => $purchase_order->id,
                        'item_id' => $line['item_id'],
                        'qty_ordered' => $line['qty_ordered'],
                        'cnt_ordered' => $cntOrdered,
                        'pcs_cnt' => $pcsCnt,
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

    public function show(PurchaseOrder $purchase_order)
    {
        $purchase_order->load(['lines.item']);

        $ordered = (float) $purchase_order->lines->sum(fn($line) => (float) $line->qty_ordered);
        $fulfilled = (float) $purchase_order->lines->sum(fn($line) => (float) $line->fulfilled_qty);
        $open = (float) $purchase_order->lines->sum(fn($line) => (float) $line->remaining_qty);

        $derivedStatus = match (true) {
            $ordered <= 0 => 'open',
            $open <= 0.00001 => 'fulfilled',
            $fulfilled > 0 => 'partial',
            default => 'open',
        };

        return view('admin.procurement.purchase-orders.show', [
            'po' => $purchase_order,
            'totals' => [
                'qty_ordered' => $ordered,
                'qty_fulfilled' => $fulfilled,
                'qty_remaining' => $open,
                'status' => $derivedStatus,
            ],
        ]);
    }

    public function report()
    {
        return view('admin.procurement.purchase-orders.report');
    }

    public function reportData(Request $request, ProcurementFulfillmentReport $report)
    {
        $draw = (int) $request->input('draw', 1);
        $start = max(0, (int) $request->input('start', 0));
        $length = (int) $request->input('length', 10);
        $search = (string) data_get($request->input('search', []), 'value', '');
        $status = $request->input('status');
        $dateFrom = $request->input('date_from');
        $dateTo = $request->input('date_to');

        $purchaseOrders = PurchaseOrder::select('id', 'code', 'ref_no', 'order_date', 'status')
            ->with([
                'lines' => function ($query) {
                    $query->select('id', 'purchase_order_id', 'item_id', 'qty_ordered', 'qty_fulfilled');
                },
                'lines.item:id,sku,name',
            ])
            ->when($status, fn ($q) => $q->where('status', $status))
            ->when($dateFrom, fn ($q) => $q->whereDate('order_date', '>=', $dateFrom))
            ->when($dateTo, fn ($q) => $q->whereDate('order_date', '<=', $dateTo))
            ->get();

        if ($purchaseOrders->isEmpty()) {
            return response()->json([
                'draw' => $draw,
                'recordsTotal' => 0,
                'recordsFiltered' => 0,
                'data' => [],
            ]);
        }

        $rows = $report->build($purchaseOrders);
        $recordsTotal = $rows->count();

        if ($search !== '') {
            $keyword = mb_strtolower($search);
            $rows = $rows->filter(function ($row) use ($keyword) {
                return str_contains(mb_strtolower((string) ($row['sku'] ?? '')), $keyword)
                    || str_contains(mb_strtolower((string) ($row['item_name'] ?? '')), $keyword);
            })->values();
        }

        $recordsFiltered = $rows->count();

        $orderReq = $request->input('order', []);
        $columnsReq = $request->input('columns', []);
        $columnsMap = [
            'sku' => 'sku',
            'item_name' => 'item_name',
            'qty_ordered' => 'qty_ordered',
            'qty_fulfilled' => 'qty_fulfilled',
            'belum_dikirim' => 'belum_dikirim',
            'masih_dijalan' => 'masih_dijalan',
            'di_pelabuhan' => 'di_pelabuhan',
            'diterima_gudang' => 'diterima_gudang',
            'status' => 'status',
        ];

        if (!empty($orderReq)) {
            foreach (array_reverse($orderReq) as $ord) {
                $idx = (int) ($ord['column'] ?? 0);
                $dirDesc = (($ord['dir'] ?? 'asc') === 'desc');
                $colData = (string) data_get($columnsReq, $idx.'.data', 'sku');
                $field = $columnsMap[$colData] ?? 'sku';
                $rows = $rows->sortBy(fn ($row) => $row[$field] ?? null, SORT_REGULAR, $dirDesc)->values();
            }
        } else {
            $rows = $rows->sortBy('sku')->values();
        }

        if ($length <= 0) {
            $length = max(1, $recordsFiltered);
        }

        $paged = $rows->slice($start, $length)->values()->map(function ($row) {
            $row['qty_ordered'] = (float) ($row['qty_ordered'] ?? 0);
            $row['qty_fulfilled'] = (float) ($row['qty_fulfilled'] ?? 0);
            $row['belum_dikirim'] = (float) ($row['belum_dikirim'] ?? 0);
            $row['masih_dijalan'] = (float) ($row['masih_dijalan'] ?? 0);
            $row['di_pelabuhan'] = (float) ($row['di_pelabuhan'] ?? 0);
            $row['diterima_gudang'] = (float) ($row['diterima_gudang'] ?? 0);
            return $row;
        })->all();

        return response()->json([
            'draw' => $draw,
            'recordsTotal' => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'data' => $paged,
        ]);
    }

    public function reportItemDetail(Item $item)
    {
        $lines = PoLine::select('id', 'purchase_order_id', 'item_id', 'qty_ordered', 'qty_fulfilled')
            ->with(['purchaseOrder:id,code,order_date'])
            ->where('item_id', $item->id)
            ->orderByDesc('purchase_order_id')
            ->get()
            ->map(function (PoLine $line) {
                $ordered = (float) $line->qty_ordered;
                $fulfilled = (float) $line->qty_fulfilled;
                $remaining = max(0.0, $ordered - $fulfilled);

                if ($ordered <= 0) {
                    $status = 'open';
                } elseif ($remaining <= 0.00001) {
                    $status = 'fulfilled';
                } elseif ($fulfilled > 0) {
                    $status = 'partial';
                } else {
                    $status = 'open';
                }

                return [
                    'po_code' => optional($line->purchaseOrder)->code,
                    'order_date' => optional(optional($line->purchaseOrder)->order_date)->format('Y-m-d'),
                    'qty_ordered' => $ordered,
                    'qty_fulfilled' => $fulfilled,
                    'qty_remaining' => $remaining,
                    'status' => $status,
                ];
            })
            ->filter(fn ($row) => $row['qty_remaining'] > 0)
            ->values();

        return response()->json([
            'item' => [
                'id' => $item->id,
                'sku' => $item->sku,
                'name' => $item->name,
            ],
            'lines' => $lines,
        ]);
    }
}
