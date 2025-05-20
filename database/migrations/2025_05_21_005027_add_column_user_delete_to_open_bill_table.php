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
        Schema::table('open_bills', function (Blueprint $table) {
            $table->unsignedBigInteger('id_user_deleted')->nullable();

            $table->foreign('id_user_deleted')->references('id')->on('users')->onDelete('CASCADE');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('open_bills', function (Blueprint $table) {
            //
        });
    }
};
