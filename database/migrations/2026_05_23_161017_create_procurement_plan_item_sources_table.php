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
        Schema::create('procurement_plan_item_sources', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('procurement_plan_item_id');
            $table->unsignedBigInteger('raw_material_request_item_id');

            $table->decimal('qty_base_allocated', 15, 5);

            $table->timestamps();

            $table->index('procurement_plan_item_id', 'idx_plan_item_id');
            $table->index('raw_material_request_item_id', 'idx_request_item_id');

            $table->unique([
                'procurement_plan_item_id',
                'raw_material_request_item_id',
            ], 'proc_plan_item_source_unique');

            $table->foreign('procurement_plan_item_id')
                ->references('id')
                ->on('procurement_plan_items')
                ->cascadeOnDelete();

            $table->foreign('raw_material_request_item_id')
                ->references('id')
                ->on('raw_material_request_items')
                ->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('procurement_plan_item_sources');
    }
};
