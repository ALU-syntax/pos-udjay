<?php

namespace Database\Seeders;

use App\Models\KategoriPengeluaran;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class KategoriPengeluaranSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        KategoriPengeluaran::create([
            'name' => 'Pembelian Stok',
        ]);

        KategoriPengeluaran::create([
            'name' => 'Pengeluaran Di Luar Usaha',
        ]);

        KategoriPengeluaran::create([
            'name' => 'Pembelian bahan baku',
        ]);

        KategoriPengeluaran::create([
            'name' => 'Biaya Operasional',
        ]);

        KategoriPengeluaran::create([
            'name' => 'Gaji/Bonus Karyawan',
        ]);

        KategoriPengeluaran::create([
            'name' => 'Pemberian Utang',
        ]);

        KategoriPengeluaran::create([
            'name' => 'Pembayaran Utang/Cicilan',
        ]);

        KategoriPengeluaran::create([
            'name' => 'Pengeluaran lain-lain',
        ]);
    }
}
