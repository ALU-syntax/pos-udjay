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
        Schema::create('purchase_order_cancellation_items', function (Blueprint $table) {
            $table->id()
                ->comment('Primary key detail item pada dokumen pembatalan purchase order.');

            $table->unsignedBigInteger('purchase_order_cancellation_id')
                ->comment('Relasi ke header pembatalan purchase_order_cancellations.');
            $table->unsignedBigInteger('purchase_order_item_id')
                ->comment('Relasi ke item PO asal yang qty-nya dibatalkan.');
            $table->unsignedBigInteger('raw_material_id')
                ->comment('Raw material yang dibatalkan; disimpan untuk mempercepat laporan dan menjaga histori item.');

            $table->decimal('qty_cancelled', 15, 5)
                ->comment('Jumlah barang yang dibatalkan dalam satuan yang dipilih saat pembatalan.');
            $table->unsignedBigInteger('unit_id')
                ->comment('Satuan yang digunakan saat mencatat pembatalan, mengacu ke tabel satuans.');
            $table->decimal('qty_base_cancelled', 15, 5)
                ->comment('Jumlah barang yang dibatalkan setelah dikonversi ke base unit raw material.');

            $table->text('notes')
                ->nullable()
                ->comment('Catatan tambahan untuk detail item pembatalan ini.');

            $table->timestamp('created_at')
                ->nullable()
                ->comment('Waktu record detail pembatalan dibuat.');
            $table->timestamp('updated_at')
                ->nullable()
                ->comment('Waktu terakhir record detail pembatalan diperbarui.');

            $table->index('purchase_order_cancellation_id', 'idx_po_cancel_items_cancel_id');
            $table->index('purchase_order_item_id', 'idx_po_cancel_items_po_item_id');
            $table->index('raw_material_id', 'idx_po_cancel_items_material_id');
            $table->index('unit_id', 'idx_po_cancel_items_unit_id');

            $table->foreign('purchase_order_cancellation_id', 'po_cancel_items_cancel_id_foreign')
                ->references('id')
                ->on('purchase_order_cancellations')
                ->cascadeOnDelete();

            $table->foreign('purchase_order_item_id', 'po_cancel_items_po_item_id_foreign')
                ->references('id')
                ->on('purchase_order_items')
                ->cascadeOnDelete();

            $table->foreign('raw_material_id', 'po_cancel_items_material_id_foreign')
                ->references('id')
                ->on('raw_materials')
                ->restrictOnDelete();

            $table->foreign('unit_id', 'po_cancel_items_unit_id_foreign')
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
        Schema::dropIfExists('purchase_order_cancellation_items');
    }
};
