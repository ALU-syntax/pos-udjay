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
        Schema::create('promos', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('outlet_id');
            $table->enum('type', ['discount', 'free-item']);
            $table->json('sales_type')->nullable();
            $table->enum('purchase_requirement', ['any_item', 'any_category']);
            $table->json('product_requirement')->nullable();
            $table->json('reward');
            $table->boolean('multiple')->nullable();
            $table->boolean('status')->nullable();
            $table->date('promo_date_periode_start')->nullable();
            $table->date('promo_date_periode_end')->nullable();
            $table->time('promo_time_periode_start')->nullable();
            $table->time('promo_time_periode_end')->nullable();
            $table->json('day_allowed')->nullable();
            $table->softDeletes();
            $table->foreign('outlet_id')->references('id')->on('outlets')->onDelete('CASCADE');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('promos');
    }
};
