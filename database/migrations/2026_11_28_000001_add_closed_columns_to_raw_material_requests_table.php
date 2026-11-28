<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Request order yang ditolak atau dibatalkan: siapa, kapan, dan alasannya.
        // Jenisnya dibaca dari status (rejected / cancelled), jadi cukup satu set kolom.
        Schema::table('raw_material_requests', function (Blueprint $table) {
            $table->unsignedBigInteger('closed_by')->nullable()->after('approved_at');
            $table->dateTime('closed_at')->nullable()->after('closed_by');
            $table->text('closed_remarks')->nullable()->after('closed_at');

            $table->foreign('closed_by', 'rm_requests_closed_by_foreign')
                ->references('id')
                ->on('users')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('raw_material_requests', function (Blueprint $table) {
            $table->dropForeign('rm_requests_closed_by_foreign');
            $table->dropColumn(['closed_by', 'closed_at', 'closed_remarks']);
        });
    }
};
