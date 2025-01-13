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
        Schema::create('transaction_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('product_id')->nullable();
            $table->unsignedBigInteger('variant_id')->nullable();
            $table->json('discount_id')->nullable();
            $table->json('modifier_id')->nullable();
            $table->json('promo_id')->nullable();
            $table->unsignedBigInteger('sales_type_id')->nullable();
            // $table->integer('quantity');
            $table->unsignedBigInteger('transaction_id');
            $table->string('catatan')->nullable();
            $table->softDeletes();
            $table->timestamps();
            $table->boolean('reward_item')->default(false);

            $table->foreign('sales_type_id')->references('id')->on('sales_types')->onDelete('CASCADE');
            $table->foreign('product_id')->references('id')->on('products')->onDelete('CASCADE');
            $table->foreign('transaction_id')->references('id')->on('transactions')->onDelete('CASCADE');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transaction_items');
    }
};
