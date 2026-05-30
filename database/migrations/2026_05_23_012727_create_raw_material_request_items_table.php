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

            $table->decimal('requested_qty', 15, 5);
            $table->unsignedBigInteger('requested_satuan_id');
            $table->string('requested_satuan_name', 100);
            $table->decimal('requested_conversion_to_base', 18, 6);
            $table->decimal('requested_base_qty', 15, 5);
            $table->unsignedBigInteger('requested_base_satuan_id');
            $table->string('requested_base_satuan_name', 100);

            $table->decimal('approved_qty', 15, 5)->nullable();
            $table->unsignedBigInteger('approved_satuan_id')->nullable();
            $table->string('approved_satuan_name', 100)->nullable();
            $table->decimal('approved_conversion_to_base', 18, 6)->nullable();
            $table->decimal('approved_base_qty', 15, 5)->nullable();
            $table->unsignedBigInteger('approved_base_satuan_id')->nullable();
            $table->string('approved_base_satuan_name', 100)->nullable();

            $table->decimal('fulfilled_base_qty', 15, 5)->default(0);
            $table->unsignedBigInteger('fulfilled_base_satuan_id');
            $table->string('fulfilled_base_satuan_name', 100);

            $table->text('notes')->nullable();

            $table->timestamps();

            $table->index('raw_material_request_id', 'idx_rm_request_items_request_id');
            $table->index('raw_material_id', 'idx_rm_request_items_raw_material_id');
            $table->index('requested_satuan_id', 'idx_rm_request_items_requested_satuan_id');
            $table->index('requested_base_satuan_id', 'idx_rm_request_items_requested_base_satuan_id');
            $table->index('approved_satuan_id', 'idx_rm_request_items_approved_satuan_id');
            $table->index('approved_base_satuan_id', 'idx_rm_request_items_approved_base_satuan_id');
            $table->index('fulfilled_base_satuan_id', 'idx_rm_request_items_fulfilled_base_satuan_id');

            $table->foreign('raw_material_request_id', 'rm_request_items_request_id_foreign')
                ->references('id')
                ->on('raw_material_requests')
                ->cascadeOnDelete();

            $table->foreign('raw_material_id', 'rm_request_items_raw_material_id_foreign')
                ->references('id')
                ->on('raw_materials')
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
