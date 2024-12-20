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
        Schema::create('petty_cashes', function (Blueprint $table) {
            $table->id();
            $table->string('outlet_id');
            $table->string('amount_awal')->nullable();
            $table->string('amount_akhir')->nullable();
            $table->string('user_id_started')->nullable();
            $table->string('user_id_ended')->nullable();
            $table->dateTime('open')->nullable();
            $table->dateTime('close')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('petty_cashes');
    }
};
