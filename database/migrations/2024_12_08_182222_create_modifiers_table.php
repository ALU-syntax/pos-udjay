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
        Schema::create('modifiers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->bigInteger('harga');
            $table->bigInteger('stok');
            $table->unsignedBigInteger('modifiers_group_id');
            $table->timestamps();
            $table->softDeletes();
            $table->foreign('modifiers_group_id')->references('id')->on('modifier_groups')->onDelete('CASCADE');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('modifiers');
    }
};
