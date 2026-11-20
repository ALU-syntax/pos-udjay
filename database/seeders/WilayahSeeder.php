<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Wilayah administrasi Indonesia: provinsi, kota/kabupaten, kecamatan, kelurahan/desa.
 * Sumber: github.com/cahyadsn/wilayah (MIT), Kepmendagri No 300.2.2-2430 Tahun 2025.
 * Kode pos kelurahan/desa: github.com/cahyadsn/wilayah_kodepos (MIT).
 *
 * Aman dijalankan berulang (upsert per kode): php artisan db:seed --class=WilayahSeeder
 */
class WilayahSeeder extends Seeder
{
    private const CHUNK = 1000;

    /** Urutan induk → anak supaya foreign key kode induk selalu terpenuhi. */
    private const TABLES = [
        'provinces' => ['code', 'name'],
        'regencies' => ['code', 'province_code', 'name'],
        'districts' => ['code', 'regency_code', 'name'],
        'villages' => ['code', 'district_code', 'name', 'postal_code'],
    ];

    public function run(): void
    {
        foreach (self::TABLES as $table => $columns) {
            $total = $this->seedTable($table, $columns);
            $this->command?->info("{$table}: {$total} baris");
        }
    }

    private function seedTable(string $table, array $columns): int
    {
        $path = database_path("seeders/data/wilayah/{$table}.csv");
        $handle = fopen($path, 'r');
        if ($handle === false) {
            throw new RuntimeException("File data wilayah tidak ditemukan: {$path}");
        }

        try {
            if (fgetcsv($handle) !== $columns) {
                throw new RuntimeException("Header {$table}.csv tidak sesuai: ".implode(',', $columns));
            }

            $total = 0;
            $rows = [];
            while (($line = fgetcsv($handle)) !== false) {
                if ($line === [null]) {
                    continue;
                }
                $rows[] = array_map(fn (string $value) => $value === '' ? null : $value, array_combine($columns, $line));
                if (count($rows) === self::CHUNK) {
                    $total += $this->flush($table, $columns, $rows);
                    $rows = [];
                }
            }

            return $total + $this->flush($table, $columns, $rows);
        } finally {
            fclose($handle);
        }
    }

    private function flush(string $table, array $columns, array $rows): int
    {
        if ($rows !== []) {
            DB::table($table)->upsert($rows, ['code'], array_values(array_diff($columns, ['code'])));
        }

        return count($rows);
    }
}
