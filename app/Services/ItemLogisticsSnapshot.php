<?php

namespace App\Services;

use App\Models\PoLine;
use App\Models\ReceiptItem;
use App\Models\ShipmentItem;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ItemLogisticsSnapshot
{
    public const CATEGORY_BELUM = 'belum_proses';
    public const CATEGORY_DIJALAN = 'sedang_dijalan';
    public const CATEGORY_SUDAH = 'sudah_diterima';

    private const SHIPMENT_CATEGORY_MAP = [
        'planned' => self::CATEGORY_BELUM,
        'ready_at_port' => self::CATEGORY_BELUM,
        'on_board' => self::CATEGORY_DIJALAN,
        'arrived' => self::CATEGORY_DIJALAN,
        'under_bc' => self::CATEGORY_DIJALAN,
        'released' => self::CATEGORY_DIJALAN,
        'delivered_to_main_wh' => self::CATEGORY_DIJALAN,
        'received' => self::CATEGORY_SUDAH,
    ];

    /**
     * @return array<string,array<int,array<string,mixed>>>
     */
    public function build(): array
    {
        $poLines = PoLine::select('id', 'item_id', 'qty_ordered', 'qty_fulfilled')
            ->with('item:id,sku,name')
            ->whereNotNull('item_id')
            ->whereHas('purchaseOrder')
            ->get();

        if ($poLines->isEmpty()) {
            return [
                self::CATEGORY_BELUM => [],
                self::CATEGORY_DIJALAN => [],
                self::CATEGORY_SUDAH => [],
            ];
        }

        $itemIds = $poLines->pluck('item_id')->unique()->filter()->values()->all();
        $shipmentBuckets = $this->buildShipmentBuckets($itemIds);

        $aggregate = [];

        foreach ($poLines as $line) {
            $item = $line->item;
            if (!$item) {
                continue;
            }

            $itemId = (int) $line->item_id;
            if (!isset($aggregate[$itemId])) {
                $aggregate[$itemId] = [
                    'item_id' => $itemId,
                    'sku' => $item->sku,
                    'name' => $item->name,
                    self::CATEGORY_BELUM => 0.0,
                    self::CATEGORY_DIJALAN => 0.0,
                    self::CATEGORY_SUDAH => 0.0,
                ];
            }

            $entry =& $aggregate[$itemId];

            $fulfilled = (float) ($line->qty_fulfilled ?? 0);
            if ($fulfilled > 0) {
                $entry[self::CATEGORY_SUDAH] += $fulfilled;
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
                $entry[self::CATEGORY_BELUM] += $remaining;
            }
        }

        return [
            self::CATEGORY_BELUM => $this->formatList($aggregate, self::CATEGORY_BELUM),
            self::CATEGORY_DIJALAN => $this->formatList($aggregate, self::CATEGORY_DIJALAN),
            self::CATEGORY_SUDAH => $this->formatList($aggregate, self::CATEGORY_SUDAH),
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
            if ($category === self::CATEGORY_SUDAH) {
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
            return self::CATEGORY_BELUM;
        }

        return self::SHIPMENT_CATEGORY_MAP[$status] ?? self::CATEGORY_BELUM;
    }
}
