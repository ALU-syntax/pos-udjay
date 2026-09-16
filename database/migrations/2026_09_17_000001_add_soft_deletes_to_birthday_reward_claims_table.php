<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Soft delete + composite index untuk kebutuhan delta sync
     * birthday_reward_claims ke mobile (Room/SQLite).
     *
     * Soft delete dipakai agar baris yang dihapus di server tetap terkirim
     * ke mobile (dengan flag is_deleted) sehingga cache lokal ikut terhapus.
     *
     * Query sync:
     *   WHERE (updated_at > :since) OR (updated_at = :since AND id > :lastId)
     *   ORDER BY updated_at ASC, id ASC
     *   LIMIT :limit
     */
    public function up(): void
    {
        Schema::table('birthday_reward_claims', function (Blueprint $table) {
            $table->softDeletes();
            $table->index(['updated_at', 'id'], 'birthday_reward_claims_updated_at_id_index');
        });
    }

    public function down(): void
    {
        Schema::table('birthday_reward_claims', function (Blueprint $table) {
            $table->dropIndex('birthday_reward_claims_updated_at_id_index');
            $table->dropSoftDeletes();
        });
    }
};
