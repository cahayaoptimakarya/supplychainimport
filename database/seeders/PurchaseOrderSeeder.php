<?php

namespace Database\Seeders;

use App\Models\Item;
use App\Models\PoLine;
use App\Models\PurchaseOrder;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class PurchaseOrderSeeder extends Seeder
{
    public function run(): void
    {
        $items = Item::all();

        if ($items->isEmpty()) {
            // Nothing to seed without items
            return;
        }

        $makeCode = function (): string {
            return 'PO-'.now()->format('ymd').'-'.strtoupper(Str::random(4));
        };

        for ($i = 0; $i < 5; $i++) {
            // Ensure unique code
            do {
                $code = $makeCode();
            } while (PurchaseOrder::where('code', $code)->exists());

            $po = PurchaseOrder::create([
                'code' => $code,
                'order_date' => now()->subDays(rand(0, 30))->toDateString(),
                'ref_no' => rand(0,1) ? ('REF-'.strtoupper(Str::random(5))) : null,
                'status' => 'open',
            ]);

            $lineCount = rand(1, 4);
            $picked = $items->shuffle()->take($lineCount);
            foreach ($picked as $it) {
                // integer qty only
                $qty = mt_rand(1, 500); // 1 - 500
                $koli = rand(0,1) ? null : round(mt_rand(1, 1000) / 10, 4); // nullable or random
                PoLine::create([
                    'purchase_order_id' => $po->id,
                    'item_id' => $it->id,
                    'qty_ordered' => $qty,
                    'koli_ordered' => $koli,
                    'notes' => rand(0,1) ? null : 'Catatan line '.Str::upper(Str::random(3)),
                ]);
            }
        }
    }
}
