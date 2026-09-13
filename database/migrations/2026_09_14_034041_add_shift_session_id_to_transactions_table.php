<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Kolom shift_session_id pada transactions + composite index untuk delta sync per-shift.
     *
     * - shift_session_id menandai device/sesi mana yang membuat transaksi
     * - Index (patty_cash_id, updated_at, id, shift_session_id):
     *     * patty_cash_id  : equality filter (scope 1 shift)
     *     * updated_at, id : keyset pagination + ordering tanpa filesort
     *     * shift_session_id diletakkan di akhir agar ordering tetap terjaga
     */
    public function up(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            if (!Schema::hasColumn('transactions', 'shift_session_id')) {
                $table->unsignedBigInteger('shift_session_id')->nullable()->after('patty_cash_id');
            }
        });

        Schema::table('transactions', function (Blueprint $table) {
            $table->index(
                ['patty_cash_id', 'updated_at', 'id', 'shift_session_id'],
                'transactions_patty_cash_updated_id_session_index'
            );
        });
    }

    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropIndex('transactions_patty_cash_updated_id_session_index');
        });

        Schema::table('transactions', function (Blueprint $table) {
            if (Schema::hasColumn('transactions', 'shift_session_id')) {
                $table->dropColumn('shift_session_id');
            }
        });
    }
};
