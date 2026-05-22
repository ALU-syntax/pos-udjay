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
        Schema::create('purchase_order_receipts', function (Blueprint $table) {
            $table->id()
                ->comment('Primary key dokumen penerimaan purchase order.');

            $table->string('receipt_number', 100)
                ->nullable()
                ->comment('Nomor dokumen penerimaan barang, contoh GRN-20260522-0001.');
            $table->unsignedBigInteger('purchase_order_id')
                ->comment('Relasi ke purchase_orders; satu PO bisa memiliki beberapa penerimaan bertahap.');
            $table->unsignedBigInteger('supplier_id')
                ->comment('Supplier yang mengirim barang pada dokumen penerimaan ini.');
            $table->unsignedBigInteger('receipt_status_id')
                ->default(1)
                ->comment('Status dokumen penerimaan; default 1 berarti draft.');
            $table->unsignedBigInteger('received_inventory_id')
                ->comment('Inventory atau gudang yang menerima barang pada dokumen penerimaan ini.');

            $table->string('delivery_note_number', 100)
                ->nullable()
                ->comment('Nomor surat jalan dari supplier jika tersedia.');
            $table->string('supplier_invoice_number', 100)
                ->nullable()
                ->comment('Nomor invoice supplier jika invoice datang bersamaan atau sudah tersedia.');

            $table->unsignedBigInteger('received_by')
                ->nullable()
                ->comment('User yang mencatat atau melakukan penerimaan fisik barang.');
            $table->unsignedBigInteger('posted_by')
                ->nullable()
                ->comment('User yang memfinalisasi penerimaan; posting dapat dipakai untuk proses masuk stok.');
            $table->unsignedBigInteger('voided_by')
                ->nullable()
                ->comment('User yang membatalkan atau melakukan void dokumen penerimaan.');

            $table->dateTime('received_at')
                ->nullable()
                ->comment('Tanggal dan jam barang diterima secara fisik.');
            $table->dateTime('posted_at')
                ->nullable()
                ->comment('Tanggal dan jam dokumen penerimaan diposting atau difinalisasi.');
            $table->dateTime('voided_at')
                ->nullable()
                ->comment('Tanggal dan jam dokumen penerimaan dibatalkan atau di-void.');

            $table->text('void_reason')
                ->nullable()
                ->comment('Alasan void, misalnya salah input jumlah, salah PO, atau dokumen double.');
            $table->text('notes')
                ->nullable()
                ->comment('Catatan umum terkait penerimaan barang.');

            $table->timestamp('created_at')
                ->nullable()
                ->comment('Waktu record dokumen penerimaan dibuat.');
            $table->timestamp('updated_at')
                ->nullable()
                ->comment('Waktu terakhir record dokumen penerimaan diperbarui.');
            $table->timestamp('deleted_at')
                ->nullable()
                ->comment('Waktu soft delete agar dokumen tidak hilang permanen untuk kebutuhan audit.');

            $table->unique('receipt_number', 'unique_po_receipt_number');

            $table->index('purchase_order_id', 'idx_po_receipts_po_id');
            $table->index('supplier_id', 'idx_po_receipts_supplier_id');
            $table->index('receipt_status_id', 'idx_po_receipts_status_id');
            $table->index('received_inventory_id', 'idx_po_receipts_inventory_id');
            $table->index('received_at', 'idx_po_receipts_received_at');

            $table->foreign('purchase_order_id', 'po_receipts_po_id_foreign')
                ->references('id')
                ->on('purchase_orders')
                ->cascadeOnDelete();

            $table->foreign('supplier_id', 'po_receipts_supplier_id_foreign')
                ->references('id')
                ->on('suppliers')
                ->restrictOnDelete();

            $table->foreign('receipt_status_id', 'po_receipts_status_id_foreign')
                ->references('id')
                ->on('purchase_order_receipt_statuses')
                ->restrictOnDelete();

            $table->foreign('received_inventory_id', 'po_receipts_inventory_id_foreign')
                ->references('id')
                ->on('inventory')
                ->restrictOnDelete();

            $table->foreign('received_by', 'po_receipts_received_by_foreign')
                ->references('id')
                ->on('users')
                ->nullOnDelete();

            $table->foreign('posted_by', 'po_receipts_posted_by_foreign')
                ->references('id')
                ->on('users')
                ->nullOnDelete();

            $table->foreign('voided_by', 'po_receipts_voided_by_foreign')
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
        Schema::dropIfExists('purchase_order_receipts');
    }
};
