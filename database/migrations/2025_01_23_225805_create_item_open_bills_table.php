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
        Schema::create('item_open_bills', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('open_bill_id');
            $table->text('catatan')->nullable();
            $table->json('diskon');
            $table->integer('harga');
            $table->string('product_id');
            $table->string('variant_id');
            $table->json('modifier');
            $table->string('nama_product');
            $table->string('nama_variant');
            $table->json('pilihan');
            $table->json('promo');
            $table->string('quantity');
            $table->integer('result_total');
            $table->string('sales_type')->nullable();
            $table->string('tmp_id');
            $table->unsignedBigInteger('queue_order');
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('open_bill_id')->references('id')->on('open_bills')->onDelete('CASCADE');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('item_open_bills');
    }
};
