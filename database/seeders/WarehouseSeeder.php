<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class WarehouseSeeder extends Seeder
{
    public function run(): void
    {
        $names = ['Nanggewer', 'Ciseeng', 'Ciapus', 'Seha'];
        foreach ($names as $name) {
            DB::table('warehouses')->updateOrInsert(
                ['name' => $name],
                [
                    'address' => null,
                    'description' => null,
                    'updated_at' => now(),
                    'created_at' => now(),
                ]
            );
        }
    }
}

