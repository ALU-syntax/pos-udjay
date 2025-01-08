<?php

namespace Database\Seeders;

use App\Models\Outlets;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class OutletsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Outlets::create([
            'name' => 'Udjay Puter',
            'address' => 'jalan jalan muter',
            'phone' => "08123",
        ]);

        Outlets::create([
            'name' => 'Udjay Malabar',
            'address' => 'jalan malabar',
            'phone' => "0878208123043",
        ]);
    }
}
