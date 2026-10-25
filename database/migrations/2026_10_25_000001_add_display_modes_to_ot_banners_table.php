<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Widen first so rows using the dropped action types can be moved to 'none'.
        DB::statement("ALTER TABLE ot_banners MODIFY action_type ENUM('none','url','internal','product','category','voucher','promo') NOT NULL");
        DB::table('ot_banners')
            ->whereIn('action_type', ['internal', 'promo'])
            ->update(['action_type' => 'none', 'action_value' => null]);
        DB::statement("ALTER TABLE ot_banners MODIFY action_type ENUM('none','url','product','category','voucher') NOT NULL DEFAULT 'none'");
        DB::statement('ALTER TABLE ot_banners MODIFY image_url VARCHAR(500) NULL');

        Schema::table('ot_banners', function (Blueprint $table) {
            $table->enum('display_mode', ['image', 'text'])->default('image')->after('title');
            $table->string('eyebrow', 40)->nullable()->after('display_mode');
            $table->string('subtitle', 120)->nullable()->after('eyebrow');
            $table->enum('tone', ['black', 'red', 'coffee', 'yellow'])->nullable()->after('subtitle');
            $table->dropColumn('position');
        });
    }

    public function down(): void
    {
        Schema::table('ot_banners', function (Blueprint $table) {
            $table->string('position', 50)->default('home_top')->after('action_value');
        });

        // Text cards without a background have no image to fall back to.
        DB::table('ot_banners')->whereNull('image_url')->update(['image_url' => '']);
        DB::statement('ALTER TABLE ot_banners MODIFY image_url VARCHAR(500) NOT NULL');
        DB::statement("ALTER TABLE ot_banners MODIFY action_type ENUM('none','url','internal','product','category','voucher','promo') NOT NULL");
        DB::table('ot_banners')
            ->where('action_type', 'none')
            ->update(['action_type' => 'internal', 'action_value' => 'menu']);
        DB::statement("ALTER TABLE ot_banners MODIFY action_type ENUM('url','internal','product','category','voucher','promo') NOT NULL");

        Schema::table('ot_banners', function (Blueprint $table) {
            $table->dropColumn(['display_mode', 'eyebrow', 'subtitle', 'tone']);
        });
    }
};
