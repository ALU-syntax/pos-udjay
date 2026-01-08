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
        Schema::create('reward_level_membership_products', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('reward_membership_id');
            $table->unsignedBigInteger('product_id');
            $table->unsignedBigInteger('outlet_id');
            $table->softDeletes();
            $table->timestamps();

            $table->foreign('reward_membership_id')->references('id')->on('reward_memberships')->onDelete('CASCADE');
            $table->foreign('product_id')->references('id')->on('products')->onDelete('CASCADE');
            $table->foreign('outlet_id')->references('id')->on('outlets')->onDelete('CASCADE');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reward_level_membership_products');
    }
};
