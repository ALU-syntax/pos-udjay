<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ot_outlet_settings', function (Blueprint $table) {
            $table->engine = 'InnoDB';

            $table->id();
            $table->unsignedBigInteger('outlet_id')->unique();
            $table->boolean('order_enabled');
            $table->enum('stock_mode', ['off', 'status_only', 'strict'])->default('status_only');
            $table->time('open_time')->nullable();
            $table->time('close_time')->nullable();
            $table->boolean('forced_close');
            $table->decimal('service_fee_pct', 5, 2)->nullable();
            $table->integer('auto_preparing_delay_seconds')->default(30);
            $table->time('session_close_time')->default('23:59:00');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ot_outlet_settings');
    }
};
