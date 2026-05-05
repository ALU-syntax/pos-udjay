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
        Schema::create('outlet_stock_request_items', function (Blueprint $table) {
            $table->id();

            $table->foreignId('outlet_stock_request_id')
                ->constrained('outlet_stock_requests')
                ->cascadeOnDelete();

            $table->foreignId('raw_material_id')
                ->constrained('raw_materials')
                ->restrictOnDelete();

            $table->foreignId('unit_id')
                ->constrained('satuans')
                ->restrictOnDelete();

            $table->decimal('requested_qty', 18, 4);

            /*
             * Qty yang disetujui gudang.
             */
            $table->decimal('approved_qty', 18, 4)->nullable();

            /*
             * Qty yang benar-benar dikirim/dipenuhi.
             */
            $table->decimal('fulfilled_qty', 18, 4)->nullable();

            $table->text('notes')->nullable();

            $table->timestamps();

            $table->index('outlet_stock_request_id');
            $table->index('raw_material_id');
            $table->index('unit_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('outlet_stock_request_items');
    }
};
