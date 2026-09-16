<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Composite index untuk kebutuhan delta sync reward_confirmations
     * ke mobile (Room/SQLite).
     *
     * Tabel ini sudah memiliki kolom softDeletes (deleted_at), jadi migrasi ini
     * hanya menambahkan index — bukan kolom baru.
     *
     * Query sync:
     *   WHERE (updated_at > :since) OR (updated_at = :since AND id > :lastId)
     *   ORDER BY updated_at ASC, id ASC
     *   LIMIT :limit
     */
    public function up(): void
    {
        Schema::table('reward_confirmations', function (Blueprint $table) {
            $table->index(['updated_at', 'id'], 'reward_confirmations_updated_at_id_index');
        });
    }

    public function down(): void
    {
        Schema::table('reward_confirmations', function (Blueprint $table) {
            $table->dropIndex('reward_confirmations_updated_at_id_index');
        });
    }
};
