<?php

namespace Database\Seeders;

use App\Models\CategoryPayment;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class CategoryPaymentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        CategoryPayment::create([
            'name' => 'Cash',
            'status' => true,
        ]);

        CategoryPayment::create([
            'name' => 'EDC',
            'status' => true,
        ]);

        CategoryPayment::create([
            'name' => 'Transfer',
            'status' => true,
        ]);
    }
}
