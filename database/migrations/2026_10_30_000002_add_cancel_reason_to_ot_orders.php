<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Alasan pembatalan order-table (kasir atau backoffice), ditampilkan ke pelanggan dan
     * dipakai laporan. Batas 160 karakter mengikuti validasi endpoint cancel di Node.
     */
    public function up(): void
    {
        Schema::table('ot_orders', function (Blueprint $table) {
            $table->string('cancel_reason', 160)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('ot_orders', function (Blueprint $table) {
            $table->dropColumn('cancel_reason');
        });
    }
};
