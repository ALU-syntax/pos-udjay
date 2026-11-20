<?php

namespace Tests\Feature\B2b;

use App\Models\B2b\ActivityLog;
use App\Models\B2b\Invoice;
use App\Models\B2b\Order;
use App\Models\Outlets;
use App\Models\User;
use App\Models\VariantProduct;
use App\Services\B2b\OrderService;
use App\Services\B2b\PaymentService;

class OverdueServiceTest extends B2bTestCase
{
    private Outlets $outlet;

    private User $user;

    private VariantProduct $variant;

    protected function setUp(): void
    {
        parent::setUp();
        $this->outlet = $this->outlet();
        $this->user = $this->userFor($this->outlet);
        $this->variant = $this->variant($this->outlet, 10000, 100);
    }

    /** Pesanan tempo 30.000 dengan jatuh tempo $days hari dari hari ini. */
    private function invoiceDueIn(int $days, array $overrides = []): Invoice
    {
        return app(OrderService::class)->place($this->user, array_merge([
            'outlet_id' => $this->outlet->id,
            'b2b_customer_id' => $this->customer()->id,
            'items' => [['variant_id' => $this->variant->id, 'qty' => 3]],
            'delivery_method' => 'pickup',
            'due_date' => today()->addDays($days)->toDateString(),
        ], $overrides))->invoice;
    }

    private function overdueLogs(Invoice $invoice): int
    {
        return ActivityLog::query()->where('action', 'invoice.overdue')->where('subject_id', $invoice->id)->count();
    }

    public function test_command_marks_only_open_credit_invoices_past_due(): void
    {
        [$cash] = $this->paymentCategory('Cash');
        $late = $this->invoiceDueIn(0);
        $dueToday = $this->invoiceDueIn(1);
        $paid = $this->invoiceDueIn(0);
        app(PaymentService::class)->record($this->user, $paid, ['category_payment_id' => $cash->id, 'amount' => 30000]);
        $cancelled = $this->invoiceDueIn(0);
        app(OrderService::class)->cancel($this->user, $cancelled->order, 'Batal');
        $immediate = $this->invoiceDueIn(0, ['payment_type' => Order::PAYMENT_IMMEDIATE, 'payment' => ['category_payment_id' => $cash->id]]);

        $this->travel(1)->days();
        $this->artisan('b2b:mark-overdue')->expectsOutputToContain('Invoice ditandai overdue: 1')->assertSuccessful();

        $late->refresh();
        $this->assertSame(Invoice::STATUS_OVERDUE, $late->status);
        $this->assertNotNull($late->overdue_at);
        $this->assertSame(Invoice::STATUS_OVERDUE, $late->schedules()->first()->status);
        $this->assertNotNull($late->schedules()->first()->overdue_at);
        $this->assertSame(1, $this->overdueLogs($late));

        $this->assertSame(Invoice::STATUS_UNPAID, $dueToday->fresh()->status);
        $this->assertNull($dueToday->fresh()->overdue_at);
        $this->assertSame(Invoice::STATUS_PAID, $paid->fresh()->status);
        $this->assertSame(Invoice::STATUS_VOID, $cancelled->fresh()->status);
        $this->assertSame(Invoice::STATUS_VOID, $cancelled->schedules()->first()->status);
        $this->assertSame(Invoice::STATUS_PAID, $immediate->fresh()->status);
    }

    public function test_command_is_idempotent_and_keeps_first_overdue_time(): void
    {
        $invoice = $this->invoiceDueIn(0);

        $this->travel(1)->days();
        $this->artisan('b2b:mark-overdue')->assertSuccessful();
        $first = $invoice->fresh()->overdue_at;

        $this->travel(1)->days();
        $this->artisan('b2b:mark-overdue')->expectsOutputToContain('Invoice ditandai overdue: 0')->assertSuccessful();

        $this->assertTrue($first->eq($invoice->fresh()->overdue_at));
        $this->assertTrue($first->eq($invoice->schedules()->first()->overdue_at));
        $this->assertSame(1, $this->overdueLogs($invoice));
    }

    public function test_partial_payment_keeps_overdue_and_full_payment_settles_it(): void
    {
        [$cash] = $this->paymentCategory('Cash');
        $service = app(PaymentService::class);
        $invoice = $this->invoiceDueIn(0);
        $this->travel(2)->days();
        $this->artisan('b2b:mark-overdue')->assertSuccessful();
        $overdueAt = $invoice->fresh()->overdue_at;

        $service->record($this->user, $invoice->fresh(), ['category_payment_id' => $cash->id, 'amount' => 10000]);
        $invoice->refresh();
        $this->assertSame(Invoice::STATUS_OVERDUE, $invoice->status);
        $this->assertSame(10000, $invoice->paid_amount);
        $this->assertSame(Invoice::STATUS_OVERDUE, $invoice->schedules()->first()->status);

        $service->record($this->user, $invoice, ['category_payment_id' => $cash->id, 'amount' => 20000]);
        $invoice->refresh();
        $this->assertSame(Invoice::STATUS_PAID, $invoice->status);
        $this->assertSame(Invoice::STATUS_PAID, $invoice->schedules()->first()->status);
        // Riwayat telat tetap tersimpan walau sudah lunas.
        $this->assertTrue($overdueAt->eq($invoice->overdue_at));
    }

    public function test_payment_after_due_date_is_overdue_even_before_command_runs(): void
    {
        [$cash] = $this->paymentCategory('Cash');
        $invoice = $this->invoiceDueIn(0);
        $this->travel(1)->days();

        app(PaymentService::class)->record($this->user, $invoice, ['category_payment_id' => $cash->id, 'amount' => 5000]);

        $invoice->refresh();
        $this->assertSame(Invoice::STATUS_OVERDUE, $invoice->status);
        $this->assertNotNull($invoice->overdue_at);
    }

    public function test_reversing_payment_recalculates_status_from_due_date(): void
    {
        [$cash] = $this->paymentCategory('Cash');
        $service = app(PaymentService::class);
        $beforeDue = $this->invoiceDueIn(5);
        $afterDue = $this->invoiceDueIn(0);
        $paymentBefore = $service->record($this->user, $beforeDue, ['category_payment_id' => $cash->id, 'amount' => 30000]);
        $paymentAfter = $service->record($this->user, $afterDue, ['category_payment_id' => $cash->id, 'amount' => 30000]);

        $this->travel(1)->days();
        $service->reverse($this->user, $paymentBefore, 'Salah catat');
        $service->reverse($this->user, $paymentAfter, 'Salah catat');

        $this->assertSame(Invoice::STATUS_UNPAID, $beforeDue->fresh()->status);
        $this->assertNull($beforeDue->fresh()->overdue_at);
        $this->assertSame(Invoice::STATUS_OVERDUE, $afterDue->fresh()->status);
        $this->assertNotNull($afterDue->fresh()->overdue_at);
        $this->assertSame(Invoice::STATUS_OVERDUE, $afterDue->schedules()->first()->status);
    }
}
