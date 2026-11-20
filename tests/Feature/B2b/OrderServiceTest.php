<?php

namespace Tests\Feature\B2b;

use App\Models\B2b\Customer;
use App\Models\B2b\Invoice;
use App\Models\B2b\Order;
use App\Models\Config;
use App\Services\B2b\B2bSettings;
use App\Services\B2b\OrderService;
use Illuminate\Validation\ValidationException;

class OrderServiceTest extends B2bTestCase
{
    private function place(array $overrides = []): Order
    {
        return app(OrderService::class)->place($overrides['user'], array_merge([
            'delivery_method' => 'pickup',
            'due_date' => today()->addDays(7)->toDateString(),
        ], collect($overrides)->except('user')->all()));
    }

    public function test_placing_order_snapshots_prices_deducts_stock_and_creates_invoice(): void
    {
        $outlet = $this->outlet();
        $user = $this->userFor($outlet);
        $variant = $this->variant($outlet, 10000, 3);
        $customer = $this->customer();
        $contract = $this->contract($customer, [['variant_id' => $variant->id, 'price' => 9000]]);

        $order = $this->place([
            'user' => $user,
            'outlet_id' => $outlet->id,
            'b2b_customer_id' => $customer->id,
            'items' => [['variant_id' => $variant->id, 'qty' => 5, 'notes' => 'Bungkus terpisah']],
            'due_date' => today()->addDays(45)->toDateString(),
        ]);

        $this->assertSame(Order::STATUS_CONFIRMED, $order->status);
        $this->assertSame($contract->id, $order->b2b_contract_id);
        $this->assertMatchesRegularExpression('#^SO/B2B/\d{4}/\d{2}/\d{4}$#', $order->order_number);
        $this->assertSame(45000, $order->subtotal);
        $this->assertSame(45000, $order->grand_total);

        $item = $order->items->first();
        $this->assertSame(10000, $item->base_price);
        $this->assertSame(9000, $item->final_price);
        $this->assertSame($contract->items->first()->id, $item->b2b_contract_item_id);
        $this->assertTrue($item->stock_insufficient);
        $this->assertSame('Bungkus terpisah', $item->notes);
        $this->assertSame(-2, (int) $variant->fresh()->stok);

        $invoice = $order->invoice;
        $this->assertMatchesRegularExpression('#^\d{2,}/INV/BGR/'.now()->format('m/Y').'$#', $invoice->invoice_number);
        $this->assertSame(45000, $invoice->total_to_pay);
        $this->assertSame(0, $invoice->unique_code_amount);
        $this->assertSame(today()->addDays(45)->toDateString(), $order->due_date->toDateString());
        $this->assertSame(today()->addDays(45)->toDateString(), $invoice->due_date->toDateString());
        $this->assertSame(today()->addDays(45)->toDateString(), $invoice->schedules->first()->due_date->toDateString());
        $this->assertCount(1, $invoice->schedules);
        $this->assertSame(45000, $invoice->schedules->first()->amount_to_pay);
        $this->assertDatabaseHas('b2b_activity_logs', ['action' => 'order.placed', 'subject_id' => $order->id]);
    }

    public function test_contract_changes_do_not_alter_existing_order_prices(): void
    {
        $outlet = $this->outlet();
        $user = $this->userFor($outlet);
        $variant = $this->variant($outlet, 10000);
        $customer = $this->customer();
        $contract = $this->contract($customer, [['variant_id' => $variant->id, 'price' => 9000]]);

        $order = $this->place(['user' => $user, 'outlet_id' => $outlet->id, 'b2b_customer_id' => $customer->id,
            'items' => [['variant_id' => $variant->id, 'qty' => 1]]]);
        $contractItem = $contract->items->first();
        $contractItem->update(['price' => 5000]);
        $contractItem->delete();
        $variant->update(['harga' => 20000]);

        $item = $order->items()->first();
        $this->assertSame(9000, $item->final_price);
        $this->assertSame(10000, $item->base_price);
        // Item kontrak yang dibuang (soft delete) tetap bisa dirujuk dari pesanan lama.
        $this->assertSame($contractItem->id, $item->contractItem?->id);
    }

