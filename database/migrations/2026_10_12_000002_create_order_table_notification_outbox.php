<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_table_events', function (Blueprint $t) {
            $t->id();
            $t->uuid('event_id')->unique();
            $t->string('payload_hash', 64);
            $t->unsignedBigInteger('order_id')->index();
            $t->unsignedBigInteger('outlet_id')->index();
            $t->string('type', 64);
            $t->json('payload');
            $t->timestamps();
        });
        Schema::create('order_table_push_deliveries', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('event_id');
            $t->unsignedBigInteger('device_id');
            $t->unsignedBigInteger('shift_session_id');
            $t->string('token_hash', 64);
            $t->string('status', 16)->default('pending');
            $t->unsignedInteger('attempts')->default(0);
            $t->timestamp('available_at')->index();
            $t->timestamp('locked_until')->nullable();
            $t->uuid('lease')->nullable();
            $t->string('last_error', 64)->nullable();
            $t->timestamps();
            $t->unique(['event_id', 'device_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_table_push_deliveries');
        Schema::dropIfExists('order_table_events');
    }
};
