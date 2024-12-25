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
            $table->unsignedBigInteger('cutomer_id')->nullable();
            $table->bigInteger('total')->nullable();
            $table->bigInteger('change')->nullable();
            $table->string('tipe_pembayaran');
            $table->json('total_pajak');
            $table->bigInteger('total_modifier');
            $table->bigInteger('total_diskon');
            $table->bigInteger('rounding_amount');
            $table->softDeletes();
            $table->timestamps();
            
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
