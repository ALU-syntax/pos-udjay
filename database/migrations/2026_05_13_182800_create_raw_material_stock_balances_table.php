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
        Schema::create('inventory_raw_material_stock_balances', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('raw_material_id');
            $table->unsignedBigInteger('inventory_id');

            $table->decimal('qty_available', 15, 5)->default(0);
            $table->decimal('qty_reserved', 15, 5)->default(0);

            $table->timestamps();

            $table->unique([
                'raw_material_id',
                'inventory_id',
            ], 'unique_material');

            $table->index('raw_material_id', 'idx_raw_material_id');
            $table->index('inventory_id', 'idx_inventory_id');

            $table->foreign('raw_material_id', 'stock_balances_raw_material_id_foreign')
                ->references('id')
                ->on('raw_materials')
                ->restrictOnDelete();

            $table->foreign('inventory_id', 'stock_balances_inventory_id_foreign')
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
        Schema::dropIfExists('inventory_raw_material_stock_balances');
    }
};
