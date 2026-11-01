<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Produk, grup modifier, dan opsi modifier yang ditampilkan di menu Order Table.
     * Default true agar menu yang sudah tampil tidak hilang. Kasir tetap melihat semuanya;
     * flag ini hanya menyaring menu pelanggan (dibaca oleh backend Node order-table).
     */
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->boolean('show_in_order_table')->default(true)->after('status');
        });

        Schema::table('modifier_groups', function (Blueprint $table) {
            $table->boolean('show_in_order_table')->default(true)->after('is_required');
        });

        Schema::table('modifiers', function (Blueprint $table) {
            $table->boolean('show_in_order_table')->default(true)->after('stok');
        });
    }

    public function down(): void
    {
        Schema::table('modifiers', function (Blueprint $table) {
            $table->dropColumn('show_in_order_table');
        });

        Schema::table('modifier_groups', function (Blueprint $table) {
            $table->dropColumn('show_in_order_table');
        });

        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('show_in_order_table');
        });
    }
};
