<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Baris yang dihapus / diganti saat kasir mengedit order bayar-di-kasir sebelum dibayar.
     * Baris tidak dihapus (jejak untuk komplain & KPI kasir): status = 'cancelled' + voided_at.
     * Pembatalan seluruh order (expired/cancelled) TIDAK mengisi kolom ini, sehingga
     * "item yang tampil" = voided_at IS NULL untuk semua status order.
     */
    public function up(): void
    {
        Schema::table('ot_order_items', function (Blueprint $table) {
            $table->timestamp('voided_at')->nullable()->after('status');
            $table->string('voided_by', 80)->nullable()->after('voided_at');
        });
    }

    public function down(): void
    {
        Schema::table('ot_order_items', function (Blueprint $table) {
            $table->dropColumn(['voided_at', 'voided_by']);
        });
    }
};
