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
        Schema::table('satuans', function (Blueprint $table) {
            if (!Schema::hasColumn('satuans', 'code')) {
                $table->string('code', 50)->nullable()->unique()->after('id');
            }

            if (!Schema::hasColumn('satuans', 'symbol')) {
                $table->string('symbol', 20)->nullable()->after('name');
            }

            if (!Schema::hasColumn('satuans', 'unit_type')) {
                $table->string('unit_type', 50)->nullable()->after('symbol');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('satuans', function (Blueprint $table) {
            if (Schema::hasColumn('satuans', 'unit_type')) {
                $table->dropColumn('unit_type');
            }

            if (Schema::hasColumn('satuans', 'symbol')) {
                $table->dropColumn('symbol');
            }

            if (Schema::hasColumn('satuans', 'code')) {
                $table->dropUnique(['code']);
                $table->dropColumn('code');
            }
        });
    }
};
