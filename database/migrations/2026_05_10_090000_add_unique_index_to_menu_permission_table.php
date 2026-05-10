<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $duplicates = DB::table('menu_permission')
            ->select('menu_id', 'permission_id', DB::raw('MIN(id) as keep_id'))
            ->groupBy('menu_id', 'permission_id')
            ->havingRaw('COUNT(*) > 1')
            ->get();

        foreach ($duplicates as $duplicate) {
            DB::table('menu_permission')
                ->where('menu_id', $duplicate->menu_id)
                ->where('permission_id', $duplicate->permission_id)
                ->where('id', '!=', $duplicate->keep_id)
                ->delete();
        }

        Schema::table('menu_permission', function (Blueprint $table) {
            $table->unique(['menu_id', 'permission_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('menu_permission', function (Blueprint $table) {
            $table->dropUnique('menu_permission_menu_id_permission_id_unique');
        });
    }
};
