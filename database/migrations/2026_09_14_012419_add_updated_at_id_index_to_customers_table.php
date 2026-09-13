<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Composite index untuk kebutuhan delta sync customer ke mobile (Room/SQLite).
     *
     * Query sync:
     *   WHERE (updated_at > :since) OR (updated_at = :since AND id > :lastId)
     *   ORDER BY updated_at ASC, id ASC
     *   LIMIT :limit
     *
     * Index (updated_at, id) membuat range scan + ordering terpakai langsung
     * tanpa filesort, sekaligus mendukung keyset pagination berbasis id.
     */
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->index(['updated_at', 'id'], 'customers_updated_at_id_index');
        });
    }

    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropIndex('customers_updated_at_id_index');
        });
    }
};
