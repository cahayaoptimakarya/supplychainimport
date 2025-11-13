<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Item;
use App\Models\Shipment;
use App\Models\ShipmentItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ShipmentController extends Controller
{
    public function index()
    {
        return view('admin.procurement.shipments.index');
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

        $base = \DB::table('shipments as sh')
            ->leftJoin('shipment_items as si', 'si.shipment_id', '=', 'sh.id')
            ->groupBy('sh.id');

        $recordsTotal = \DB::table('shipments')->count();

        $filtered = (clone $base)
            ->selectRaw('sh.id')
            ->when($search, function($q) use ($search){
                $like = '%'.$search.'%';
                $q->where(function($w) use ($like){
                    $w->where('sh.code','like',$like)
                      ->orWhere('sh.container_no','like',$like)
                      ->orWhere('sh.pl_no','like',$like);
                });
            })
            ->when($status, fn($q)=> $q->where('sh.status', $status))
            ->when($dateFrom, fn($q)=> $q->whereDate('sh.etd','>=',$dateFrom))
            ->when($dateTo, fn($q)=> $q->whereDate('sh.etd','<=',$dateTo));
        $recordsFiltered = $filtered->get()->count();

        $dataQuery = (clone $base)
            ->selectRaw('sh.id, sh.code, sh.container_no, sh.pl_no, sh.etd, sh.eta, sh.status')
            ->selectRaw('COUNT(DISTINCT si.id) as items_count')
            ->selectRaw('COALESCE(SUM(si.cnt_expected),0) as cnt_expected_total')
            ->when($search, function($q) use ($search){
                $like = '%'.$search.'%';
                $q->where(function($w) use ($like){
                    $w->where('sh.code','like',$like)
                      ->orWhere('sh.container_no','like',$like)
                      ->orWhere('sh.pl_no','like',$like);
                });
            })
            ->when($status, fn($q)=> $q->where('sh.status', $status))
            ->when($dateFrom, fn($q)=> $q->whereDate('sh.etd','>=',$dateFrom))
            ->when($dateTo, fn($q)=> $q->whereDate('sh.etd','<=',$dateTo));

        // Ordering
        $orderReq = $request->input('order', []);
        $columnsReq = $request->input('columns', []);
        $columnsMap = [
            'id' => 'sh.id',
            'code' => 'sh.code',
            'container_no' => 'sh.container_no',
            'pl_no' => 'sh.pl_no',
            'etd' => 'sh.etd',
            'eta' => 'sh.eta',
            'status' => 'sh.status',
            'items_count' => 'items_count',
            'cnt_expected_total' => 'cnt_expected_total',
        ];
        foreach ($orderReq as $ord) {
            $idx = (int) ($ord['column'] ?? 0);
            $dir = ($ord['dir'] ?? 'asc') === 'desc' ? 'desc' : 'asc';
            $colData = (string) data_get($columnsReq, $idx.'.data', 'sh.id');
            $col = $columnsMap[$colData] ?? 'sh.id';
            $dataQuery->orderByRaw("$col $dir");
        }
        if (empty($orderReq)) {
            $dataQuery->orderBy('sh.id','desc');
        }

        $rows = $dataQuery->skip($start)->take($length)->get()->map(function($r){
            return [
                'id' => $r->id,
                'code' => $r->code,
                'container_no' => $r->container_no,
                'pl_no' => $r->pl_no,
                'etd' => $r->etd ? \Carbon\Carbon::parse($r->etd)->format('Y-m-d') : null,
                'eta' => $r->eta ? \Carbon\Carbon::parse($r->eta)->format('Y-m-d') : null,
                'status' => $r->status,
                'items_count' => (int) $r->items_count,
                'cnt_expected_total' => $r->cnt_expected_total,
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
        $code = 'SH-'.now()->format('ymd').'-'.strtoupper(Str::random(4));
        return view('admin.procurement.shipments.create', compact('items', 'code'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'container_no' => ['nullable', 'string', 'max:255'],
            'pl_no' => ['nullable', 'string', 'max:255'],
            'etd' => ['nullable', 'date'],
            'eta' => ['nullable', 'date'],
            'status' => ['required', 'string'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.item_id' => ['required', 'exists:items,id'],
            'items.*.qty_expected' => ['required', 'integer', 'min:1'],
            'items.*.cnt_expected' => ['nullable', 'numeric', 'min:0'],
            'items.*.pcs_cnt' => ['nullable', 'string', 'max:100'],
            'items.*.description' => ['nullable', 'string', 'max:500'],
        ]);

        DB::transaction(function () use ($validated, $request) {
            $code = $request->input('code');
            if (!$code) { $code = 'SH-'.now()->format('ymd').'-'.strtoupper(Str::random(4)); }
            while (\App\Models\Shipment::where('code', $code)->exists()) {
                $code = 'SH-'.now()->format('ymd').'-'.strtoupper(Str::random(4));
            }

            $shipment = Shipment::create([
                'code' => $code,
                'container_no' => $validated['container_no'] ?? null,
                'pl_no' => $validated['pl_no'] ?? null,
                'etd' => $validated['etd'] ?? null,
                'eta' => $validated['eta'] ?? null,
                'status' => $validated['status'] ?? 'planned',
            ]);
            foreach ($validated['items'] as $row) {
                $cntExpected = $row['cnt_expected'] ?? null;
                if ($cntExpected === '' || $cntExpected === null) {
                    $cntExpected = null;
                }
                $pcsCnt = array_key_exists('pcs_cnt', $row) ? trim((string) $row['pcs_cnt']) : null;
                if ($pcsCnt === '') {
                    $pcsCnt = null;
                }
                ShipmentItem::create([
                    'shipment_id' => $shipment->id,
                    'item_id' => $row['item_id'],
                    'qty_expected' => $row['qty_expected'],
                    'cnt_expected' => $cntExpected,
                    'pcs_cnt' => $pcsCnt,
                    'description' => $row['description'] ?? null,
                ]);
            }
        });

        return redirect()->route('admin.procurement.shipments.index')->with('success', 'Shipment berhasil dibuat');
    }

    public function edit(Shipment $shipment)
    {
        $items = Item::orderBy('name')->get();
        $shipment->load('items');
        return view('admin.procurement.shipments.edit', compact('shipment', 'items'));
    }

    public function update(Request $request, Shipment $shipment)
    {
        $validated = $request->validate([
            'container_no' => ['nullable', 'string', 'max:255'],
            'pl_no' => ['nullable', 'string', 'max:255'],
            'etd' => ['nullable', 'date'],
            'eta' => ['nullable', 'date'],
            'status' => ['required', 'string'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.id' => ['nullable', 'integer'],
            'items.*.item_id' => ['required', 'exists:items,id'],
            'items.*.qty_expected' => ['required', 'integer', 'min:1'],
            'items.*.cnt_expected' => ['nullable', 'numeric', 'min:0'],
            'items.*.pcs_cnt' => ['nullable', 'string', 'max:100'],
            'items.*.description' => ['nullable', 'string', 'max:500'],
        ]);

        DB::transaction(function () use ($validated, $shipment) {
            $shipment->update([
                'container_no' => $validated['container_no'] ?? null,
                'pl_no' => $validated['pl_no'] ?? null,
                'etd' => $validated['etd'] ?? null,
                'eta' => $validated['eta'] ?? null,
                'status' => $validated['status'] ?? 'planned',
            ]);

            $keep = [];
            foreach ($validated['items'] as $row) {
                $cntExpected = $row['cnt_expected'] ?? null;
                if ($cntExpected === '' || $cntExpected === null) {
                    $cntExpected = null;
                }
                $pcsCnt = array_key_exists('pcs_cnt', $row) ? trim((string) $row['pcs_cnt']) : null;
                if ($pcsCnt === '') {
                    $pcsCnt = null;
                }
                if (!empty($row['id'])) {
                    $si = ShipmentItem::where('shipment_id', $shipment->id)->where('id', $row['id'])->firstOrFail();
                    $si->update([
                        'item_id' => $row['item_id'],
                        'qty_expected' => $row['qty_expected'],
                        'cnt_expected' => $cntExpected,
                        'pcs_cnt' => $pcsCnt,
                        'description' => $row['description'] ?? null,
                    ]);
                    $keep[] = $si->id;
                } else {
                    $si = ShipmentItem::create([
                        'shipment_id' => $shipment->id,
                        'item_id' => $row['item_id'],
                        'qty_expected' => $row['qty_expected'],
                        'cnt_expected' => $cntExpected,
                        'pcs_cnt' => $pcsCnt,
                        'description' => $row['description'] ?? null,
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
