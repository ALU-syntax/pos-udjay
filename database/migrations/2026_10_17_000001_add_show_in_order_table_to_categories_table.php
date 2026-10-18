<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Kategori yang ditampilkan di menu Order Table (pelanggan pesan dari meja).
     * Default true agar menu yang sudah tampil tidak hilang; kategori internal
     * (Waste, Kalibrasi, Compliment, dll.) dimatikan manual dari backoffice.
     */
    public function up(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->boolean('show_in_order_table')->default(true)->after('reward_categories');
        });
    }

    public function down(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->dropColumn('show_in_order_table');
        });
    }
};
