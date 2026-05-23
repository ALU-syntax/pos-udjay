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
        Schema::table('purchase_orders', function (Blueprint $table) {
            $table->dropForeign(['receiving_inventory_id']);
            $table->dropIndex('idx_receiving_inventory_id');
            $table->dropColumn('receiving_inventory_id');
        });

        Schema::table('purchase_orders', function (Blueprint $table) {
            $table->unsignedBigInteger('procurement_plan_id')->nullable()->after('po_number');
            $table->unsignedBigInteger('ordered_by_inventory_id')->after('procurement_plan_id');
            $table->unsignedBigInteger('receiving_inventory_id')->after('ordered_by_inventory_id');

            $table->index('procurement_plan_id', 'idx_procurement_plan_id');
            $table->index('ordered_by_inventory_id', 'idx_ordered_by_inventory_id');
            $table->index('receiving_inventory_id', 'idx_receiving_inventory_id');

            $table->foreign('procurement_plan_id')
                ->references('id')
                ->on('procurement_plans')
                ->nullOnDelete();

            $table->foreign('ordered_by_inventory_id')
                ->references('id')
                ->on('inventory')
                ->restrictOnDelete();

            $table->foreign('receiving_inventory_id')
                ->references('id')
                ->on('inventory')
                ->restrictOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('purchase_orders', function (Blueprint $table) {
            $table->dropForeign(['procurement_plan_id']);
            $table->dropForeign(['ordered_by_inventory_id']);
            $table->dropForeign(['receiving_inventory_id']);

            $table->dropIndex('idx_procurement_plan_id');
            $table->dropIndex('idx_ordered_by_inventory_id');
            $table->dropIndex('idx_receiving_inventory_id');

            $table->dropColumn([
                'procurement_plan_id',
                'ordered_by_inventory_id',
                'receiving_inventory_id',
            ]);
        });

        Schema::table('purchase_orders', function (Blueprint $table) {
            $table->unsignedBigInteger('receiving_inventory_id')->after('po_number');

            $table->index('receiving_inventory_id', 'idx_receiving_inventory_id');

            $table->foreign('receiving_inventory_id')
                ->references('id')
                ->on('inventory')
                ->restrictOnDelete();
        });
    }
};
