<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::table('suppliers')->where('procurement_mode', 'offline')->update(['procurement_mode' => 1]);
        DB::table('suppliers')->where('procurement_mode', 'online')->update(['procurement_mode' => 2]);
        DB::table('suppliers')->where('procurement_mode', 'both')->update(['procurement_mode' => 3]);

        DB::statement("ALTER TABLE `suppliers` MODIFY `procurement_mode` ENUM('1','2','3') NOT NULL DEFAULT '3'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("ALTER TABLE `suppliers` MODIFY `procurement_mode` VARCHAR(20) NOT NULL DEFAULT 'both'");

        DB::table('suppliers')->where('procurement_mode', '1')->update(['procurement_mode' => 'offline']);
        DB::table('suppliers')->where('procurement_mode', '2')->update(['procurement_mode' => 'online']);
        DB::table('suppliers')->where('procurement_mode', '3')->update(['procurement_mode' => 'both']);
    }
};
