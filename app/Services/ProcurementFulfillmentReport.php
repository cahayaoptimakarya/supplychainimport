<?php

namespace App\Services;

use App\Models\PurchaseOrder;
use App\Models\ReceiptItem;
use App\Models\ShipmentItem;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ProcurementFulfillmentReport
{
    private const STAGE_BELUM_DIKIRIM = 'belum_dikirim';
    private const STAGE_MASIH_DIJALAN = 'masih_dijalan';
    private const STAGE_DI_PELABUHAN = 'di_pelabuhan';
    private const STAGE_DITERIMA_GUDANG = 'diterima_gudang';

    private const SHIPMENT_STAGE_MAP = [
        'planned' => self::STAGE_BELUM_DIKIRIM,
        'ready_at_port' => self::STAGE_BELUM_DIKIRIM,
        'on_board' => self::STAGE_MASIH_DIJALAN,
        'arrived' => self::STAGE_DI_PELABUHAN,
        'under_bc' => self::STAGE_DI_PELABUHAN,
        'released' => self::STAGE_DI_PELABUHAN,
        'delivered_to_main_wh' => self::STAGE_DI_PELABUHAN,
        'received' => self::STAGE_DITERIMA_GUDANG,
    ];

    /**
     * Build aggregated report rows for the given purchase orders.
     *
     * @param  \Illuminate\Database\Eloquent\Collection<int,\App\Models\PurchaseOrder>  $purchaseOrders
     * @return \Illuminate\Support\Collection<int,array<string,mixed>>
     */
    public function build(EloquentCollection $purchaseOrders): Collection
    {
        if ($purchaseOrders->isEmpty()) {
            return collect();
        }

        $poSorted = $purchaseOrders
            ->sortBy(fn (PurchaseOrder $po) => sprintf('%s-%08d', optional($po->order_date)->format('Ymd') ?? '99991231', $po->id))
            ->values();

        $lines = $poSorted->flatMap->lines;
        $itemIds = $lines->pluck('item_id')->unique()->filter()->values()->all();
        $shipmentsByItem = $this->buildShipmentBuckets($itemIds);

        $rows = [];
        $itemOrder = [];

        foreach ($poSorted as $po) {
            foreach ($po->lines as $line) {
                $itemId = $line->item_id;
                if (!$itemId) {
                    continue;
                }

                if (!isset($rows[$itemId])) {
                    $item = $line->item;
                    $rows[$itemId] = [
                        'item_id' => $itemId,
                        'sku' => optional($item)->sku ?? ('SKU-'.$itemId),
                        'item_name' => optional($item)->name ?? '-',
                        'qty_ordered' => 0.0,
                        'qty_fulfilled' => 0.0,
                        self::STAGE_BELUM_DIKIRIM => 0.0,
                        self::STAGE_MASIH_DIJALAN => 0.0,
                        self::STAGE_DI_PELABUHAN => 0.0,
                        self::STAGE_DITERIMA_GUDANG => 0.0,
                    ];
                    $itemOrder[] = $itemId;
                }

                $row =& $rows[$itemId];

                $ordered = (float) $line->qty_ordered;
                $fulfilled = (float) $line->qty_fulfilled;

                $row['qty_ordered'] += $ordered;
                $row['qty_fulfilled'] += $fulfilled;
                $row[self::STAGE_DITERIMA_GUDANG] += $fulfilled;

                $remaining = max(0.0, $ordered - $fulfilled);
                if ($remaining > 0) {
                    if (isset($shipmentsByItem[$itemId])) {
                        foreach ($shipmentsByItem[$itemId] as &$shipment) {
                            if ($remaining <= 0) {
                                break;
                            }

                            $available = $shipment['available'];
                            if ($available <= 0) {
                                continue;
                            }

                            $take = min($available, $remaining);
                            $row[$shipment['stage']] += $take;
                            $shipment['available'] -= $take;
                            $remaining -= $take;
                        }
                        unset($shipment);
                    }

                    if ($remaining > 0) {
                        $row[self::STAGE_BELUM_DIKIRIM] += $remaining;
                    }
                }

                $row['status'] = $this->deriveStatus($row['qty_ordered'], $row['qty_fulfilled']);
                unset($row);
            }
        }

        $orderedRows = collect();
        foreach ($itemOrder as $itemId) {
            if (!isset($rows[$itemId])) {
                continue;
            }
            $orderedRows->push($this->castRow($rows[$itemId]));
        }

        return $orderedRows;
    }

    /**
     * @param  float  $ordered
     * @param  float  $fulfilled
     */
    private function deriveStatus(float $ordered, float $fulfilled): string
    {
        $open = max(0.0, $ordered - $fulfilled);

        if ($ordered <= 0) {
            return 'open';
        }

        if ($open <= 0.00001) {
            return 'fulfilled';
        }

        return $fulfilled > 0 ? 'partial' : 'open';
    }

    /**
     * @param  array<string,mixed>  $row
     * @return array<string,mixed>
     */
    private function castRow(array $row): array
    {
        foreach (['qty_ordered', 'qty_fulfilled', self::STAGE_BELUM_DIKIRIM, self::STAGE_MASIH_DIJALAN, self::STAGE_DI_PELABUHAN, self::STAGE_DITERIMA_GUDANG] as $key) {
            $row[$key] = (float) $row[$key];
        }

        return $row;
    }

    /**
     * @param  array<int,int>  $itemIds
     * @return array<int,array<int,array<string,mixed>>>
     */
    private function buildShipmentBuckets(array $itemIds): array
    {
        if (empty($itemIds)) {
            return [];
        }

        $receiptSums = ReceiptItem::query()
            ->selectRaw('warehouse_receipts.shipment_id as shipment_id, receipt_items.item_id as item_id, SUM(receipt_items.qty_received) as qty_received')
            ->join('warehouse_receipts', 'warehouse_receipts.id', '=', 'receipt_items.warehouse_receipt_id')
            ->whereIn('receipt_items.item_id', $itemIds)
            ->groupBy('warehouse_receipts.shipment_id', 'receipt_items.item_id')
            ->get()
            ->reduce(function (array $carry, $row) {
                $key = $row->shipment_id.'|'.$row->item_id;
                $carry[$key] = (float) $row->qty_received;
                return $carry;
            }, []);

        $shipments = ShipmentItem::query()
            ->select([
                'shipment_items.item_id',
                'shipment_items.qty_expected',
                'shipments.id as shipment_id',
                'shipments.status as shipment_status',
                DB::raw('COALESCE(shipments.etd, shipments.eta, shipments.created_at) as sort_key'),
            ])
            ->join('shipments', 'shipments.id', '=', 'shipment_items.shipment_id')
            ->whereIn('shipment_items.item_id', $itemIds)
            ->orderBy('sort_key')
            ->orderBy('shipment_items.id')
            ->get();

        $buckets = [];

        foreach ($shipments as $shipment) {
            $expected = (float) $shipment->qty_expected;
            $receivedKey = $shipment->shipment_id.'|'.$shipment->item_id;
            $received = $receiptSums[$receivedKey] ?? 0.0;
            $available = max(0.0, $expected - $received);
            if ($available <= 0) {
                continue;
            }

            $stage = $this->mapStatusToStage($shipment->shipment_status);

            $buckets[$shipment->item_id][] = [
                'shipment_id' => $shipment->shipment_id,
                'available' => $available,
                'stage' => $stage,
            ];
        }

        return $buckets;
    }

    private function mapStatusToStage(?string $status): string
    {
        if (!$status) {
            return self::STAGE_BELUM_DIKIRIM;
        }

        return self::SHIPMENT_STAGE_MAP[$status] ?? self::STAGE_BELUM_DIKIRIM;
    }
}
