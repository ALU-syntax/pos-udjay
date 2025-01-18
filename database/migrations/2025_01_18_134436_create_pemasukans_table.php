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
        Schema::create('pemasukans', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('outlet_id');
            $table->unsignedBigInteger('kategori_pemasukan_id');
            $table->unsignedBigInteger('customer_id')->nullable();
            $table->bigInteger('jumlah');
            $table->string('photo')->nullable();
            $table->date('tanggal')->nullable();
            $table->text('catatan')->nullable();
            $table->unsignedBigInteger('user_id');
            $table->timestamps();

            $table->foreign('outlet_id')->references('id')->on('outlets')->onDelete('CASCADE');
            $table->foreign('kategori_pemasukan_id')->references('id')->on('kategori_pemasukans')->onDelete('CASCADE');
            $table->foreign('customer_id')->references('id')->on('customers')->onDelete('CASCADE');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('CASCADE');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pemasukans');
    }
};
