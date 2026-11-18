<?php

namespace Tests\Feature\B2b;

use App\Models\B2b\Invoice;
use App\Models\B2b\Order;
use App\Models\B2b\Payment;
use App\Models\B2b\Refund;
use App\Models\Outlets;
use App\Models\User;
use App\Models\VariantProduct;
use App\Services\B2b\OrderService;
use Illuminate\Validation\ValidationException;

class PaymentServiceTest extends B2bTestCase
{
    private Outlets $outlet;

    private User $user;

    private VariantProduct $variant;

    protected function setUp(): void
    {
        parent::setUp();
        $this->outlet = $this->outlet();
        $this->user = $this->userFor($this->outlet);
        $this->variant = $this->variant($this->outlet, 10000, 10);
    }

    private function place(array $overrides = []): Order
    {
        return app(OrderService::class)->place($this->user, array_merge([
            'outlet_id' => $this->outlet->id,
            'b2b_customer_id' => $this->customer()->id,
            'items' => [['variant_id' => $this->variant->id, 'qty' => 3]],
            'delivery_method' => 'pickup',
            'due_date' => today()->addDays(7)->toDateString(),
        ], $overrides));
    }

    public function test_orders_default_to_credit_without_payment(): void
    {
        $order = $this->place();

        $this->assertSame(Order::PAYMENT_CREDIT, $order->fresh()->payment_type);
        $this->assertSame(Invoice::STATUS_UNPAID, $order->invoice->status);
        $this->assertCount(0, $order->invoice->allocations);
    }

    public function test_immediate_payment_settles_invoice_in_same_transaction(): void
    {
        [$edc, $methods] = $this->paymentCategory('EDC', ['DEBIT', 'QRIS']);

        $order = $this->place([
            'payment_type' => Order::PAYMENT_IMMEDIATE,
            'due_date' => today()->addDays(999)->toDateString(), // diabaikan untuk bayar langsung
            'payment' => ['category_payment_id' => $edc->id, 'payment_id' => $methods['QRIS']->id, 'reference' => ' APR123 '],
        ]);

        $order->refresh();
        $this->assertSame(Order::PAYMENT_IMMEDIATE, $order->payment_type);
        $this->assertSame(today()->toDateString(), $order->due_date->toDateString());

        $invoice = $order->invoice()->first();
        $this->assertSame(Invoice::STATUS_PAID, $invoice->status);
        $this->assertSame(30000, $invoice->paid_amount);
        $this->assertNotNull($invoice->paid_at);
        $this->assertSame(0, $invoice->outstandingAmount());
        $this->assertSame(today()->toDateString(), $invoice->due_date->toDateString());

        $schedule = $invoice->schedules()->first();
        $this->assertSame(Invoice::STATUS_PAID, $schedule->status);
        $this->assertSame(30000, $schedule->paid_amount);

        $payment = Payment::query()->where('b2b_customer_id', $order->b2b_customer_id)->sole();
        $this->assertMatchesRegularExpression('#^PAY/B2B/\d{4}/\d{2}/\d{4}$#', $payment->payment_number);
        $this->assertSame($this->outlet->id, $payment->outlet_id);
        $this->assertSame($edc->id, $payment->category_payment_id);
        $this->assertSame($methods['QRIS']->id, $payment->payment_id);
        $this->assertStringEndsWith(' · QRIS', $payment->method_name);
        $this->assertSame(30000, $payment->amount);
        $this->assertSame('APR123', $payment->reference);
        $this->assertSame($this->user->id, $payment->received_by);
        $this->assertSame(Payment::STATUS_POSTED, $payment->status);

        $allocation = $payment->allocations()->sole();
        $this->assertSame($invoice->id, $allocation->b2b_invoice_id);
        $this->assertSame($schedule->id, $allocation->b2b_invoice_schedule_id);
        $this->assertSame(30000, $allocation->amount);
        $this->assertDatabaseHas('b2b_activity_logs', ['action' => 'payment.received', 'subject_id' => $payment->id]);
    }

    public function test_category_without_sub_methods_such_as_cash_is_accepted(): void
    {
        [$cash] = $this->paymentCategory('Cash');

        $order = $this->place(['payment_type' => Order::PAYMENT_IMMEDIATE, 'payment' => ['category_payment_id' => $cash->id]]);

        $payment = $order->invoice->allocations->first()->payment;
        $this->assertNull($payment->payment_id);
        $this->assertSame($cash->name, $payment->method_name);
        $this->assertNull($payment->reference);
    }

    /** @return array<string, array{0: callable}> */
    public static function invalidPaymentProvider(): array
    {
        return [
            'tanpa metode' => [fn (array $edc, array $other) => []],
            'sub metode wajib' => [fn (array $edc, array $other) => ['category_payment_id' => $edc[0]->id]],
            'sub metode kategori lain' => [fn (array $edc, array $other) => ['category_payment_id' => $edc[0]->id, 'payment_id' => $other[1]['BCA']->id]],
            'kategori nonaktif' => [function (array $edc, array $other) {
                $edc[0]->update(['status' => false]);

                return ['category_payment_id' => $edc[0]->id, 'payment_id' => $edc[1]['QRIS']->id];
            }],
        ];
    }