    public function test_order_numbers_are_sequential(): void
    {
        $outlet = $this->outlet();
        $user = $this->userFor($outlet);
        $variant = $this->variant($outlet);
        $customer = $this->customer();
        $payload = ['user' => $user, 'outlet_id' => $outlet->id, 'b2b_customer_id' => $customer->id,
            'items' => [['variant_id' => $variant->id, 'qty' => 1]]];

        $first = $this->place($payload);
        $second = $this->place($payload);

        $this->assertSame((int) substr($first->order_number, -4) + 1, (int) substr($second->order_number, -4));
    }

    public function test_internal_delivery_requires_customer_address(): void
    {
        $outlet = $this->outlet();
        $user = $this->userFor($outlet);
        $variant = $this->variant($outlet);
        $customer = $this->customer();

        $this->expectException(ValidationException::class);
        $this->place(['user' => $user, 'outlet_id' => $outlet->id, 'b2b_customer_id' => $customer->id,
            'delivery_method' => 'internal', 'items' => [['variant_id' => $variant->id, 'qty' => 1]]]);
    }

    public function test_internal_delivery_requires_region_up_to_regency(): void
    {
        $outlet = $this->outlet();
        $user = $this->userFor($outlet);
        $variant = $this->variant($outlet);
        $this->seedWilayah();
        $order = fn (Customer $customer) => $this->place(['user' => $user, 'outlet_id' => $outlet->id, 'b2b_customer_id' => $customer->id,
            'delivery_method' => 'internal', 'items' => [['variant_id' => $variant->id, 'qty' => 1]]]);

        // Alamat jalan + provinsi saja belum cukup.
        $withoutRegency = $this->customer(['address' => 'Jl. Kantor', 'province_code' => '32']);
        $this->assertFalse($withoutRegency->hasDeliveryAddress());
        try {
            $order($withoutRegency);
            $this->fail('Pengiriman internal tanpa kota/kabupaten seharusnya ditolak.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('delivery_method', $e->errors());
        }

        $complete = $this->customer(['address' => 'Jl. Kantor', 'province_code' => '32', 'regency_code' => '32.01']);
        $this->assertTrue($complete->hasDeliveryAddress());
        $this->assertNotNull($order($complete)->shipping_address_snapshot);
    }

    public function test_customer_address_is_snapshotted_to_order_and_invoice(): void
    {
        $outlet = $this->outlet();
        $user = $this->userFor($outlet);
        $variant = $this->variant($outlet);
        $this->seedWilayah();
        $customer = $this->customer(['contact_name' => 'Budi', 'address' => 'Jl. Kantor', 'province_code' => '32', 'regency_code' => '32.01']);
        $items = [['variant_id' => $variant->id, 'qty' => 1]];

        $internal = $this->place(['user' => $user, 'outlet_id' => $outlet->id, 'b2b_customer_id' => $customer->id,
            'delivery_method' => 'internal', 'items' => $items]);
        $pickup = $this->place(['user' => $user, 'outlet_id' => $outlet->id, 'b2b_customer_id' => $customer->id, 'items' => $items]);
        $customer->update(['address' => 'Jl. Pindah']);

        $this->assertSame('Jl. Kantor, Kabupaten Bogor, Jawa Barat', $internal->fresh()->shipping_address_snapshot['full_address']);
        $this->assertSame('Budi', $internal->shipping_address_snapshot['recipient_name']);
        $this->assertSame('Kabupaten Bogor', $internal->shipping_address_snapshot['regency']);
        $this->assertSame('Jl. Kantor, Kabupaten Bogor, Jawa Barat', $internal->invoice->fresh()->billing_address_snapshot['full_address']);
        // Ambil sendiri: tidak ada alamat kirim, tapi alamat tagihan tetap dicetak di invoice.
        $this->assertNull($pickup->fresh()->shipping_address_snapshot);
        $this->assertSame('Jl. Kantor, Kabupaten Bogor, Jawa Barat', $pickup->invoice->fresh()->billing_address_snapshot['full_address']);
    }

    public function test_inactive_customer_cannot_order(): void
    {
        $outlet = $this->outlet();
        $variant = $this->variant($outlet);

        $this->expectException(ValidationException::class);
        $this->place(['user' => $this->userFor($outlet), 'outlet_id' => $outlet->id,
            'b2b_customer_id' => $this->customer(['is_active' => false])->id,
            'items' => [['variant_id' => $variant->id, 'qty' => 1]]]);
    }

    public function test_cancel_restores_stock_and_voids_invoice(): void
    {
        $outlet = $this->outlet();
        $user = $this->userFor($outlet);
        $variant = $this->variant($outlet, 10000, 10);
        $order = $this->place(['user' => $user, 'outlet_id' => $outlet->id, 'b2b_customer_id' => $this->customer()->id,
            'items' => [['variant_id' => $variant->id, 'qty' => 4]]]);

        app(OrderService::class)->cancel($user, $order, 'Salah input');

        $this->assertSame(10, (int) $variant->fresh()->stok);
        $this->assertSame(Order::STATUS_CANCELLED, $order->fresh()->status);
        $invoice = $order->invoice()->first();
        $this->assertSame(Invoice::STATUS_VOID, $invoice->status);
        $this->assertSame(Invoice::STATUS_VOID, $invoice->schedules()->first()->status);
        $this->assertSame(0, $invoice->outstandingAmount());
    }

    public function test_cancel_is_rejected_when_invoice_has_payment(): void
    {
        $outlet = $this->outlet();
        $user = $this->userFor($outlet);
        $variant = $this->variant($outlet, 10000, 10);
        $order = $this->place(['user' => $user, 'outlet_id' => $outlet->id, 'b2b_customer_id' => $this->customer()->id,
            'items' => [['variant_id' => $variant->id, 'qty' => 1]]]);
        $order->invoice->update(['paid_amount' => 5000, 'status' => Invoice::STATUS_PARTIAL]);

        try {
            app(OrderService::class)->cancel($user, $order, 'Batal');
            $this->fail('Pembatalan seharusnya ditolak.');
        } catch (ValidationException) {
            $this->assertSame(9, (int) $variant->fresh()->stok);
            $this->assertSame(Order::STATUS_CONFIRMED, $order->fresh()->status);
        }
    }

    public function test_completed_or_cancelled_order_cannot_be_cancelled_again(): void
    {
        $outlet = $this->outlet();
        $user = $this->userFor($outlet);
        $variant = $this->variant($outlet, 10000, 10);
        $order = $this->place(['user' => $user, 'outlet_id' => $outlet->id, 'b2b_customer_id' => $this->customer()->id,
            'items' => [['variant_id' => $variant->id, 'qty' => 2]]]);
        app(OrderService::class)->complete($user, $order);

        $this->expectException(ValidationException::class);
        app(OrderService::class)->cancel($user, $order, 'Batal');
    }

    public function test_due_date_must_be_between_today_and_max_days(): void
    {
        $outlet = $this->outlet();
        $user = $this->userFor($outlet);
        $variant = $this->variant($outlet);
        $customer = $this->customer();

        foreach ([null, 'bukan-tanggal', today()->subDay()->toDateString(), today()->addDays(B2bSettings::MAX_DUE_DAYS + 1)->toDateString()] as $dueDate) {
            try {
                $this->place(['user' => $user, 'outlet_id' => $outlet->id, 'b2b_customer_id' => $customer->id,
                    'items' => [['variant_id' => $variant->id, 'qty' => 1]], 'due_date' => $dueDate]);
                $this->fail('Jatuh tempo '.var_export($dueDate, true).' seharusnya ditolak.');
            } catch (ValidationException $exception) {
                $this->assertArrayHasKey('due_date', $exception->errors());
            }
        }

        $order = $this->place(['user' => $user, 'outlet_id' => $outlet->id, 'b2b_customer_id' => $customer->id,
            'items' => [['variant_id' => $variant->id, 'qty' => 1]], 'due_date' => today()->toDateString()]);
        $this->assertSame(today()->toDateString(), $order->invoice->due_date->toDateString());
    }

    public function test_default_payment_term_uses_config_and_falls_back_to_seven_days(): void
    {
        $settings = app(B2bSettings::class);
        Config::query()->where('name', B2bSettings::DEFAULT_PAYMENT_TERM_KEY)->delete();
        $this->assertSame(7, $settings->defaultPaymentTermDays());

        Config::setValue(B2bSettings::DEFAULT_PAYMENT_TERM_KEY, 30, null, 'integer');
        $this->assertSame(30, $settings->defaultPaymentTermDays());
        $this->assertSame(today()->addDays(30)->toDateString(), $settings->suggestedDueDate()->toDateString());

        Config::setValue(B2bSettings::DEFAULT_PAYMENT_TERM_KEY, 'abc', null, 'integer');
        $this->assertSame(7, $settings->defaultPaymentTermDays());
    }
}
