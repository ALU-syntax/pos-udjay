<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ot_order_payments', function (Blueprint $table) {
            $table->engine = 'InnoDB';

            $table->id();
            $table->foreignId('order_id')
                ->constrained('ot_orders')
                ->cascadeOnDelete();
            $table->enum('method', ['qris', 'cashier', 'gateway']);
            $table->string('gateway_ref', 100)->nullable()->unique();
            $table->bigInteger('amount');
            $table->enum('status', ['pending', 'paid', 'failed', 'expired']);
            $table->text('qr_string')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->json('raw_callback')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ot_order_payments');
    }
};
