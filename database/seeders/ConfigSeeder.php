<?php

namespace Database\Seeders;

use App\Models\Config;
use Illuminate\Database\Seeder;

class ConfigSeeder extends Seeder
{
    /**
     * Seed config default yang dipakai aplikasi mobile.
     *
     * Menggunakan firstOrCreate agar aman dijalankan berulang kali
     * (value yang sudah diubah tidak ter-reset ke default).
     */
    public function run(): void
    {
        $configs = [
            [
                'name' => 'password_min_length',
                'type' => 'integer',
                'value' => '8',
                'description' => 'Panjang minimal password untuk validasi di mobile',
            ],
            [
                'name' => 'pin_length',
                'type' => 'integer',
                'value' => '6',
                'description' => 'Panjang PIN kasir untuk validasi di mobile',
            ],
            [
                'name' => 'transaction_sync_limit',
                'type' => 'integer',
                'value' => '100',
                'description' => 'Jumlah maksimal data per request sinkronisasi transaksi',
            ],
            [
                'name' => 'feature_multi_outlet',
                'type' => 'boolean',
                'value' => '0',
                'description' => 'Flag fitur multi outlet',
            ],
        ];

        foreach ($configs as $config) {
            Config::firstOrCreate(
                ['name' => $config['name']],
                [
                    'type' => $config['type'],
                    'value' => $config['value'],
                    'description' => $config['description'],
                ],
            );
        }
    }
}
