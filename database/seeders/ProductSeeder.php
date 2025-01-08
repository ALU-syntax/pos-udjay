<?php

namespace Database\Seeders;

use App\Models\Product;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Product::create([
            'name' => "Kopi Susu",
            'category_id' => 1,
            'status' => true,
            'harga_modal' => 20000,
            'outlet_id' => 1
        ]);

        Product::create([
            'name' => "Kopi Susu",
            'category_id' => 1,
            'status' => true,
            'harga_modal' => 20000,
            'outlet_id' => 2
        ]);

        Product::create([
            'name' => "Americano",
            'category_id' => 1,
            'status' => true,
            'harga_modal' => 10000,
            'outlet_id' => 1
        ]);

        Product::create([
            'name' => "Americano",
            'category_id' => 1,
            'status' => true,
            'harga_modal' => 10000,
            'outlet_id' => 2
        ]);

        Product::create([
            'name' => "Anggur",
            'category_id' => 3,
            'status' => true,
            'harga_modal' => 50000,
            'outlet_id' => 1
        ]);
        
    }
}
