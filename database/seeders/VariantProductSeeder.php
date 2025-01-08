<?php

namespace Database\Seeders;

use App\Models\VariantProduct;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class VariantProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        VariantProduct::create([
            'name' => "Kopi Susu",
            'harga' => 25000, 
            'stok' => 20,
            'product_id' => 1
        ]);

        VariantProduct::create([
            'name' => "Kopi Susu",
            'harga' => 25000, 
            'stok' => 20,
            'product_id' => 2
        ]);

        VariantProduct::create([
            'name' => "Size S",
            'harga' => 15000, 
            'stok' => 20,
            'product_id' => 3
        ]);

        VariantProduct::create([
            'name' => "Size M",
            'harga' => 20000, 
            'stok' => 20,
            'product_id' => 3
        ]);

        VariantProduct::create([
            'name' => "Size L",
            'harga' => 25000, 
            'stok' => 20,
            'product_id' => 3
        ]);

        VariantProduct::create([
            'name' => "Size S",
            'harga' => 15000, 
            'stok' => 20,
            'product_id' => 4
        ]);

        VariantProduct::create([
            'name' => "Size M",
            'harga' => 20000, 
            'stok' => 20,
            'product_id' => 4
        ]);

        VariantProduct::create([
            'name' => "Size L",
            'harga' => 25000, 
            'stok' => 20,
            'product_id' => 4
        ]);

        VariantProduct::create([
            'name' => "Merah",
            'harga' => 60000, 
            'stok' => 20,
            'product_id' => 5
        ]);

        VariantProduct::create([
            'name' => "Hijau",
            'harga' => 30000, 
            'stok' => 20,
            'product_id' => 5
        ]);
    }
}
