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
        Schema::table('reward_confirmations', function (Blueprint $table) {
            $table->unsignedBigInteger('outlet_id')->nullable();
            $table->json('snapshot')->nullable();
            $table->unsignedInteger('level_batch')->nullable();
            $table->softDeletes();

            $table->foreign('outlet_id')->references('id')->on('outlets')->onDelete('CASCADE');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('reward_confirmations', function (Blueprint $table) {
            //
        });
    }
};
