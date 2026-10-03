<?php

namespace Tests\Feature\OrderTable;

use App\Models\OrderTable\DiningTable;
use App\Models\OrderTable\OutletSetting;
use App\Models\OrderTable\PaymentMethod;
use App\Models\Outlets;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

abstract class OrderTableTestCase extends TestCase
{
    use DatabaseTransactions;

    protected function outlet(string $suffix = ''): Outlets
    {
        return Outlets::create([
            'name' => 'Order Table Outlet '.$suffix,
            'address' => 'Test address',
            'phone' => '0800000000',
        ]);
    }

    protected function userFor(Outlets $outlet, array $permissions = []): User
    {
        $user = User::factory()->create(['outlet_id' => [$outlet->id]]);

        foreach ($permissions as $name) {
            $permission = Permission::firstOrCreate([
                'name' => $name,
                'guard_name' => 'web',
            ]);
            $user->givePermissionTo($permission);
        }

        return $user;
    }

    protected function setting(Outlets $outlet, array $attributes = []): OutletSetting
    {
        return OutletSetting::create(array_merge([
            'outlet_id' => $outlet->id,
            'order_enabled' => true,
            'stock_mode' => 'status_only',
            'forced_close' => false,
            'service_fee_pct' => 5,
            'auto_preparing_delay_seconds' => 30,
            'session_close_time' => '23:59',
        ], $attributes));
    }

    protected function diningTable(Outlets $outlet, array $attributes = []): DiningTable
    {
        $table = new DiningTable(array_merge([
            'outlet_id' => $outlet->id,
            'code' => 'T01',
            'name' => 'Table 1',
            'capacity' => 4,
            'qr_active' => true,
        ], $attributes));
        $table->qr_token = bin2hex(random_bytes(32));
        $table->save();

        return $table;
    }

    protected function paymentMethod(Outlets $outlet, array $attributes = []): PaymentMethod
    {
        return PaymentMethod::create(array_merge([
            'outlet_id' => $outlet->id,
            'payment_id' => null,
            'category_payment_id' => 1,
            'code' => 'pay_at_cashier',
            'label' => 'Pay at cashier',
            'nama_tipe_pembayaran' => 'Cash',
            'qris_expiry_minutes' => null,
            'payment_due_minutes' => 60,
            'enabled' => true,
            'sort_order' => 1,
        ], $attributes));
    }
}
