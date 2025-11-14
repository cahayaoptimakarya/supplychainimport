<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Warehouse;
use App\Services\ItemLogisticsSnapshot;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class ProcurementReportController extends Controller
{
    private const SHIPMENT_STATUS_LABELS = [
        'planned' => 'Direncanakan',
        'ready_at_port' => 'Siap di Pelabuhan',
        'on_board' => 'Berangkat',
        'arrived' => 'Tiba di Pelabuhan',
        'under_bc' => 'Under BC',
        'released' => 'Dirilis',
        'delivered_to_main_wh' => 'Tiba di WH Utama',
        'received' => 'Diterima',
    ];

    private const SHIPMENT_COMPLETE_STATUSES = [
        'released',
        'delivered_to_main_wh',
        'received',
    ];

    public function itemLogistics()
    {
        return view('admin.procurement.reports.item-logistics');
    }

    public function itemLogisticsData(ItemLogisticsSnapshot $snapshot)
    {
        return response()->json($snapshot->build());
    }

    public function shipmentsOverview()
    {
        return view('admin.procurement.reports.shipments-overview');
    }

    public function shipmentsOverviewData(Request $request)
    {
        $shipmentsQuery = DB::table('shipments as sh');
        $shipmentsQuery = $this->applyShipmentFilters($shipmentsQuery, $request);

        $totalShipments = (clone $shipmentsQuery)->count();
        $statusBreakdown = (clone $shipmentsQuery)
            ->select('sh.status', DB::raw('COUNT(*) as total'))
            ->groupBy('sh.status')
            ->orderByDesc('total')
            ->get()
            ->map(function ($row) {
                $status = $row->status;
                return [
                    'status' => $status,
                    'label' => self::SHIPMENT_STATUS_LABELS[$status] ?? ucfirst(str_replace('_', ' ', $status)),
                    'total' => (int) $row->total,
                ];
            })
            ->values();

        $shipmentLines = DB::table('shipments as sh')
            ->leftJoin('shipment_items as si', 'si.shipment_id', '=', 'sh.id');
        $shipmentLines = $this->applyShipmentFilters($shipmentLines, $request);

        $totals = (clone $shipmentLines)
            ->selectRaw('COUNT(DISTINCT sh.id) as shipments')
            ->selectRaw('COALESCE(SUM(si.qty_expected), 0) as qty_expected')
            ->selectRaw('COALESCE(SUM(si.cnt_expected), 0) as cnt_expected')
            ->selectRaw('COUNT(si.id) as items_count')
            ->first();
        if (!$totals) {
            $totals = (object) [
                'shipments' => 0,
                'qty_expected' => 0,
                'cnt_expected' => 0,
                'items_count' => 0,
            ];
        }

        $today = Carbon::today();

        $etaLateBreakdown = (clone $shipmentLines)
            ->select('sh.id', 'sh.code', 'sh.container_no', 'sh.eta', 'sh.status')
            ->groupBy('sh.id', 'sh.code', 'sh.container_no', 'sh.eta', 'sh.status')
            ->get()
            ->map(function ($row) use ($today) {
                $eta = $row->eta ? Carbon::parse($row->eta) : null;
                $isLate = $eta
                    ? ($eta->isBefore($today) && !in_array($row->status, self::SHIPMENT_COMPLETE_STATUSES, true))
                    : false;
                return [
                    'id' => $row->id,
                    'code' => $row->code,
                    'container_no' => $row->container_no,
                    'eta' => $eta ? $eta->toDateString() : null,
                    'is_late' => $isLate,
                ];
            })
            ->filter(fn ($row) => $row['is_late'])
            ->values();

        $rows = (clone $shipmentLines)
            ->select('sh.id', 'sh.code', 'sh.container_no', 'sh.pl_no', 'sh.status', 'sh.etd', 'sh.eta', 'sh.created_at')
            ->selectRaw('COUNT(si.id) as lines')
            ->selectRaw('COALESCE(SUM(si.qty_expected), 0) as qty_expected_total')
            ->selectRaw('COALESCE(SUM(si.cnt_expected), 0) as cnt_expected_total')
            ->groupBy('sh.id', 'sh.code', 'sh.container_no', 'sh.pl_no', 'sh.status', 'sh.etd', 'sh.eta', 'sh.created_at')
            ->orderByRaw('COALESCE(sh.etd, sh.created_at) desc')
            ->limit(250)
            ->get()
            ->map(function ($row) use ($today) {
                $eta = $row->eta ? Carbon::parse($row->eta) : null;
                $etd = $row->etd ? Carbon::parse($row->etd) : null;
                $isLate = $eta
                    ? ($eta->isBefore($today) && !in_array($row->status, self::SHIPMENT_COMPLETE_STATUSES, true))
                    : false;
                return [
                    'id' => $row->id,
                    'code' => $row->code,
                    'container_no' => $row->container_no,
                    'pl_no' => $row->pl_no,
                    'status' => $row->status,
                    'status_label' => self::SHIPMENT_STATUS_LABELS[$row->status] ?? ucfirst(str_replace('_', ' ', (string) $row->status)),
                    'etd' => $etd ? $etd->toDateString() : null,
                    'eta' => $eta ? $eta->toDateString() : null,
                    'qty_expected' => (float) $row->qty_expected_total,
                    'cnt_expected' => (float) $row->cnt_expected_total,
                    'items_count' => (int) $row->lines,
                    'is_late' => $isLate,
                ];
            })
            ->values();

        return response()->json([
            'summary' => [
                'total_shipments' => $totalShipments,
                'lines' => (int) ($totals->items_count ?? 0),
                'qty_expected' => (float) ($totals->qty_expected ?? 0),
                'cnt_expected' => (float) ($totals->cnt_expected ?? 0),
                'status_breakdown' => $statusBreakdown,
            ],
            'rows' => $rows,
            'late_rows' => $etaLateBreakdown,
        ]);
    }

    public function receiptsOverview()
    {
        $warehouses = Warehouse::orderBy('name')->get(['id', 'name']);
        return view('admin.procurement.reports.receipts-overview', compact('warehouses'));
    }

    public function receiptsOverviewData(Request $request)
    {
        $base = DB::table('warehouse_receipts as wr')
            ->leftJoin('shipments as sh', 'sh.id', '=', 'wr.shipment_id')
            ->leftJoin('warehouses as wh', 'wh.id', '=', 'wr.warehouse_id');
        $base = $this->applyReceiptFilters($base, $request);

        $totalReceipts = (clone $base)->count();
        $statusBreakdown = (clone $base)
            ->select('wr.status', DB::raw('COUNT(*) as total'))
            ->groupBy('wr.status')
            ->orderByDesc('total')
            ->get()
            ->map(fn($row) => [
                'status' => $row->status,
                'label' => $row->status === 'posted' ? 'Sudah Diposting' : 'Draft',
                'total' => (int) $row->total,
            ])
            ->values();

        $warehouseBreakdown = (clone $base)
            ->select('wh.name as warehouse_name', DB::raw('COUNT(*) as total'))
            ->groupBy('wh.name')
            ->orderByDesc('total')
            ->limit(6)
            ->get()
            ->map(fn($row) => [
                'warehouse' => $row->warehouse_name ?? 'Tanpa Nama',
                'total' => (int) $row->total,
            ])
            ->values();

        $lines = DB::table('warehouse_receipts as wr')
            ->leftJoin('shipments as sh', 'sh.id', '=', 'wr.shipment_id')
            ->leftJoin('warehouses as wh', 'wh.id', '=', 'wr.warehouse_id')
            ->leftJoin('receipt_items as ri', 'ri.warehouse_receipt_id', '=', 'wr.id');
        $lines = $this->applyReceiptFilters($lines, $request);

        $totals = (clone $lines)
            ->selectRaw('COUNT(DISTINCT wr.id) as receipts')
            ->selectRaw('COALESCE(SUM(ri.qty_received), 0) as qty_received')
            ->selectRaw('COALESCE(SUM(ri.cnt_received), 0) as cnt_received')
            ->selectRaw('COUNT(ri.id) as line_count')
            ->first();
        if (!$totals) {
            $totals = (object) [
                'receipts' => 0,
                'qty_received' => 0,
                'cnt_received' => 0,
                'line_count' => 0,
            ];
        }

        $rows = (clone $lines)
            ->select(
                'wr.id',
                'wr.code',
                'wr.received_at',
                'wr.status',
                'sh.code as shipment_code',
                'sh.container_no',
                'wh.name as warehouse_name'
            )
            ->selectRaw('COUNT(ri.id) as line_count')
            ->selectRaw('COALESCE(SUM(ri.qty_received), 0) as qty_total')
            ->selectRaw('COALESCE(SUM(ri.cnt_received), 0) as cnt_total')
            ->groupBy(
                'wr.id',
                'wr.code',
                'wr.received_at',
                'wr.status',
                'sh.code',
                'sh.container_no',
                'wh.name'
            )
            ->orderByDesc('wr.received_at')
            ->limit(250)
            ->get()
            ->map(function ($row) {
                return [
                    'id' => $row->id,
                    'code' => $row->code,
                    'status' => $row->status,
                    'status_label' => $row->status === 'posted' ? 'Sudah Diposting' : 'Draft',
                    'received_at' => $row->received_at
                        ? Carbon::parse($row->received_at)->format('Y-m-d H:i')
                        : null,
                    'warehouse' => $row->warehouse_name,
                    'shipment' => $row->shipment_code ?: $row->container_no,
                    'lines' => (int) $row->line_count,
                    'qty_total' => (float) $row->qty_total,
                    'cnt_total' => (float) $row->cnt_total,
                ];
            })
            ->values();

        return response()->json([
            'summary' => [
                'total_receipts' => (int) ($totals->receipts ?? 0),
                'lines' => (int) ($totals->line_count ?? 0),
                'qty_received' => (float) ($totals->qty_received ?? 0),
                'cnt_received' => (float) ($totals->cnt_received ?? 0),
                'status_breakdown' => $statusBreakdown,
                'warehouse_breakdown' => $warehouseBreakdown,
            ],
            'rows' => $rows,
        ]);
    }

    private function applyShipmentFilters($query, Request $request)
    {
        $search = trim((string) $request->input('search', ''));
        if ($search !== '') {
            $like = '%'.$search.'%';
            $query->where(function ($w) use ($like) {
                $w->where('sh.code', 'like', $like)
                    ->orWhere('sh.container_no', 'like', $like)
                    ->orWhere('sh.pl_no', 'like', $like);
            });
        }

        if ($status = $request->input('status')) {
            $query->where('sh.status', $status);
        }

        if ($from = $request->input('date_from')) {
            $query->whereDate('sh.etd', '>=', $from);
        }

        if ($to = $request->input('date_to')) {
            $query->whereDate('sh.etd', '<=', $to);
        }

        return $query;
    }

    private function applyReceiptFilters($query, Request $request)
    {
        $search = trim((string) $request->input('search', ''));
        if ($search !== '') {
            $like = '%'.$search.'%';
            $query->where(function ($w) use ($like) {
                $w->where('wr.code', 'like', $like)
                    ->orWhere('sh.container_no', 'like', $like)
                    ->orWhere('sh.code', 'like', $like)
                    ->orWhere('wh.name', 'like', $like);
            });
        }

        if ($status = $request->input('status')) {
            $query->where('wr.status', $status);
        }

        if ($from = $request->input('date_from')) {
            $query->whereDate('wr.received_at', '>=', $from);
        }

        if ($to = $request->input('date_to')) {
            $query->whereDate('wr.received_at', '<=', $to);
        }

        if ($warehouseId = $request->input('warehouse_id')) {
            $query->where('wr.warehouse_id', $warehouseId);
        }

        return $query;
    }
}
