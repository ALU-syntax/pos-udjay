<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Voucher lama menyimpan NULL tetapi berlaku 1× per perangkat (dijaga index UNIQUE).
        // NULL kini berarti tanpa batas, jadi perilaku lama dipertahankan dengan mengisinya 1.
        DB::table('ot_vouchers')->whereNull('quota_per_device')->update(['quota_per_device' => 1]);

        // Index biasa dibuat lebih dulu agar hitungan pemakaian tetap cepat dan foreign key
        // voucher_id tetap punya index saat UNIQUE dilepas.
        Schema::table('ot_voucher_redemptions', function (Blueprint $table) {
            $table->index(['voucher_id', 'device_id']);
            $table->index(['voucher_id', 'session_id']);
        });

        // Batas per perangkat/sesi kini dihitung Node dari quota_per_device / quota_per_session.
        Schema::table('ot_voucher_redemptions', function (Blueprint $table) {
            $table->dropUnique(['voucher_id', 'device_id']);
            $table->dropUnique(['voucher_id', 'session_id']);
        });
    }

    public function down(): void
    {
        // Gagal bila sudah ada perangkat/sesi yang memakai satu voucher lebih dari sekali.
        Schema::table('ot_voucher_redemptions', function (Blueprint $table) {
            $table->unique(['voucher_id', 'device_id']);
            $table->unique(['voucher_id', 'session_id']);
        });

        Schema::table('ot_voucher_redemptions', function (Blueprint $table) {
            $table->dropIndex(['voucher_id', 'device_id']);
            $table->dropIndex(['voucher_id', 'session_id']);
        });
    }
};
