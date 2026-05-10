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
            'Pieces' => 'pcs',
            'Kilogram' => 'kg',
            'Gram' => 'g',
            'Liter' => 'L',
            'Mililiter' => 'ml',
            'Pack' => 'pack',
            'Box' => 'box',
            'Botol' => 'botol',
            'Kaleng' => 'kaleng',
            "Dus" => 'dus',
            'Sak' => 'sak',
            'Ikat' => 'ikat',
            'Buah' => 'buah',
            'Lembar' => 'lembar',
            'Roll' => 'roll',
            'Meter' => 'm',
            'Centimeter' => 'cm',
            'Sendok' => 'sendok',
            'Cup' => 'cup',
            'Porsi' => 'porsi',
        ];

        foreach ($satuans as $name => $symbol) {
            DB::table('satuans')->updateOrInsert(
                [
                    'name' => $name,
                    'symbol' => $symbol
                ],
                [
                    'is_active' => true,
                    'updated_at' => now(),
                    'created_at' => now(),
                ]
            );
        }
    }
}
