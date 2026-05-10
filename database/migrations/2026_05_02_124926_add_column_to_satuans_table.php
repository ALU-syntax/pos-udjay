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
            if (!Schema::hasColumn('satuans', 'symbol')) {
                $table->string('symbol', 20)->nullable()->after('name'); // contoh: pcs, kg, g, ml
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('satuans', function (Blueprint $table) {
            if (Schema::hasColumn('satuans', 'symbol')) {
                $table->dropColumn('symbol');
            }
        });
    }
};
