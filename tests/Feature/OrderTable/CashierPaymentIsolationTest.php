<?php

namespace Tests\Feature\OrderTable;

use App\Models\ShiftSession;
use App\Services\OrderTable\CashierError;
use App\Services\OrderTable\CashierPaymentService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class CashierPaymentIsolationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:',
            'order-table.pay_at_cashier_enabled' => true]);
        DB::purge('sqlite');
        Schema::create('ot_orders', function (Blueprint $t) {
            $t->id();
            $t->integer('outlet_id');
            $t->string('order_no');
            $t->string('payment_mode');
            $t->string('status');
            $t->string('payment_status');
            $t->timestamp('payment_due_at');
            $t->integer('grand_total');
            $t->integer('pos_transaction_id')->nullable();
            $t->string('pos_bridge_status')->default('pending');
            $t->timestamps();
        });
        Schema::create('transactions', function (Blueprint $t) {
            $t->id();
            $t->integer('outlet_id');
            $t->string('reference_id');
            $t->integer('total');
            $t->softDeletes();
            $t->timestamps();
        });
        (require base_path('database/migrations/2026_10_12_000001_create_order_table_cashier_tables.php'))->up();
        DB::table('ot_orders')->insert([
            'id' => 1, 'outlet_id' => 11, 'order_no' => 'OT-TEST', 'payment_mode' => 'pay_at_cashier',
            'status' => 'placed', 'payment_status' => 'unpaid', 'payment_due_at' => now()->addHour(), 'grand_total' => 100,
        ]);
    }

    private function shift(): ShiftSession
    {
        return new ShiftSession(['id' => 1, 'outlet_id' => 11, 'user_id' => 1, 'android_id' => 'device-a']);
    }

    public function test_expired_claim_after_commit_never_authorizes_collection(): void
    {
        DB::table('transactions')->insert(['id' => 10, 'outlet_id' => 11, 'reference_id' => 'OT-TEST', 'total' => 100]);
        DB::table('order_table_payment_claims')->insert([
            'order_id' => 1, 'user_id' => 1, 'shift_session_id' => 1, 'device_id' => 'device-a',
            'claim_token' => 'old', 'expires_at' => now()->subHour(), 'transaction_id' => 10,
        ]);
        $service = app(CashierPaymentService::class);
        $state = $service->claim(1, $this->shift());
        $this->assertFalse($state['collectionAllowed']);
        $this->assertTrue($state['settleRequired']);
        $this->assertSame(10, $state['posTransactionId']);
        $retry = $service->pay(1, $this->shift(), ['claimToken' => 'old', 'paymentId' => 1, 'nominalBayar' => 100]);
        $this->assertSame(10, $retry['posTransactionId']);
        $this->assertSame(1, DB::table('transactions')->count());
    }

    public function test_deleted_commit_evidence_blocks_collection(): void
    {
        DB::table('order_table_payment_claims')->insert([
            'order_id' => 1, 'user_id' => 1, 'shift_session_id' => 1, 'device_id' => 'device-a',
            'claim_token' => 'old', 'expires_at' => now()->subHour(), 'transaction_id' => 10,
        ]);
        $this->expectException(CashierError::class);
        $this->expectExceptionMessage('PAYMENT_RECONCILIATION_REQUIRED');
        app(CashierPaymentService::class)->claim(1, $this->shift());
    }

    public function test_qris_cannot_be_claimed(): void
    {
        DB::table('ot_orders')->update(['payment_mode' => 'qris']);
        $this->expectException(CashierError::class);
        $this->expectExceptionMessage('CASHIER_MODE_REQUIRED');
        app(CashierPaymentService::class)->claim(1, $this->shift());
    }

    public function test_other_outlet_cannot_read_order(): void
    {
        $this->expectException(\Illuminate\Database\Eloquent\ModelNotFoundException::class);
        app(CashierPaymentService::class)->order(1, 22);
    }
}
