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
        Schema::create('log_error_androids', function (Blueprint $table) {
            $table->id();
            $table->timestamp('timestamp');
            $table->text('error_message');
            $table->text('stack_trace');
            $table->json('device_info');
            $table->json('user_info');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('log_error_androids');
    }
};
