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
        Schema::create('pilihans', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->bigInteger('harga');
            $table->bigInteger('stok')->nullable();
            $table->unsignedBigInteger('pilihan_group_id');
            $table->softDeletes();
            $table->foreign('pilihan_group_id')->references('id')->on('pilihan_groups')->onDelete('CASCADE');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pilihans');
    }
};
