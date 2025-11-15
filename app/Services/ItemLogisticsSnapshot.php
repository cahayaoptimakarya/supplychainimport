<?php

namespace App\Services;

use App\Models\Item;
use App\Models\PoLine;
use App\Models\ReceiptItem;
use App\Models\ShipmentItem;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ItemLogisticsSnapshot
{
    public const CATEGORY_PENDING = 'belum_pengiriman';
    public const CATEGORY_PLANNED = 'planned';
    public const CATEGORY_AT_PORT = 'di_pelabuhan';
    public const CATEGORY_IN_TRANSIT_SEA = 'dalam_perjalanan_laut';
    public const CATEGORY_IN_TRANSIT_LAND = 'dalam_perjalanan_darat';
    public const CATEGORY_RECEIVED = 'sudah_diterima';

    private const SHIPMENT_CATEGORY_MAP = [
        'planned' => self::CATEGORY_PLANNED,
        'ready_at_port' => self::CATEGORY_AT_PORT,
        'arrived' => self::CATEGORY_AT_PORT,
        'under_bc' => self::CATEGORY_AT_PORT,
        'on_board' => self::CATEGORY_IN_TRANSIT_SEA,
        'released' => self::CATEGORY_IN_TRANSIT_LAND,
        'delivered_to_main_wh' => self::CATEGORY_IN_TRANSIT_LAND,
        'received' => self::CATEGORY_RECEIVED,
    ];

    /**
     * @return array<string,array<int,array<string,mixed>>>
     */
    public function build(): array
    {
        $poLines = PoLine::select('id', 'item_id', 'qty_ordered', 'qty_fulfilled')
            ->whereNotNull('item_id')
            ->whereHas('purchaseOrder')
            ->get();
        $shipmentItemIds = ShipmentItem::query()
            ->whereNotNull('item_id')
            ->distinct()
            ->pluck('item_id')
            ->filter()
            ->all();
        $receiptItemIds = ReceiptItem::query()
            ->whereNotNull('item_id')
            ->distinct()
            ->pluck('item_id')
            ->filter()
            ->all();

        $itemIds = collect($poLines->pluck('item_id'))
            ->merge($shipmentItemIds)
            ->merge($receiptItemIds)
            ->filter()
            ->unique()
            ->values()
            ->all();

        if (empty($itemIds)) {
            return [
                self::CATEGORY_PENDING => [],
                self::CATEGORY_PLANNED => [],
                self::CATEGORY_IN_TRANSIT_SEA => [],
                self::CATEGORY_IN_TRANSIT_LAND => [],
                self::CATEGORY_AT_PORT => [],
                self::CATEGORY_RECEIVED => [],
            ];
        }

        $items = Item::whereIn('id', $itemIds)
            ->get(['id', 'sku', 'name'])
            ->keyBy('id');

        $shipmentBuckets = $this->buildShipmentBuckets($itemIds);
        $receiptFallback = ReceiptItem::query()
            ->selectRaw('item_id, SUM(qty_received) as qty_total')
            ->whereIn('item_id', $itemIds)
            ->groupBy('item_id')
            ->pluck('qty_total', 'item_id')
            ->map(fn ($qty) => (float) $qty);

        $aggregate = [];

        foreach ($itemIds as $itemId) {
            $item = $items->get($itemId);
                $aggregate[$itemId] = [
                    'item_id' => $itemId,
                    'sku' => $item?->sku,
                    'name' => $item?->name,
                    self::CATEGORY_PENDING => 0.0,
                    self::CATEGORY_PLANNED => 0.0,
                    self::CATEGORY_AT_PORT => 0.0,
                    self::CATEGORY_IN_TRANSIT_SEA => 0.0,
                    self::CATEGORY_IN_TRANSIT_LAND => 0.0,
                    self::CATEGORY_RECEIVED => 0.0,
                ];
            }

        foreach ($poLines as $line) {
            $itemId = (int) $line->item_id;
            if (!isset($aggregate[$itemId])) {
                continue;
            }
            $entry =& $aggregate[$itemId];

            $fulfilled = (float) ($line->qty_fulfilled ?? 0);
            if ($fulfilled > 0) {
                $entry[self::CATEGORY_RECEIVED] += $fulfilled;
            }

            $remaining = max(0.0, (float) $line->qty_ordered - $fulfilled);
            if ($remaining <= 0) {
                continue;
            }

            if (isset($shipmentBuckets[$itemId])) {
                foreach ($shipmentBuckets[$itemId] as &$shipment) {
                    if ($remaining <= 0) {
                        break;
                    }

                    $available = $shipment['available'];
                    if ($available <= 0) {
                        continue;
                    }

                    $take = min($available, $remaining);
                    $entry[$shipment['category']] += $take;
                    $shipment['available'] -= $take;
                    $remaining -= $take;
                }
                unset($shipment);
            }

            if ($remaining > 0) {
                $entry[self::CATEGORY_PENDING] += $remaining;
            }
        }

        foreach ($shipmentBuckets as $itemId => $shipments) {
            if (!isset($aggregate[$itemId])) {
                continue;
            }
            foreach ($shipments as $shipment) {
                $available = (float) ($shipment['available'] ?? 0);
                if ($available <= 0) {
                    continue;
                }
                $category = $shipment['category'] ?? self::CATEGORY_IN_TRANSIT_SEA;
                $aggregate[$itemId][$category] = ($aggregate[$itemId][$category] ?? 0) + $available;
            }
        }

        foreach ($receiptFallback as $itemId => $qty) {
            if (!isset($aggregate[$itemId])) {
                continue;
            }
            $hasPo = $poLines->contains(fn ($line) => (int) $line->item_id === (int) $itemId);
            if (!$hasPo) {
                $aggregate[$itemId][self::CATEGORY_RECEIVED] += (float) $qty;
            }
        }

        return [
            self::CATEGORY_PENDING => $this->formatList($aggregate, self::CATEGORY_PENDING),
            self::CATEGORY_PLANNED => $this->formatList($aggregate, self::CATEGORY_PLANNED),
            self::CATEGORY_AT_PORT => $this->formatList($aggregate, self::CATEGORY_AT_PORT),
            self::CATEGORY_IN_TRANSIT_SEA => $this->formatList($aggregate, self::CATEGORY_IN_TRANSIT_SEA),
            self::CATEGORY_IN_TRANSIT_LAND => $this->formatList($aggregate, self::CATEGORY_IN_TRANSIT_LAND),
            self::CATEGORY_RECEIVED => $this->formatList($aggregate, self::CATEGORY_RECEIVED),
        ];
    }

    /**
     * @param  array<int,array<string,mixed>>  $aggregate
     * @return array<int,array<string,mixed>>
     */
    private function formatList(array $aggregate, string $category): array
    {
        return collect($aggregate)
            ->filter(fn (array $row) => ($row[$category] ?? 0) > 0)
            ->map(function (array $row) use ($category) {
                return [
                    'item_id' => $row['item_id'],
                    'sku' => $row['sku'],
                    'name' => $row['name'],
                    'qty' => round((float) $row[$category], 4),
                ];
            })
            ->sortByDesc('qty')
            ->values()
            ->all();
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
            ->whereNotNull('warehouse_receipts.shipment_id')
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
            $category = $this->mapStatusToCategory($shipment->shipment_status);
            if ($category === self::CATEGORY_RECEIVED) {
                // Received shipments should already be reflected in fulfillment metrics.
                continue;
            }

            $expected = (float) $shipment->qty_expected;
            $receivedKey = $shipment->shipment_id.'|'.$shipment->item_id;
            $received = $receiptSums[$receivedKey] ?? 0.0;
            $available = max(0.0, $expected - $received);
            if ($available <= 0) {
                continue;
            }

            $buckets[$shipment->item_id][] = [
                'category' => $category,
                'available' => $available,
            ];
        }

        return $buckets;
    }

    private function mapStatusToCategory(?string $status): string
    {
        if (!$status) {
            return self::CATEGORY_PENDING;
        }

        return self::SHIPMENT_CATEGORY_MAP[$status] ?? self::CATEGORY_PENDING;
    }
}
