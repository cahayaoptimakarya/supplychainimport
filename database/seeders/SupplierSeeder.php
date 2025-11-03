<?php

namespace Database\Seeders;

use App\Models\Supplier;
use App\Models\SupplierCategory;
use Illuminate\Database\Seeder;

class SupplierSeeder extends Seeder
{
    public function run(): void
    {
        // Ensure the 'import' category exists
        $category = SupplierCategory::firstOrCreate(
            ['slug' => 'import'],
            ['name' => 'import']
        );

        $suppliers = [
            ['name' => 'zunyang'],
            ['name' => 'jungsuan'],
        ];

        foreach ($suppliers as $data) {
            Supplier::firstOrCreate(
                ['name' => $data['name']],
                [
                    'supplier_category_id' => $category->id,
                ]
            );
        }
    }
}

