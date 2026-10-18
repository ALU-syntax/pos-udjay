<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_table_payment_claims', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            // Deliberately no cascading FK: committed collection evidence must survive deletion.
            $table->unsignedBigInteger('order_id')->primary();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('shift_session_id');
            $table->string('device_id', 64);
            $table->uuid('claim_token');
            $table->string('snapshot_hash', 64)->nullable();
            $table->timestamp('expires_at');
            $table->unsignedBigInteger('transaction_id')->nullable()->unique();
            $table->timestamps();
        });
        Schema::create('order_table_devices', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('access_token_id')->unique();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('outlet_id')->index();
            $table->unsignedBigInteger('shift_session_id');
            $table->string('device_id', 64);
            $table->text('fcm_token')->nullable();
            $table->string('fcm_token_hash', 64)->nullable()->unique();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_table_devices');
        Schema::dropIfExists('order_table_payment_claims');
    }
};
