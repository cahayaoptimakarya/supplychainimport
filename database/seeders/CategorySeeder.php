<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $names = ['Import', 'Produksi'];
        foreach ($names as $name) {
            $slug = Str::slug($name);
            DB::table('categories')->updateOrInsert(
                ['slug' => $slug],
                [
                    'name' => $name,
                    'updated_at' => now(),
                    'created_at' => now(),
                ]
            );
        }
    }
}

