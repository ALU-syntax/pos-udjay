<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ot_banners', function (Blueprint $table) {
            $table->engine = 'InnoDB';

            $table->id();
            $table->unsignedBigInteger('outlet_id')->nullable()->index();
            $table->string('title');
            $table->string('image_url', 500);
            $table->enum('action_type', ['url', 'internal', 'product', 'category', 'voucher', 'promo']);
            $table->string('action_value', 500)->nullable();
            $table->string('position', 50);
            $table->integer('sort_order')->default(0);
            $table->timestamp('start_at')->nullable();
            $table->timestamp('end_at')->nullable();
            $table->boolean('status')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ot_banners');
    }
};
