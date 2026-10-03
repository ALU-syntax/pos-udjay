<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ot_dining_tables', function (Blueprint $table) {
            $table->unique(['outlet_id', 'code'], 'ot_dining_tables_outlet_code_unique');
        });
    }

    public function down(): void
    {
        Schema::table('ot_dining_tables', function (Blueprint $table) {
            $table->dropUnique('ot_dining_tables_outlet_code_unique');
        });
    }
};
