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
        Schema::create('membership_registration_links', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code');
            $table->unsignedBigInteger('outlet_id');
            $table->timestamps();

            $table->foreign('outlet_id')->references('id')->on('outlets')->onDelete('CASCADE');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('membership_registration_links');
    }
};
