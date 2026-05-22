<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('purchase_order_items', function (Blueprint $table) {
            $table->decimal('qty_base_rejected', 15, 5)->default(0)->after('qty_base_received');
            $table->decimal('qty_base_cancelled', 15, 5)->default(0)->after('qty_base_rejected');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('purchase_order_items', function (Blueprint $table) {
            $table->dropColumn([
                'qty_base_rejected',
                'qty_base_cancelled',
            ]);
        });
    }
};
