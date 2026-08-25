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
        Schema::create('shift_sessions', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('petty_cash_id')
                ->comment('ID petty cash yang digunakan untuk shift');

            $table->unsignedBigInteger('outlet_id')
                ->comment('ID outlet tempat shift berlangsung');

            $table->unsignedBigInteger('user_id')
                ->comment('ID user yang menjalankan shift');

            $table->unsignedBigInteger('parent_session_id')
                ->nullable()
                ->default(null)
                ->comment('ID session induk. NULL = parent session, terisi = child session');

            $table->enum('status', ['ACTIVE', 'CLOSED'])
                ->default('ACTIVE')
                ->comment('Status shift session');

            $table->string('device_name', 100)
                ->nullable()
                ->default(null)
                ->comment('Nama perangkat Android, misalnya Samsung Galaxy Tab A8');

            $table->string('android_id', 64)
                ->comment('Android ID dari Settings.Secure.ANDROID_ID');

            $table->dateTime('last_sync_at')
                ->nullable()
                ->default(null)
                ->comment('Waktu terakhir device melakukan sinkronisasi');

            $table->dateTime('closed_at')
                ->nullable()
                ->default(null)
                ->comment('Waktu shift ditutup');

            $table->timestamps();

            // Unique constraint — satu device hanya boleh punya satu session per petty cash
            $table->unique(['petty_cash_id', 'android_id'], 'uq_session_per_device');

            // Indexes
            $table->index(['petty_cash_id', 'status'], 'idx_shift_sessions_petty_cash_status');
            $table->index('parent_session_id', 'idx_shift_sessions_parent');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('shift_sessions');
    }
};
