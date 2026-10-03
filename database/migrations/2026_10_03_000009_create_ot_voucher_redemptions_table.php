<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ot_voucher_redemptions', function (Blueprint $table) {
            $table->engine = 'InnoDB';

            $table->id();
            $table->foreignId('voucher_id')
                ->constrained('ot_vouchers')
                ->cascadeOnDelete();
            $table->foreignId('order_id')
                ->constrained('ot_orders')
                ->cascadeOnDelete();
            $table->foreignId('session_id')
                ->constrained('ot_table_sessions')
                ->cascadeOnDelete();
            $table->char('device_id', 36);
            $table->bigInteger('discount_amount');
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->unique(['voucher_id', 'order_id']);
            $table->unique(['voucher_id', 'device_id']);
            $table->unique(['voucher_id', 'session_id']);
            $table->index('device_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ot_voucher_redemptions');
    }
};
