<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ot_table_sessions', function (Blueprint $table) {
            $table->engine = 'InnoDB';

            $table->id();
            $table->char('device_id', 36);
            $table->foreignId('table_id')
                ->constrained('ot_dining_tables')
                ->cascadeOnDelete();
            $table->unsignedBigInteger('outlet_id');
            $table->enum('status', ['open', 'closed'])->default('open');
            $table->timestamp('opened_at');
            $table->timestamp('closed_at')->nullable();
            $table->enum('close_reason', ['eod', 'kasir', 'manual'])->nullable();
            $table->enum('closed_by', ['system', 'kasir'])->nullable();
            $table->timestamps();

            $table->index(['device_id', 'status']);
            $table->index(['table_id', 'status']);
            $table->index(['outlet_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ot_table_sessions');
    }
};
