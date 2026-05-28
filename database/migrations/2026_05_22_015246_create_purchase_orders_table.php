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
        Schema::create('purchase_orders', function (Blueprint $table) {
            $table->id();

            $table->string('po_number', 100);
            $table->unsignedBigInteger('procurement_plan_id')->nullable();
            $table->unsignedBigInteger('supplier_id');
            $table->unsignedBigInteger('ordered_by_inventory_id');
            $table->unsignedBigInteger('receiving_inventory_id');
            $table->unsignedBigInteger('status_id')->default(1);

            $table->date('order_date')->nullable();
            $table->date('expected_delivery_date')->nullable();

            $table->unsignedBigInteger('requested_by')->nullable();
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->unsignedBigInteger('ordered_by')->nullable();

            $table->dateTime('requested_at')->nullable();
            $table->dateTime('approved_at')->nullable();
            $table->dateTime('ordered_at')->nullable();

            $table->text('notes')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->unique('po_number', 'unique_po_number');

            $table->index('procurement_plan_id', 'idx_procurement_plan_id');
            $table->index('supplier_id', 'idx_supplier_id');
            $table->index('ordered_by_inventory_id', 'idx_ordered_by_inventory_id');
            $table->index('receiving_inventory_id', 'idx_receiving_inventory_id');
            $table->index('status_id', 'idx_status');

            $table->foreign('procurement_plan_id')
                ->references('id')
                ->on('procurement_plans')
                ->nullOnDelete();

            $table->foreign('supplier_id')
                ->references('id')
                ->on('suppliers')
                ->restrictOnDelete();

            $table->foreign('ordered_by_inventory_id')
                ->references('id')
                ->on('inventory')
                ->restrictOnDelete();

            $table->foreign('receiving_inventory_id')
                ->references('id')
                ->on('inventory')
                ->restrictOnDelete();

            $table->foreign('status_id')
                ->references('id')
                ->on('purchase_order_statuses')
                ->restrictOnDelete();

            $table->foreign('requested_by')
                ->references('id')
                ->on('users')
                ->nullOnDelete();

            $table->foreign('approved_by')
                ->references('id')
                ->on('users')
                ->nullOnDelete();

            $table->foreign('ordered_by')
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
        Schema::dropIfExists('purchase_orders');
    }
};
