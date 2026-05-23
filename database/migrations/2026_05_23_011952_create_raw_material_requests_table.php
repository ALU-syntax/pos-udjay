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
        Schema::create('raw_material_requests', function (Blueprint $table) {
            $table->id();

            $table->string('request_number', 100);

            $table->unsignedBigInteger('requester_inventory_id');
            $table->unsignedBigInteger('fulfillment_location_id')->nullable();

            $table->unsignedBigInteger('status_id')->default(1);

            $table->date('needed_at')->nullable();

            $table->unsignedBigInteger('requested_by')->nullable();
            $table->unsignedBigInteger('approved_by')->nullable();

            $table->dateTime('requested_at')->nullable();
            $table->dateTime('approved_at')->nullable();

            $table->text('notes')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->unique('request_number', 'unique_raw_material_request_number');

            $table->index('requester_inventory_id', 'idx_rm_requests_requester_inventory_id');
            $table->index('fulfillment_location_id', 'idx_rm_requests_fulfillment_location_id');
            $table->index('status_id', 'idx_rm_requests_status_id');
            $table->index('needed_at', 'idx_rm_requests_needed_at');

            $table->foreign('requester_inventory_id', 'rm_requests_requester_inventory_id_foreign')
                ->references('id')
                ->on('inventory')
                ->restrictOnDelete();

            $table->foreign('fulfillment_location_id', 'rm_requests_fulfillment_location_id_foreign')
                ->references('id')
                ->on('inventory')
                ->nullOnDelete();

            $table->foreign('status_id', 'rm_requests_status_id_foreign')
                ->references('id')
                ->on('raw_material_request_statuses')
                ->restrictOnDelete();

            $table->foreign('requested_by', 'rm_requests_requested_by_foreign')
                ->references('id')
                ->on('users')
                ->nullOnDelete();

            $table->foreign('approved_by', 'rm_requests_approved_by_foreign')
                ->references('id')
                ->on('users')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('raw_material_requests');
    }
};
