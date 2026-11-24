<?php

namespace Database\Seeders;

use App\Models\AccountProvider;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Isi awal master bank & e-wallet. Aman dijalankan ulang: baris yang ada diperbarui nama/jenis/urutannya,
 * status aktif yang sudah diubah tidak ditimpa. Kode jangan diubah karena tersimpan di rekening customer.
 */
class AccountProviderSeeder extends Seeder
{
    /** kode => [jenis, nama tampil] */
    public const PROVIDERS = [
        'bca' => [AccountProvider::TYPE_BANK, 'BCA'],
        'mandiri' => [AccountProvider::TYPE_BANK, 'Mandiri'],
        'bri' => [AccountProvider::TYPE_BANK, 'BRI'],
        'bni' => [AccountProvider::TYPE_BANK, 'BNI'],
        'bsi' => [AccountProvider::TYPE_BANK, 'BSI'],
        'cimb' => [AccountProvider::TYPE_BANK, 'CIMB Niaga'],
        'danamon' => [AccountProvider::TYPE_BANK, 'Danamon'],
        'permata' => [AccountProvider::TYPE_BANK, 'Permata'],
        'btn' => [AccountProvider::TYPE_BANK, 'BTN'],
        'ocbc' => [AccountProvider::TYPE_BANK, 'OCBC'],
        'panin' => [AccountProvider::TYPE_BANK, 'Panin'],
        'maybank' => [AccountProvider::TYPE_BANK, 'Maybank'],
        'mega' => [AccountProvider::TYPE_BANK, 'Bank Mega'],
        'jenius' => [AccountProvider::TYPE_BANK, 'Jenius / SMBC Indonesia'],
        'jago' => [AccountProvider::TYPE_BANK, 'Bank Jago'],
        'seabank' => [AccountProvider::TYPE_BANK, 'SeaBank'],
        'blu' => [AccountProvider::TYPE_BANK, 'blu by BCA Digital'],
        'bjb' => [AccountProvider::TYPE_BANK, 'Bank BJB'],
        'dki' => [AccountProvider::TYPE_BANK, 'Bank DKI'],
        'jateng' => [AccountProvider::TYPE_BANK, 'Bank Jateng'],
        'jatim' => [AccountProvider::TYPE_BANK, 'Bank Jatim'],
        'gopay' => [AccountProvider::TYPE_EWALLET, 'GoPay'],
        'ovo' => [AccountProvider::TYPE_EWALLET, 'OVO'],
        'dana' => [AccountProvider::TYPE_EWALLET, 'DANA'],
        'shopeepay' => [AccountProvider::TYPE_EWALLET, 'ShopeePay'],
        'linkaja' => [AccountProvider::TYPE_EWALLET, 'LinkAja'],
    ];

    public function run(): void
    {
        $now = now();
        $rows = [];
        $order = 0;
        foreach (self::PROVIDERS as $code => [$type, $name]) {
            $rows[] = ['code' => $code, 'type' => $type, 'name' => $name, 'sort_order' => $order += 10, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now];
        }

        DB::table('account_providers')->upsert($rows, ['code'], ['type', 'name', 'sort_order', 'updated_at']);
        AccountProvider::flushCache();
    }
}