    /** @dataProvider invalidPaymentProvider */
    public function test_invalid_payment_method_rolls_back_whole_order(callable $payment): void
    {
        $edc = $this->paymentCategory('EDC', ['QRIS']);
        $other = $this->paymentCategory('Transfer', ['BCA']);
        $customer = $this->customer();

        try {
            $this->place(['b2b_customer_id' => $customer->id, 'payment_type' => Order::PAYMENT_IMMEDIATE, 'payment' => $payment($edc, $other)]);
            $this->fail('Pesanan seharusnya ditolak.');
        } catch (ValidationException $e) {
            $this->assertNotEmpty(array_filter(array_keys($e->errors()), fn ($key) => str_starts_with($key, 'payment.')));
        }

        $this->assertSame(0, Order::query()->where('b2b_customer_id', $customer->id)->count());
        $this->assertSame(10, (int) $this->variant->fresh()->stok);
        $this->assertSame(0, Payment::query()->where('b2b_customer_id', $customer->id)->count());
    }

    public function test_unknown_payment_type_is_rejected(): void
    {
        $this->expectException(ValidationException::class);
        $this->place(['payment_type' => 'barter']);
    }

    public function test_paid_order_cannot_be_cancelled_without_refund_data(): void
    {
        [$cash] = $this->paymentCategory('Cash');
        $order = $this->place(['payment_type' => Order::PAYMENT_IMMEDIATE, 'payment' => ['category_payment_id' => $cash->id]]);

        try {
            app(OrderService::class)->cancel($this->user, $order, 'Batal');
            $this->fail('Pembatalan seharusnya ditolak.');
        } catch (ValidationException) {
            $this->assertSame(Order::STATUS_CONFIRMED, $order->fresh()->status);
            $this->assertSame(Invoice::STATUS_PAID, $order->invoice()->first()->status);
        }
    }

    public function test_cancelling_paid_order_reverses_payment_and_records_refund(): void
    {
        [$edc, $edcMethods] = $this->paymentCategory('EDC', ['DEBIT']);
        [$transfer, $transferMethods] = $this->paymentCategory('Transfer', ['BCA']);
        $order = $this->place([
            'payment_type' => Order::PAYMENT_IMMEDIATE,
            'payment' => ['category_payment_id' => $edc->id, 'payment_id' => $edcMethods['DEBIT']->id],
        ]);
        $payment = $order->invoice->allocations->first()->payment;

        app(OrderService::class)->cancel($this->user, $order, 'Customer batal acara', [
            'category_payment_id' => $transfer->id,
            'payment_id' => $transferMethods['BCA']->id,
            'reference' => 'TRF-889',
        ]);

        $this->assertSame(Order::STATUS_CANCELLED, $order->fresh()->status);
        $this->assertSame(10, (int) $this->variant->fresh()->stok);

        $invoice = $order->invoice()->first();
        $this->assertSame(Invoice::STATUS_VOID, $invoice->status);
        $this->assertSame(0, $invoice->paid_amount);
        $this->assertNull($invoice->paid_at);
        $this->assertSame(0, $invoice->schedules()->first()->paid_amount);

        $payment->refresh();
        $this->assertSame(Payment::STATUS_REVERSED, $payment->status);
        $this->assertSame($this->user->id, $payment->reversed_by);
        $this->assertSame('Customer batal acara', $payment->reverse_reason);

        $refund = Refund::query()->where('b2b_payment_id', $payment->id)->sole();
        $this->assertSame(30000, $refund->amount);
        $this->assertSame($invoice->id, $refund->b2b_invoice_id);
        $this->assertSame($this->outlet->id, $refund->outlet_id);
        $this->assertSame($transfer->id, $refund->category_payment_id);
        $this->assertStringEndsWith(' · BCA', $refund->method_name);
        $this->assertSame('TRF-889', $refund->reference);
        $this->assertDatabaseHas('b2b_activity_logs', ['action' => 'payment.reversed', 'subject_id' => $payment->id]);
    }

    public function test_refund_is_rejected_when_paid_amount_has_no_payment_record(): void
    {
        [$cash] = $this->paymentCategory('Cash');
        $order = $this->place();
        $order->invoice->update(['paid_amount' => 5000, 'status' => Invoice::STATUS_PARTIAL]);

        try {
            app(OrderService::class)->cancel($this->user, $order, 'Batal', ['category_payment_id' => $cash->id]);
            $this->fail('Pembatalan seharusnya ditolak.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('refund', $e->errors());
            $this->assertSame(Order::STATUS_CONFIRMED, $order->fresh()->status);
            $this->assertSame(7, (int) $this->variant->fresh()->stok);
        }
    }
}
