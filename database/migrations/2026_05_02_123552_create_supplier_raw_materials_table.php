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
        Schema::create('supplier_raw_materials', function (Blueprint $table) {
            $table->id();

            $table->foreignId('supplier_id')
                ->constrained('suppliers')
                ->cascadeOnDelete();

            $table->foreignId('raw_material_id')
                ->constrained('raw_materials')
                ->cascadeOnDelete();

            /*
             * Nama bahan menurut supplier.
             * Contoh:
             * Master: Daging Ayam
             * Supplier A: Ayam Broiler Potong Fresh
             * Supplier B: Daging Ayam Segar Grade A
             */
            $table->string('supplier_material_name')->nullable();

            $table->string('supplier_sku', 100)->nullable();

            $table->foreignId('purchase_unit_id')
                ->constrained('satuans')
                ->restrictOnDelete();

            $table->decimal('minimum_order_qty', 18, 4)->default(0);

            /*
             * Lead time estimasi berapa hari barang datang dari supplier.
             */
            $table->unsignedSmallInteger('lead_time_days')->default(0);

            /*
             * Harga aktif saat ini.
             * Untuk histori perubahan harga tetap disimpan di tabel
             * supplier_raw_material_price_histories.
             */
            $table->decimal('current_price', 18, 2)->nullable();
            $table->timestamp('price_updated_at')->nullable();

            /*
             * Jika ada beberapa supplier untuk bahan yang sama,
             * gudang bisa menandai supplier prioritas.
             */
            $table->boolean('is_preferred')->default(false);

            $table->boolean('is_active')->default(true);
            $table->text('notes')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index('supplier_id');
            $table->index('raw_material_id');
            $table->index('purchase_unit_id');
            $table->index('is_preferred');
            $table->index('is_active');

            $table->unique([
                'supplier_id',
                'raw_material_id',
                'purchase_unit_id',
            ], 'supplier_raw_material_unit_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('supplier_raw_materials');
    }
};
