<?php

namespace Database\Seeders;

use App\Models\SupplierCategory;
use Illuminate\Database\Seeder;

class SupplierCategorySeeder extends Seeder
{
    public function run(): void
    {
        // Ensure the 'import' supplier category exists
        SupplierCategory::firstOrCreate(
            ['slug' => 'import'],
            ['name' => 'import']
        );
    }
}

