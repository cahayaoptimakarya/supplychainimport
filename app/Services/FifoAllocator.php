<?php

namespace App\Services;

use App\Models\PoLine;
use App\Models\ReceiptItem;
use App\Models\ReceiptAllocation;
use Illuminate\Support\Facades\DB;

class FifoAllocator
{
    /**
     * Allocate a receipt item quantity to open PO lines of same item (FIFO by PO order_date then line id).
     */
    public static function allocateReceiptItem(ReceiptItem $receiptItem): void
    {
        $itemId = $receiptItem->item_id;
        $qty = (float) $receiptItem->qty_received;
        if ($qty <= 0) return;

        DB::transaction(function () use ($itemId, $qty, $receiptItem) {
            $remaining = $qty;
            // Fetch open PO lines for this SKU ordered by PO date then line id
            $lines = PoLine::query()
                ->where('item_id', $itemId)
                ->whereHas('purchaseOrder')
                ->with(['purchaseOrder'])
                ->get()
                ->sortBy(fn ($l) => sprintf('%s-%08d', optional($l->purchaseOrder->order_date)->format('Ymd') ?? '99999999', $l->id))
                ->values();

            foreach ($lines as $line) {
                $open = (float) $line->open_qty;
                if ($open <= 0) continue;
                $take = min($open, $remaining);
                if ($take <= 0) break;

                $alloc = ReceiptAllocation::firstOrCreate([
                    'receipt_item_id' => $receiptItem->id,
                    'po_line_id' => $line->id,
                ], [
                    'qty' => 0,
                ]);
                $alloc->qty = (float) $alloc->qty + $take;
                $alloc->save();

                $remaining -= $take;
                if ($remaining <= 0) break;
            }
        });
    }
}
