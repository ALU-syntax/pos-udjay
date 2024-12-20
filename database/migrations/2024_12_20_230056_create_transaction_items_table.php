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
            $table->json('product_id');
            $table->json('discount_id');
            $table->json('modifier_id');
            $table->integer('quantity');
            $table->unsignedBigInteger('transaction_id');
            $table->softDeletes();
            $table->timestamps();
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
