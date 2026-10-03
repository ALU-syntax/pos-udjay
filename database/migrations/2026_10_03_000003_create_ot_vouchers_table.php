<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ot_vouchers', function (Blueprint $table) {
            $table->engine = 'InnoDB';

            $table->id();
            $table->unsignedBigInteger('outlet_id')->nullable()->index();
            $table->string('code', 50)->unique();
            $table->enum('type', ['percent', 'fixed']);
            $table->bigInteger('value');
            $table->bigInteger('min_spend')->default(0);
            $table->bigInteger('max_discount')->nullable();
            $table->integer('quota_total')->nullable();
            $table->integer('quota_per_device')->nullable();
            $table->integer('quota_per_session')->nullable();
            $table->integer('quota_used')->default(0);
            $table->timestamp('valid_from')->nullable();
            $table->timestamp('valid_to')->nullable();
            $table->enum('scope', ['all', 'category', 'product'])->default('all');
            $table->json('scope_ids')->nullable();
            $table->boolean('status')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ot_vouchers');
    }
};
