<?php

namespace Database\Seeders;

use App\Models\KategoriPemasukan;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class KategoriPemasukanSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        KategoriPemasukan::create([
            'name' => 'Penambahan Modal',
        ]);

        KategoriPemasukan::create([
            'name' => 'Pendapatan Di Luar Usaha',
        ]);

        KategoriPemasukan::create([
            'name' => 'Pendapatan Lain-Lain',
        ]);

        KategoriPemasukan::create([
            'name' => 'Pendapatan Jasa/Komisi',
        ]);

        KategoriPemasukan::create([
            'name' => 'Terima Pinjaman',
        ]);

        KategoriPemasukan::create([
            'name' => 'Penagihan Utang/Cicilan',
        ]);
    }
}
