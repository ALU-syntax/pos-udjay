<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ot_order_items', function (Blueprint $table) {
            $table->engine = 'InnoDB';

            $table->id();
            $table->foreignId('order_id')
                ->constrained('ot_orders')
                ->cascadeOnDelete();
            $table->unsignedBigInteger('product_id')->index();
            $table->unsignedBigInteger('variant_id')->nullable()->index();
            $table->string('product_name');
            $table->string('variant_name')->nullable();
            $table->bigInteger('unit_price');
            $table->integer('qty');
            $table->bigInteger('modifier_total');
            $table->bigInteger('line_total');
            $table->boolean('exclude_tax');
            $table->text('notes')->nullable();
            $table->enum('status', ['pending', 'preparing', 'served', 'cancelled']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ot_order_items');
    }
};
