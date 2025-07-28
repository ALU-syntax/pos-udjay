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
        Schema::create('note_receipt_schedulings', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('message');
            $table->time('start');
            $table->time('end');
            $table->unsignedBigInteger('outlet_id');
            $table->json('product_id')->nullable();
            $table->boolean('status')->default(true);
            $table->softDeletes();
            $table->timestamps();

            $table->foreign('outlet_id')->references('id')->on('outlets')->onDelete('CASCADE');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('note_receipt_schedulings');
    }
};
