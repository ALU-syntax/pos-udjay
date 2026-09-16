<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;

    /**
     * Pastikan data referensi minimal tersedia untuk test.
     *
     * Beberapa test API (transaksi) memakai `category_payment_id = 1` secara
     * hardcode — dulu nilai itu selalu ada di database development. Di database
     * test, id 1 belum tentu ada karena auto increment terus bergeser antar test
     * (rollback tidak mereset AUTO_INCREMENT). Karena itu di sini id 1 dipastikan
     * benar-benar ada sebelum setiap test.
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->ensureReferenceData();
    }

    private function ensureReferenceData(): void
    {
        if (!Schema::hasTable('category_payments')) {
            return;
        }

        if (!DB::table('category_payments')->where('id', 1)->exists()) {
            DB::table('category_payments')->insert([[
                'id'         => 1,
                'name'       => 'Cash',
                'status'     => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]]);
        }

        if (!DB::table('category_payments')->where('id', 2)->exists()) {
            DB::table('category_payments')->insert([[
                'id'         => 2,
                'name'       => 'EDC',
                'status'     => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]]);
        }

        if (!DB::table('category_payments')->where('id', 3)->exists()) {
            DB::table('category_payments')->insert([[
                'id'         => 3,
                'name'       => 'Transfer',
                'status'     => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]]);
        }
    }
}
