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
        Schema::create('purchase_order_items', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('purchase_order_id');
            $table->unsignedBigInteger('raw_material_id');

            $table->decimal('qty_ordered', 15, 5);
            $table->unsignedBigInteger('unit_id');
            $table->decimal('qty_base_ordered', 15, 5);

            $table->decimal('qty_base_received', 15, 5)->default(0);

            $table->decimal('unit_price', 15, 2)->nullable();
            $table->decimal('subtotal', 15, 2)->nullable();

            $table->text('notes')->nullable();

            $table->timestamps();

            $table->index('purchase_order_id', 'idx_purchase_order_id');
            $table->index('raw_material_id', 'idx_raw_material_id');
            $table->index('unit_id', 'idx_unit_id');

            $table->foreign('purchase_order_id')
                ->references('id')
                ->on('purchase_orders')
                ->cascadeOnDelete();

            $table->foreign('raw_material_id')
                ->references('id')
                ->on('raw_materials')
                ->restrictOnDelete();

            $table->foreign('unit_id')
                ->references('id')
                ->on('satuans')
                ->restrictOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('purchase_order_items');
    }
};
