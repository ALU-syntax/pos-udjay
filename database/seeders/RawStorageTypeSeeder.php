<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RawStorageTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $storageTypes = [
            'Dry',
            'Chilled',
            'Frozen',
            'Other',
        ];

        foreach ($storageTypes as $storageType) {
            DB::table('raw_storage_types')->updateOrInsert(
                ['name' => $storageType],
                [
                    'updated_at' => now(),
                    'created_at' => now(),
                ]
            );
        }
    }
}
