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
        Schema::create('purchase_order_receipt_items', function (Blueprint $table) {
            $table->id()
                ->comment('Primary key detail item pada dokumen penerimaan purchase order.');

            $table->unsignedBigInteger('purchase_order_receipt_id')
                ->comment('Relasi ke header penerimaan purchase_order_receipts.');
            $table->unsignedBigInteger('purchase_order_item_id')
                ->comment('Relasi ke item PO asal agar penerimaan dapat dibandingkan dengan qty yang dipesan.');
            $table->unsignedBigInteger('raw_material_id')
                ->comment('Raw material yang diterima; disimpan untuk mempercepat laporan dan menjaga histori item.');

            $table->decimal('qty_received', 15, 5)
                ->comment('Jumlah fisik barang yang datang dari supplier dalam satuan yang dipilih.');
            $table->unsignedBigInteger('unit_id')
                ->comment('Satuan yang digunakan saat penerimaan, mengacu ke tabel satuans.');
            $table->decimal('qty_base_received', 15, 5)
                ->comment('Jumlah fisik barang yang datang setelah dikonversi ke base unit raw material.');

            $table->decimal('qty_accepted', 15, 5)
                ->comment('Jumlah barang yang diterima baik dan boleh masuk stok dalam satuan penerimaan.');
            $table->decimal('qty_base_accepted', 15, 5)
                ->comment('Jumlah barang yang diterima baik setelah dikonversi ke base unit raw material.');

            $table->decimal('qty_rejected', 15, 5)
                ->default(0)
                ->comment('Jumlah barang yang ditolak karena rusak, bocor, kurang layak, atau alasan lain.');
            $table->decimal('qty_base_rejected', 15, 5)
                ->default(0)
                ->comment('Jumlah barang yang ditolak setelah dikonversi ke base unit raw material.');

            $table->decimal('unit_price', 15, 2)
                ->nullable()
                ->comment('Harga satuan barang pada saat penerimaan jika perlu disimpan atau berbeda dari PO.');
            $table->decimal('accepted_subtotal', 15, 2)
                ->nullable()
                ->comment('Nilai subtotal barang yang diterima baik, biasanya qty accepted dikali harga satuan.');

            $table->string('supplier_batch_number', 100)
                ->nullable()
                ->comment('Nomor batch atau lot dari supplier jika tersedia.');
            $table->date('expiry_date')
                ->nullable()
                ->comment('Tanggal kedaluwarsa barang jika raw material memiliki masa berlaku.');
            $table->text('rejection_reason')
                ->nullable()
                ->comment('Alasan penolakan barang; diisi ketika qty_rejected lebih dari nol.');
            $table->text('notes')
                ->nullable()
                ->comment('Catatan tambahan untuk detail item penerimaan ini.');

            $table->timestamp('created_at')
                ->nullable()
                ->comment('Waktu record detail penerimaan dibuat.');
            $table->timestamp('updated_at')
                ->nullable()
                ->comment('Waktu terakhir record detail penerimaan diperbarui.');

            $table->index('purchase_order_receipt_id', 'idx_po_receipt_items_receipt_id');
            $table->index('purchase_order_item_id', 'idx_po_receipt_items_po_item_id');
            $table->index('raw_material_id', 'idx_po_receipt_items_material_id');
            $table->index('unit_id', 'idx_po_receipt_items_unit_id');

            $table->foreign('purchase_order_receipt_id', 'po_receipt_items_receipt_id_foreign')
                ->references('id')
                ->on('purchase_order_receipts')
                ->cascadeOnDelete();

            $table->foreign('purchase_order_item_id', 'po_receipt_items_po_item_id_foreign')
                ->references('id')
                ->on('purchase_order_items')
                ->cascadeOnDelete();

            $table->foreign('raw_material_id', 'po_receipt_items_material_id_foreign')
                ->references('id')
                ->on('raw_materials')
                ->restrictOnDelete();

            $table->foreign('unit_id', 'po_receipt_items_unit_id_foreign')
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
        Schema::dropIfExists('purchase_order_receipt_items');
    }
};
