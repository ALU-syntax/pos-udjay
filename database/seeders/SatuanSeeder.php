<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SatuanSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $satuans = [
            'pcs',
            'kg',
            'gram',
            'liter',
            'ml',
            'pack',
            'box',
            'botol',
            'kaleng',
            'dus',
            'sak',
            'ikat',
            'buah',
            'lembar',
            'roll',
            'meter',
            'cm',
            'sendok',
            'cup',
            'porsi',
        ];

        foreach ($satuans as $satuan) {
            DB::table('satuans')->updateOrInsert(
                ['name' => $satuan],
                [
                    'is_active' => true,
                    'updated_at' => now(),
                    'created_at' => now(),
                ]
            );
        }
    }
}
