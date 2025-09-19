<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::statement('ALTER TABLE customers MODIFY exp BIGINT NULL DEFAULT 0;');
        DB::statement('ALTER TABLE customers MODIFY point BIGINT NULL DEFAULT 0;');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // ⚠️ sama: akan error jika ada nilai negatif
        DB::statement('ALTER TABLE customers MODIFY exp BIGINT UNSIGNED NULL DEFAULT 0;');
        DB::statement('ALTER TABLE customers MODIFY point BIGINT UNSIGNED NULL DEFAULT 0;');
    }
};
