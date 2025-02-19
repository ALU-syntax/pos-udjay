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
        Schema::table('communities', function (Blueprint $table) {
            $table->unsignedBigInteger('exp')->default(0);
            $table->string('domisili');
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('outlet_id');

            $table->foreign('user_id')->references('id')->on('users')->onDelete('CASCADE');
            $table->foreign('outlet_id')->references('id')->on('outlets')->onDelete('CASCADE');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('communities', function (Blueprint $table) {
            //
        });
    }
};
