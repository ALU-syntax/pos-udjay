<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ot_orders', function (Blueprint $table) {
            $table->engine = 'InnoDB';

            $table->id();
            $table->string('order_no', 30)->unique();
            $table->unsignedBigInteger('outlet_id');
            $table->foreignId('session_id')
                ->constrained('ot_table_sessions')
                ->cascadeOnDelete();
            $table->char('device_id', 36);
            $table->foreignId('table_id')
                ->constrained('ot_dining_tables')
                ->cascadeOnDelete();
            $table->enum('status', [
                'draft',
                'placed',
                'received',
                'preparing',
                'served',
                'cancelled',
                'expired',
            ]);
            $table->enum('payment_mode', ['qris', 'pay_at_cashier']);
            $table->enum('payment_status', [
                'unpaid',
                'pending',
                'paid',
                'failed',
                'expired',
                'refunded',
            ]);
            $table->bigInteger('subtotal');
            $table->bigInteger('discount_item_total')->default(0);
            $table->bigInteger('voucher_discount')->default(0);
            $table->bigInteger('modifier_total')->default(0);
            $table->bigInteger('tax_total');
            $table->json('tax_breakdown')->nullable();
            $table->bigInteger('rounding')->default(0);
            $table->bigInteger('grand_total');
            $table->foreignId('voucher_id')
                ->nullable()
                ->constrained('ot_vouchers')
                ->nullOnDelete();
            $table->text('notes')->nullable();
            $table->decimal('lat', 10, 7)->nullable();
            $table->decimal('lng', 10, 7)->nullable();
            $table->string('accuracy', 20)->nullable();
            $table->enum('geofence_flag', ['inside', 'outside', 'unknown']);
            $table->enum('pos_bridge_status', ['pending', 'linked', 'failed'])->default('pending');
            $table->unsignedBigInteger('pos_transaction_id')->nullable()->index();
            $table->timestamp('placed_at')->nullable();
            $table->timestamp('payment_due_at')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();

            $table->index(['device_id', 'status']);
            $table->index(['outlet_id', 'status']);
            $table->index('payment_status');
            $table->index('pos_bridge_status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ot_orders');
    }
};
