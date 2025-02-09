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
        Schema::create('customer_poin_exps', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('customer_id');
            $table->unsignedBigInteger('point')->default(0);
            $table->unsignedBigInteger('exp')->default(0);
            $table->unsignedBigInteger('transaction_id')->nullable();
            $table->unsignedBigInteger('referee_id')->nullable();
            $table->text('log');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customer_poin_exps');
    }
};
