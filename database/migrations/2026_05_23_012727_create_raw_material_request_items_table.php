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
        Schema::create('raw_material_request_items', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('raw_material_request_id');
            $table->unsignedBigInteger('raw_material_id');

            $table->decimal('qty_requested', 15, 5);
            $table->unsignedBigInteger('unit_id');
            $table->decimal('qty_base_requested', 15, 5);

            $table->decimal('qty_base_approved', 15, 5)->nullable();
            $table->decimal('qty_base_fulfilled', 15, 5)->default(0);

            $table->text('notes')->nullable();

            $table->timestamps();

            $table->index('raw_material_request_id', 'idx_rm_request_items_request_id');
            $table->index('raw_material_id', 'idx_rm_request_items_raw_material_id');
            $table->index('unit_id', 'idx_rm_request_items_unit_id');

            $table->foreign('raw_material_request_id', 'rm_request_items_request_id_foreign')
                ->references('id')
                ->on('raw_material_requests')
                ->cascadeOnDelete();

            $table->foreign('raw_material_id', 'rm_request_items_raw_material_id_foreign')
                ->references('id')
                ->on('raw_materials')
                ->restrictOnDelete();

            $table->foreign('unit_id', 'rm_request_items_unit_id_foreign')
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
        Schema::dropIfExists('raw_material_request_items');
    }
};
