<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Item;
use App\Models\Uom;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ItemSeeder extends Seeder
{
    public function run(): void
    {
        // Ensure base categories exist
        $categories = [
            ['name' => 'Import'],
            ['name' => 'Produksi'],
        ];

        foreach ($categories as $cat) {
            $slug = Str::slug($cat['name']);
            Category::firstOrCreate(
                ['slug' => $slug],
                ['name' => $cat['name']]
            );
        }

        // Map for quick lookup
        $categoryIds = Category::whereIn('slug', ['import', 'produksi'])->pluck('id', 'slug');
        $uomIds = Uom::whereIn('symbol', ['pcs', 'set', 'kg'])->pluck('id', 'symbol');

        $items = [
            [
                'name' => 'Karton Import',
                'sku' => 'KARTON-IMP',
                'category_slug' => 'import',
                'uom_symbol' => 'pcs',
                'cnt' => '1',
                'description' => 'Karton pengemasan untuk barang import',
            ],
            [
                'name' => 'Baut M6',
                'sku' => 'BAUT-M6',
                'category_slug' => 'produksi',
                'uom_symbol' => 'pcs',
                'cnt' => '1',
                'description' => 'Baut ukuran M6 untuk perakitan',
            ],
            [
                'name' => 'Mur M6',
                'sku' => 'MUR-M6',
                'category_slug' => 'produksi',
                'uom_symbol' => 'pcs',
                'cnt' => '1',
                'description' => 'Mur ukuran M6 pasangan baut',
            ],
        ];

        foreach ($items as $data) {
            $categoryId = $categoryIds[$data['category_slug']] ?? null;
            $uomId = $uomIds[$data['uom_symbol']] ?? null;

            if (!$categoryId || !$uomId) {
                // Skip if required references are missing
                continue;
            }

            Item::updateOrCreate(
                ['sku' => $data['sku']],
                [
                    'name' => $data['name'],
                    'category_id' => $categoryId,
                    'uom_id' => $uomId,
                    'cnt' => $data['cnt'],
                    'description' => $data['description'],
                ]
            );
        }
    }
}
