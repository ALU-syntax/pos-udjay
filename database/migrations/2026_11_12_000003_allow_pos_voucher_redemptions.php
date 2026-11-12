<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Voucher Order Table kini bisa dipakai di transaksi POS reguler (kasir), yang tidak punya
     * order, sesi meja, maupun perangkat pelanggan. Pemakaian itu dicatat di tabel yang sama
     * dengan `source = pos` dan `pos_reference` = reference_id transaksi POS.
     */
    public function up(): void
    {
        // MODIFY tidak menyentuh foreign key; kolomnya hanya dibuat boleh NULL untuk baris POS.
        DB::statement('ALTER TABLE ot_voucher_redemptions
            MODIFY order_id BIGINT UNSIGNED NULL,
            MODIFY session_id BIGINT UNSIGNED NULL,
            MODIFY device_id CHAR(36) NULL');

        Schema::table('ot_voucher_redemptions', function (Blueprint $table) {
            $table->string('source', 20)->default('order_table')->after('voucher_id');
            $table->unsignedBigInteger('outlet_id')->nullable()->after('source');
            $table->string('pos_reference', 100)->nullable()->after('outlet_id');

            // Satu transaksi POS hanya memakai satu voucher; juga membuat pencatatan ulang aman.
            $table->unique(['outlet_id', 'pos_reference']);
        });
    }

    public function down(): void
    {
        // Baris POS tidak punya order/sesi/perangkat, jadi tidak bisa bertahan di skema lama.
        $posRows = DB::table('ot_voucher_redemptions')->where('source', 'pos');
        foreach ((clone $posRows)->selectRaw('voucher_id, COUNT(*) AS used')->groupBy('voucher_id')->get() as $row) {
            DB::table('ot_vouchers')->where('id', $row->voucher_id)
                ->update(['quota_used' => DB::raw('GREATEST(quota_used - '.(int) $row->used.', 0)')]);
        }
        $posRows->delete();

        Schema::table('ot_voucher_redemptions', function (Blueprint $table) {
            $table->dropUnique(['outlet_id', 'pos_reference']);
            $table->dropColumn(['source', 'outlet_id', 'pos_reference']);
        });

        DB::statement('ALTER TABLE ot_voucher_redemptions
            MODIFY order_id BIGINT UNSIGNED NOT NULL,
            MODIFY session_id BIGINT UNSIGNED NOT NULL,
            MODIFY device_id CHAR(36) NOT NULL');
    }
};
