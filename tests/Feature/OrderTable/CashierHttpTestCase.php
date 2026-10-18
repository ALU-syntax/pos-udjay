<?php

namespace Tests\Feature\OrderTable;

use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

abstract class CashierHttpTestCase extends TestCase
{
    protected User $cashier;

    protected string $bearer;

    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:',
            'order-table.pay_at_cashier_enabled' => true, 'cache.default' => 'array']);
        DB::purge('sqlite');
        $this->assertSame(':memory:', DB::connection()->getDatabaseName());
        Schema::create('users', function (Blueprint $t) {
            $t->id();
            $t->string('name');
            $t->string('email')->nullable();
            $t->string('password')->nullable();
            $t->json('outlet_id');
            $t->integer('status')->default(1);
            $t->integer('deleted')->default(0);
            $t->integer('role')->default(3);
            $t->timestamps();
        });
        Schema::create('outlets', function (Blueprint $t) {
            $t->id();
            $t->string('name');
            $t->timestamps();
        });
        Schema::create('categories', function (Blueprint $t) {
            $t->id();
        });
        Schema::create('sales_types', function (Blueprint $t) {
            $t->id();
        });
        foreach ([
            '2019_12_14_000001_create_personal_access_tokens_table.php',
            '2024_12_20_194049_create_petty_cashes_table.php',
            '2026_08_24_000001_create_shift_sessions_table.php',
            '2024_11_30_145920_create_category_payments_table.php',
            '2024_11_30_153818_create_payments_table.php',
            '2024_11_20_234719_create_products_table.php',
            '2024_12_20_230048_create_transactions_table.php',
            '2024_12_25_130056_create_transaction_items_table.php',
            '2026_10_12_000001_create_order_table_cashier_tables.php',
            '2026_10_12_000002_create_order_table_notification_outbox.php',
        ] as $file) {
            (require database_path('migrations/'.$file))->up();
        }
        Schema::create('variant_products', function (Blueprint $t) {
            $t->id();
            $t->foreignId('product_id')->constrained('products');
            $t->string('name');
            $t->bigInteger('harga');
            $t->bigInteger('stok');
            $t->softDeletes();
            $t->timestamps();
        });
        Schema::table('transactions', function (Blueprint $t) {
            $t->string('reference_id')->nullable()->index();
            $t->unsignedBigInteger('shift_session_id')->nullable();
            $t->integer('potongan_point')->nullable();
            $t->string('receipt_number')->nullable();
        });
        Schema::table('transaction_items', function (Blueprint $t) {
            $t->unsignedBigInteger('harga')->nullable();
        });
        // Use actual OT migrations including foreign keys, not ad-hoc payment table mocks.
        foreach (glob(database_path('migrations/2026_10_03_*.php')) as $file) {
            if (str_contains($file, 'geolocation')) {
                continue;
            }
            (require $file)->up();
        }
        (require database_path('migrations/2026_10_18_000001_add_voided_at_to_ot_order_items_table.php'))->up();
        DB::table('outlets')->insert([['id' => 11, 'name' => 'A'], ['id' => 22, 'name' => 'B']]);
        $this->cashier = User::create(['name' => 'Kasir', 'outlet_id' => [11], 'status' => 1, 'deleted' => 0]);
        $this->bearer = $this->cashier->createToken('test-device', ['*'], now()->addDay())->plainTextToken;
        DB::table('petty_cashes')->insert(['id' => 1, 'outlet_id' => '11', 'open' => now()]);
        DB::table('shift_sessions')->insert(['id' => 1, 'outlet_id' => 11, 'user_id' => $this->cashier->id,
            'petty_cash_id' => 1, 'android_id' => 'device-a', 'status' => 'ACTIVE']);
        DB::table('category_payments')->insert(['id' => 1, 'name' => 'Cash']);
        DB::table('payments')->insert(['id' => 1, 'name' => 'Debit', 'category_payment_id' => 1, 'status' => 1]);
        DB::table('products')->insert(['id' => 1, 'name' => 'Kopi', 'outlet_id' => 11]);
        DB::table('variant_products')->insert(['id' => 1, 'product_id' => 1, 'name' => 'Regular', 'harga' => 99999, 'stok' => 20]);
        DB::table('ot_dining_tables')->insert(['id' => 1, 'outlet_id' => 11, 'code' => 'M1', 'name' => 'Meja 1', 'qr_token' => 'test']);
        DB::table('ot_table_sessions')->insert(['id' => 1, 'device_id' => 'guest', 'table_id' => 1,
            'outlet_id' => 11, 'status' => 'open', 'opened_at' => now()]);
        $this->makeOrder();
        $this->deviceHeaders();
    }

    protected function makeOrder(int $id = 1, array $extra = []): void
    {
        DB::table('ot_orders')->insert(array_merge([
            'id' => $id, 'order_no' => 'OT-TEST-'.$id, 'outlet_id' => 11, 'session_id' => 1,
            'device_id' => 'guest', 'table_id' => 1, 'status' => 'placed', 'payment_mode' => 'pay_at_cashier',
            'payment_status' => 'unpaid', 'subtotal' => 22000, 'modifier_total' => 2000,
            'tax_total' => 2000, 'tax_breakdown' => json_encode([['id' => 1, 'name' => 'Tax', 'total' => 2000]]),
            'grand_total' => 22000, 'geofence_flag' => 'unknown', 'payment_due_at' => now()->addHour(),
            'created_at' => now(), 'updated_at' => now(),
        ], $extra));
        $item = DB::table('ot_order_items')->insertGetId(['order_id' => $id, 'product_id' => 1, 'variant_id' => 1,
            'product_name' => 'Kopi', 'variant_name' => 'Regular', 'unit_price' => 10000, 'qty' => 2,
            'modifier_total' => 1000, 'line_total' => 22000, 'exclude_tax' => false, 'status' => 'pending']);
        DB::table('ot_order_item_modifiers')->insert(['order_item_id' => $item, 'modifier_id' => 10,
            'name' => 'Extra', 'harga' => 500, 'qty' => 2]);
        DB::table('ot_order_payments')->insert(['order_id' => $id, 'method' => 'cashier', 'amount' => $extra['grand_total'] ?? 22000,
            'status' => 'pending', 'expires_at' => now()->addHour()]);
    }

    protected function deviceHeaders(?string $bearer = null, string $device = 'device-a', int $shift = 1): void
    {
        app('auth')->forgetGuards();
        $this->withHeaders(['Authorization' => 'Bearer '.($bearer ?? $this->bearer),
            'X-Device-Id' => $device, 'X-Shift-Session-Id' => (string) $shift]);
    }

    protected function claimToken(int $id = 1): string
    {
        return $this->postJson('/api/v1/order-table/orders/'.$id.'/claim')->assertOk()->json('data.claimToken');
    }
}
