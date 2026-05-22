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
        Schema::create('purchase_order_cancellations', function (Blueprint $table) {
            $table->id()
                ->comment('Primary key dokumen pembatalan purchase order.');

            $table->unsignedBigInteger('purchase_order_id')
                ->comment('Relasi ke purchase_orders yang dibatalkan sebagian atau seluruhnya.');
            $table->unsignedBigInteger('supplier_id')
                ->nullable()
                ->comment('Supplier terkait pembatalan; boleh null jika pembatalan internal mencakup beberapa supplier.');
            $table->string('cancellation_number', 100)
                ->nullable()
                ->comment('Nomor dokumen pembatalan PO jika ingin dibuat sebagai referensi audit.');
            $table->string('cancelled_by_party', 30)
                ->comment('Pihak yang membatalkan PO; contoh buyer atau supplier.');
            $table->unsignedBigInteger('cancelled_by')
                ->nullable()
                ->comment('User internal yang mencatat pembatalan; null jika hanya mewakili pembatalan dari supplier.');
            $table->dateTime('cancelled_at')
                ->comment('Tanggal dan jam pembatalan PO dicatat atau terjadi.');

            $table->string('reason', 255)
                ->nullable()
                ->comment('Ringkasan alasan pembatalan, misalnya stok supplier kosong atau PO tidak dilanjutkan.');
            $table->text('notes')
                ->nullable()
                ->comment('Catatan detail tambahan terkait pembatalan PO.');

            $table->timestamp('created_at')
                ->nullable()
                ->comment('Waktu record pembatalan PO dibuat.');
            $table->timestamp('updated_at')
                ->nullable()
                ->comment('Waktu terakhir record pembatalan PO diperbarui.');

            $table->unique('cancellation_number', 'unique_po_cancellation_number');
            $table->index('purchase_order_id', 'idx_po_cancellations_po_id');
            $table->index('supplier_id', 'idx_po_cancellations_supplier_id');
            $table->index('cancelled_by_party', 'idx_po_cancellations_party');
            $table->index('cancelled_at', 'idx_po_cancellations_cancelled_at');

            $table->foreign('purchase_order_id', 'po_cancellations_po_id_foreign')
                ->references('id')
                ->on('purchase_orders')
                ->cascadeOnDelete();

            $table->foreign('supplier_id', 'po_cancellations_supplier_id_foreign')
                ->references('id')
                ->on('suppliers')
                ->nullOnDelete();

            $table->foreign('cancelled_by', 'po_cancellations_cancelled_by_foreign')
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
        Schema::dropIfExists('purchase_order_cancellations');
    }
};
