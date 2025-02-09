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
        Schema::table('customers', function (Blueprint $table) {
            $table->unsignedBigInteger('exp')->default(0);
            $table->unsignedBigInteger('point')->default(0);
            $table->unsignedBigInteger('referral_id')->nullable();
            $table->unsignedBigInteger('level_memberships_id');

            $table->foreign('community_id')->references('id')->on('communities')->onDelete('CASCADE');
            $table->foreign('referral_id')->references('id')->on('customers')->onDelete('CASCADE');
            $table->foreign('level_memberships_id')->references('id')->on('level_memberships')->onDelete('CASCADE');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            //
        });
    }
};
