<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('b2b_orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_number', 40)->unique();
            $table->foreignId('b2b_customer_id')->constrained('b2b_customers');
            $table->foreignId('b2b_contract_id')->nullable()->comment('kosong = harga normal')->constrained('b2b_contracts')->nullOnDelete();
            $table->foreignId('outlet_id')->constrained('outlets');
            $table->foreignId('user_id')->nullable()->comment('kasir')->constrained('users')->nullOnDelete();
            $table->dateTime('order_date');
            $table->date('due_date')->comment('jatuh tempo pilihan kasir; bayar langsung = tanggal pesanan; disalin ke invoice');
            $table->enum('payment_type', ['credit', 'immediate'])->default('credit')->comment('credit = tempo, immediate = bayar langsung di kasir');
            $table->enum('status', ['draft', 'confirmed', 'completed', 'cancelled'])->default('confirmed');
            $table->bigInteger('subtotal')->default(0)->comment('jumlah line_total item');
            $table->bigInteger('tax_total')->default(0);
            $table->bigInteger('shipping_cost')->default(0);
            $table->bigInteger('grand_total')->default(0);
            $table->enum('delivery_method', ['pickup', 'internal'])->default('pickup');
            $table->json('shipping_address_snapshot')->nullable();
            $table->json('credit_check_result')->nullable();
            $table->foreignId('credit_override_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('credit_override_reason')->nullable();
            $table->foreignId('confirmed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('confirmed_at')->nullable();
            $table->foreignId('completed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('completed_at')->nullable();
            $table->foreignId('cancelled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('cancelled_at')->nullable();
            $table->string('cancel_reason')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['outlet_id', 'status', 'order_date']);
            $table->index(['b2b_customer_id', 'order_date']);
        });

        // Satu baris per item dengan kolom qty (POS menyimpan satu baris per unit; B2B tidak meniru itu).
        Schema::create('b2b_order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('b2b_order_id')->constrained('b2b_orders')->cascadeOnDelete();
            $table->foreignId('product_id')->nullable()->constrained('products')->nullOnDelete();
            $table->foreignId('variant_id')->nullable()->constrained('variant_products')->nullOnDelete();
            $table->string('product_name');
            $table->string('variant_name');
            $table->unsignedInteger('qty');
            $table->bigInteger('base_price')->comment('harga normal varian saat transaksi');
            $table->foreignId('b2b_contract_item_id')->nullable()->comment('kosong = harga normal')->constrained('b2b_contract_items');
            $table->bigInteger('final_price')->comment('harga yang dipakai: harga kontrak atau harga normal');
            $table->bigInteger('line_total');
            $table->boolean('stock_insufficient')->default(false);
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('b2b_order_items');
        Schema::dropIfExists('b2b_orders');
    }
};
