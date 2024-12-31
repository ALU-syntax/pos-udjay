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
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('outlet_id');
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('customer_id')->nullable();
            $table->bigInteger('total')->nullable();
            $table->bigInteger('nominal_bayar')->nullable();
            $table->bigInteger('change')->nullable();
            $table->unsignedBigInteger('category_payment_id')->nullable();
            $table->unsignedBigInteger('tipe_pembayaran')->nullable();
            $table->string('nama_tipe_pembayaran')->nullable();            
            $table->json('total_pajak')->nullable();
            $table->bigInteger('total_modifier')->nullable();
            $table->bigInteger('total_diskon')->nullable();
            $table->json('diskon_all_item')->nullable();
            $table->bigInteger('rounding_amount')->nullable();
            $table->string('tanda_rounding')->nullable();
            $table->text('catatan')->nullable();
            $table->softDeletes();
            $table->timestamps();
            
            $table->foreign('category_payment_id')->references('id')->on('category_payments')->onDelete('CASCADE');
            $table->foreign('tipe_pembayaran')->references('id')->on('payments')->onDelete('CASCADE');
            $table->foreign('outlet_id')->references('id')->on('outlets')->onDelete('CASCADE');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('CASCADE');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
