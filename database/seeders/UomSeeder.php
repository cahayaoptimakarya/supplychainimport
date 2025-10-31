<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class UomSeeder extends Seeder
{
    public function run(): void
    {
        $uoms = [
            ['name' => 'Piece', 'symbol' => 'pcs', 'keterangan' => 'Satuan'],
            ['name' => 'Set', 'symbol' => 'set', 'keterangan' => 'Set'],
            ['name' => 'Kilogram', 'symbol' => 'kg', 'keterangan' => 'Massa'],
            ['name' => 'Gram', 'symbol' => 'g', 'keterangan' => 'Massa'],
            ['name' => 'Liter', 'symbol' => 'L', 'keterangan' => 'Volume'],
            ['name' => 'Meter', 'symbol' => 'm', 'keterangan' => 'Panjang'],
        ];

        foreach ($uoms as $uom) {
            DB::table('uoms')->updateOrInsert(
                ['name' => $uom['name']],
                [
                    'symbol' => $uom['symbol'],
                    'keterangan' => $uom['keterangan'],
                    'updated_at' => now(),
                    'created_at' => now(),
                ]
            );
        }
    }
}

