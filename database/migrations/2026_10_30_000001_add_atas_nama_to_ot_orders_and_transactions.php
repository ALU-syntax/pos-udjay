<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Nama pemesan order-table, diisi pelanggan saat checkout dan disalin ke transaksi POS.
     * Nullable: data lama, transaksi POS biasa, dan order draft tidak punya nama; kewajiban
     * mengisi ditegakkan di aplikasi. Tanpa ->after() supaya ALTER pada tabel transactions
     * yang besar bisa memakai ALGORITHM=INSTANT.
     */
    public function up(): void
    {
        Schema::table('ot_orders', function (Blueprint $table) {
            $table->string('atas_nama', 50)->nullable();
        });
        Schema::table('transactions', function (Blueprint $table) {
            $table->string('atas_nama', 50)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('ot_orders', function (Blueprint $table) {
            $table->dropColumn('atas_nama');
        });
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropColumn('atas_nama');
        });
    }
};
