<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Item;
use App\Models\PoLine;
use App\Models\PurchaseOrder;
use App\Models\Supplier;
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
            ->leftJoin('suppliers as s', 's.id', '=', 'po.supplier_id')
            ->leftJoin('po_lines as pl', 'pl.purchase_order_id', '=', 'po.id')
            ->leftJoin('receipt_allocations as ra', 'ra.po_line_id', '=', 'pl.id')
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
                      ->orWhere('po.ref_no', 'like', $like)
                      ->orWhere('s.name', 'like', $like);
                });
            })
            ->when($dateFrom, fn($q)=> $q->whereDate('po.order_date', '>=', $dateFrom))
            ->when($dateTo, fn($q)=> $q->whereDate('po.order_date', '<=', $dateTo));

        // For status filtering we need aggregates; use having on computed fields
        $filtered = $filtered
            ->selectRaw(
                "
                COALESCE(SUM(pl.qty_ordered),0) as qty_ordered,
                COALESCE(SUM(ra.qty),0) as qty_fulfilled,
                GREATEST(0, COALESCE(SUM(pl.qty_ordered),0) - COALESCE(SUM(ra.qty),0)) as qty_open
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
            ->selectRaw('po.id, po.code, po.ref_no, po.order_date, s.name as supplier')
            ->selectRaw('COUNT(DISTINCT pl.id) as lines_count')
            ->selectRaw('COALESCE(SUM(pl.qty_ordered),0) as qty_ordered')
            ->selectRaw('COALESCE(SUM(pl.koli_ordered),0) as koli_ordered')
            ->selectRaw('COALESCE(SUM(ra.qty),0) as qty_fulfilled')
            ->selectRaw('GREATEST(0, COALESCE(SUM(pl.qty_ordered),0) - COALESCE(SUM(ra.qty),0)) as qty_open')
            ->selectRaw("CASE WHEN GREATEST(0, COALESCE(SUM(pl.qty_ordered),0) - COALESCE(SUM(ra.qty),0)) <= 0 THEN 'fulfilled' WHEN COALESCE(SUM(ra.qty),0) > 0 THEN 'partial' ELSE 'open' END as status")
            ->when($search, function($q) use ($search) {
                $like = '%'.$search.'%';
                $q->where(function($w) use ($like){
                    $w->where('po.code', 'like', $like)
                      ->orWhere('po.ref_no', 'like', $like)
                      ->orWhere('s.name', 'like', $like);
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
            'supplier' => 'supplier',
            'order_date' => 'po.order_date',
            'lines_count' => 'lines_count',
            'qty_ordered' => 'qty_ordered',
            'koli_ordered' => 'koli_ordered',
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
                'supplier' => $r->supplier,
                'order_date' => $r->order_date ? \Carbon\Carbon::parse($r->order_date)->format('Y-m-d') : null,
                'lines_count' => (int) $r->lines_count,
                'qty_ordered' => (int) $r->qty_ordered,
                'koli_ordered' => $r->koli_ordered, // may be null
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
        $suppliers = Supplier::orderBy('name')->get();
        $items = Item::orderBy('name')->get();
        $code = 'PO-'.now()->format('ymd').'-'.strtoupper(Str::random(4));
        return view('admin.procurement.purchase-orders.create', compact('suppliers', 'items', 'code'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'supplier_id' => ['required', 'exists:suppliers,id'],
            'order_date' => ['required', 'date'],
            'ref_no' => ['nullable', 'string', 'max:255'],
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.item_id' => ['required', 'exists:items,id'],
            'lines.*.qty_ordered' => ['required', 'integer', 'min:1'],
            'lines.*.koli_ordered' => ['nullable', 'numeric', 'min:0'],
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
                'supplier_id' => $validated['supplier_id'],
                'code' => $code,
                'order_date' => $validated['order_date'],
                'ref_no' => $validated['ref_no'] ?? null,
                'status' => 'open',
            ]);
            foreach ($validated['lines'] as $line) {
                PoLine::create([
                    'purchase_order_id' => $po->id,
                    'item_id' => $line['item_id'],
                    'qty_ordered' => $line['qty_ordered'],
                    'koli_ordered' => $line['koli_ordered'] ?? null,
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
            'lines.*.qty_ordered' => ['required', 'integer', 'min:1'],
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
                        'koli_ordered' => $line['koli_ordered'] ?? null,
                        'notes' => $line['notes'] ?? null,
                    ]);
                    $keepIds[] = $pl->id;
                } else {
                    $pl = PoLine::create([
                        'purchase_order_id' => $purchase_order->id,
                        'item_id' => $line['item_id'],
                        'qty_ordered' => $line['qty_ordered'],
                        'koli_ordered' => $line['koli_ordered'] ?? null,
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
        $purchase_order->load(['supplier', 'lines.item']);
        $ordered = (float) $purchase_order->lines->sum('qty_ordered');
        $fulfilled = 0.0;
        foreach ($purchase_order->lines as $l) { $fulfilled += (float) $l->fulfilled_qty; }
        $open = max(0.0, $ordered - $fulfilled);
        $derivedStatus = $open <= 0 ? 'fulfilled' : ($fulfilled > 0 ? 'partial' : 'open');

        return view('admin.procurement.purchase-orders.show', [
            'po' => $purchase_order,
            'totals' => [
                'qty_ordered' => $ordered,
                'qty_fulfilled' => $fulfilled,
                'qty_open' => $open,
                'status' => $derivedStatus,
            ],
        ]);
    }
}
