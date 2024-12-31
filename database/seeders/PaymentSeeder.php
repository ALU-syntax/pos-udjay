<?php

namespace Database\Seeders;

use App\Models\Payment;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class PaymentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Payment::create([
            'name' => 'DEBIT',
            'status' => true,
            'category_payment_id' => 2
        ]);

        Payment::create([
            'name' => 'QRIS',
            'status' => true,
            'category_payment_id' => 2
        ]);

        Payment::create([
            'name' => 'BCA',
            'status' => true,
            'category_payment_id' => 3
        ]);
    }
}
