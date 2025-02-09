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
        Schema::create('reward_confirmations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('level_membership_id');
            $table->unsignedBigInteger('reward_memberships_id');
            $table->unsignedBigInteger('customer_id');
            $table->unsignedBigInteger('user_id');
            $table->string('photo')->nullable();
            $table->timestamps();

            $table->foreign('level_membership_id')->references('id')->on('level_memberships')->onDelete('CASCADE');
            $table->foreign('reward_memberships_id')->references('id')->on('reward_memberships')->onDelete('CASCADE');
            $table->foreign('customer_id')->references('id')->on('customers')->onDelete('CASCADE');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('CASCADE');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reward_confirmations');
    }
};
