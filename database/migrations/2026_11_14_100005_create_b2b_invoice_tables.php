<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('b2b_invoices', function (Blueprint $table) {
            $table->id();
            $table->string('invoice_number', 40)->unique();
            $table->foreignId('b2b_order_id')->unique()->constrained('b2b_orders');
            $table->foreignId('b2b_customer_id')->constrained('b2b_customers');
            $table->date('issue_date');
            $table->date('due_date')->comment('jatuh tempo jadwal terakhir');
            $table->bigInteger('amount')->comment('pokok');
            // Disiapkan untuk metode pembayaran yang belum diputuskan (kode unik / VA). Sekarang selalu 0.
            $table->bigInteger('unique_code_amount')->default(0);
            $table->bigInteger('total_to_pay');
            $table->bigInteger('paid_amount')->default(0);
            $table->enum('status', ['unpaid', 'partial', 'paid', 'overdue', 'void'])->default('unpaid');
            $table->timestamp('paid_at')->nullable();
            $table->foreignId('voided_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('voided_at')->nullable();
            $table->string('void_reason')->nullable();
            $table->json('billing_address_snapshot')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['b2b_customer_id', 'status']);
            $table->index(['status', 'due_date']);
        });

        // Jadwal tagihan: sekarang selalu 1 baris per invoice, disiapkan untuk cicilan.
        Schema::create('b2b_invoice_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('b2b_invoice_id')->constrained('b2b_invoices')->cascadeOnDelete();
            $table->unsignedTinyInteger('sequence')->default(1);
            $table->date('due_date');
            $table->bigInteger('amount');
            $table->unsignedSmallInteger('unique_code')->default(0);
            $table->bigInteger('amount_to_pay');
            $table->bigInteger('paid_amount')->default(0);
            $table->enum('status', ['unpaid', 'partial', 'paid', 'overdue', 'void'])->default('unpaid');
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();

            $table->unique(['b2b_invoice_id', 'sequence']);
            $table->index(['status', 'due_date']);
            $table->index(['amount_to_pay', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('b2b_invoice_schedules');
        Schema::dropIfExists('b2b_invoices');
    }
};
