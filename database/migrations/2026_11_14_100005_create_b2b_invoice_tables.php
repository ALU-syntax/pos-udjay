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
            // Diisi b2b:mark-overdue / PaymentService saat pertama kali lewat jatuh tempo; tidak dikosongkan saat lunas.
            $table->timestamp('overdue_at')->nullable();
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
            $table->timestamp('overdue_at')->nullable();
            $table->timestamps();

            $table->unique(['b2b_invoice_id', 'sequence']);
            $table->index(['status', 'due_date']);
            $table->index(['amount_to_pay', 'status']);
        });

        // Pencatatan pembayaran. Metode memakai master pembayaran POS (`category_payments` → `payments`);
        // tabel di sini hanya menyimpan transaksinya. Ringkasan lunas tetap di `b2b_invoices.paid_amount/status/paid_at`.
        Schema::create('b2b_payments', function (Blueprint $table) {
            $table->id();
            $table->string('payment_number', 40)->unique();
            $table->foreignId('b2b_customer_id')->constrained('b2b_customers');
            $table->foreignId('outlet_id')->nullable()->comment('outlet tempat bayar; kosong untuk pelunasan di luar kasir')->constrained('outlets');
            $table->foreignId('category_payment_id')->constrained('category_payments');
            $table->foreignId('payment_id')->nullable()->comment('kosong bila kategori tanpa sub metode, mis. Cash')->constrained('payments');
            $table->string('method_name', 100)->comment('snapshot nama metode, mis. "EDC · QRIS"');
            $table->bigInteger('amount');
            $table->dateTime('paid_at');
            $table->string('reference', 100)->nullable()->comment('kode approval EDC / no. referensi transfer');
            $table->foreignId('received_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->string('attachment_path')->nullable()->comment('bukti bayar (opsional), disk local');
            $table->string('attachment_name')->nullable();
            $table->enum('status', ['posted', 'reversed'])->default('posted');
            $table->foreignId('reversed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reversed_at')->nullable();
            $table->string('reverse_reason')->nullable();
            $table->timestamps();

            $table->index(['b2b_customer_id', 'paid_at']);
            $table->index(['outlet_id', 'paid_at']);
        });

        Schema::create('b2b_payment_allocations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('b2b_payment_id')->constrained('b2b_payments');
            $table->foreignId('b2b_invoice_id')->constrained('b2b_invoices');
            $table->foreignId('b2b_invoice_schedule_id')->nullable()->constrained('b2b_invoice_schedules');
            $table->bigInteger('amount');
            $table->timestamps();

            $table->index('b2b_invoice_id');
        });

        // Pengembalian uang saat pesanan yang sudah dibayar dibatalkan. Metode refund boleh beda dari metode bayar.
        Schema::create('b2b_refunds', function (Blueprint $table) {
            $table->id();
            $table->foreignId('b2b_payment_id')->constrained('b2b_payments');
            $table->foreignId('b2b_invoice_id')->constrained('b2b_invoices');
            $table->foreignId('outlet_id')->nullable()->constrained('outlets');
            $table->foreignId('category_payment_id')->constrained('category_payments');
            $table->foreignId('payment_id')->nullable()->constrained('payments');
            $table->string('method_name', 100);
            $table->bigInteger('amount');
            $table->string('reference', 100)->nullable();
            $table->string('reason');
            $table->foreignId('refunded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('refunded_at');
            $table->timestamps();

            $table->index(['outlet_id', 'refunded_at']);
        });

        // Log kiriman ke customer: satu baris per kiriman (reminder email digabung per customer per hari).
        Schema::create('b2b_reminder_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('b2b_customer_id')->constrained('b2b_customers');
            $table->enum('channel', ['email', 'whatsapp']);
            $table->enum('kind', ['reminder', 'invoice', 'receipt', 'registration'])->default('reminder')
                ->comment('reminder = pengingat tagihan; invoice = invoice pesanan tempo; receipt = kwitansi invoice lunas; registration = konfirmasi pendaftaran lewat form umum (tanpa invoice)');
            $table->string('recipient')->nullable()->comment('email / nomor tujuan; kosong kalau data customer tidak lengkap');
            $table->date('reminder_date');
            $table->enum('trigger', ['auto', 'manual']);
            $table->enum('status', ['queued', 'sent', 'failed', 'skipped'])->default('queued');
            // Anti dobel kiriman otomatis: auto:{customer}:{channel}:{tanggal} (reminder), registration:{customer}:{channel}
            // (konfirmasi pendaftaran). Manual = null, boleh berulang.
            $table->string('dedupe_key', 80)->nullable()->unique();
            $table->string('subject')->nullable();
            $table->longText('body')->nullable()->comment('isi email/pesan yang dikirim, untuk log');
            $table->json('attachments')->nullable()->comment('nama file lampiran');
            $table->string('provider_message_id')->nullable();
            $table->unsignedTinyInteger('attempts')->default(0);
            $table->text('error')->nullable();
            $table->foreignId('sent_by')->nullable()->constrained('users')->nullOnDelete()->comment('diisi untuk kiriman manual');
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();

            $table->index(['reminder_date', 'status']);
            $table->index(['b2b_customer_id', 'reminder_date']);
        });

        Schema::create('b2b_reminder_log_invoices', function (Blueprint $table) {
            $table->id();
            // Nama constraint ditulis manual: nama otomatis melebihi batas 64 karakter MySQL.
            $table->foreignId('b2b_reminder_log_id')->constrained('b2b_reminder_logs', indexName: 'b2b_rli_log_fk')->cascadeOnDelete();
            $table->foreignId('b2b_invoice_id')->constrained('b2b_invoices', indexName: 'b2b_rli_invoice_fk');
            $table->foreignId('b2b_invoice_schedule_id')->nullable()->constrained('b2b_invoice_schedules', indexName: 'b2b_rli_schedule_fk');
            $table->smallInteger('offset_days')->comment('hari relatif ke jatuh tempo: -3 = H-3, 2 = lewat 2 hari');

            $table->unique(['b2b_reminder_log_id', 'b2b_invoice_id'], 'b2b_rli_log_invoice_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('b2b_reminder_log_invoices');
        Schema::dropIfExists('b2b_reminder_logs');
        Schema::dropIfExists('b2b_refunds');
        Schema::dropIfExists('b2b_payment_allocations');
        Schema::dropIfExists('b2b_payments');
        Schema::dropIfExists('b2b_invoice_schedules');
        Schema::dropIfExists('b2b_invoices');
    }
};
