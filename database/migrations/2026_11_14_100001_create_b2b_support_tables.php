<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Penomoran dokumen B2B (customer, kontrak, pesanan, invoice). Nomor diambil dengan lock baris.
        Schema::create('b2b_sequences', function (Blueprint $table) {
            $table->id();
            $table->string('type', 30);
            $table->string('period', 10)->comment('contoh: 2026 atau 202611; "all" untuk tanpa periode');
            $table->unsignedInteger('last_number')->default(0);
            $table->timestamps();

            $table->unique(['type', 'period']);
        });

        Schema::create('b2b_activity_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('subject_type', 100);
            $table->unsignedBigInteger('subject_id');
            $table->string('action', 60);
            $table->json('changes')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['subject_type', 'subject_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('b2b_activity_logs');
        Schema::dropIfExists('b2b_sequences');
    }
};
