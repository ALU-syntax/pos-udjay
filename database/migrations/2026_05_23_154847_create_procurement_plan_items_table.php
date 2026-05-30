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
        Schema::create('procurement_plan_items', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('procurement_plan_id');
            $table->unsignedBigInteger('raw_material_id');
            $table->unsignedBigInteger('supplier_id')->nullable();
            $table->unsignedBigInteger('supplier_raw_material_id')->nullable();

            $table->decimal('qty_required_base', 15, 5)->default(0);
            $table->decimal('qty_available_base', 15, 5)->default(0);
            $table->decimal('qty_shortage_base', 15, 5)->default(0);
            $table->decimal('qty_to_purchase_base', 15, 5)->default(0);

            $table->unsignedBigInteger('unit_id');

            $table->decimal('estimated_unit_price', 15, 2)->nullable();
            $table->decimal('estimated_subtotal', 15, 2)->nullable();

            $table->text('notes')->nullable();

            $table->timestamps();

            $table->index('procurement_plan_id', 'idx_procurement_plan_id');
            $table->index('raw_material_id', 'idx_raw_material_id');
            $table->index('supplier_id', 'idx_supplier_id');
            $table->index('supplier_raw_material_id', 'idx_supplier_raw_material_id');
            $table->index('unit_id', 'idx_unit_id');

            $table->unique([
                'procurement_plan_id',
                'raw_material_id',
            ], 'procurement_plan_raw_material_unique');

            $table->foreign('procurement_plan_id')
                ->references('id')
                ->on('procurement_plans')
                ->cascadeOnDelete();

            $table->foreign('raw_material_id')
                ->references('id')
                ->on('raw_materials')
                ->restrictOnDelete();

            $table->foreign('supplier_id')
                ->references('id')
                ->on('suppliers')
                ->nullOnDelete();

            $table->foreign('supplier_raw_material_id')
                ->references('id')
                ->on('supplier_raw_materials')
                ->nullOnDelete();

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
        Schema::dropIfExists('procurement_plan_items');
    }
};
