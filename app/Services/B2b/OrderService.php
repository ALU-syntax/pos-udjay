<?php

namespace App\Services\B2b;

use App\Models\B2b\Customer;
use App\Models\B2b\Invoice;
use App\Models\B2b\Order;
use App\Models\User;
use App\Models\VariantProduct;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Alur pesanan B2B: pesanan dikonfirmasi → stok varian dikurangi (boleh minus) → invoice + jadwal tagihan.
 * Pembayaran belum dibangun; invoice tidak memakai kode unik sampai metode pembayaran diputuskan.
 */
class OrderService
{
    public function __construct(
        private readonly PricingService $pricing,
        private readonly SequenceService $sequences,
        private readonly ActivityLogger $logger,
        private readonly B2bSettings $settings,
    ) {}

    /**
     * @param  array{outlet_id:int, b2b_customer_id:int, items:array, delivery_method:string, due_date:string,
     *               notes?:string|null}  $data
     */
    public function place(User $user, array $data): Order
    {
        return DB::transaction(function () use ($user, $data) {
            $outletId = (int) $data['outlet_id'];
            $customer = Customer::query()->lockForUpdate()->findOrFail($data['b2b_customer_id']);
            if (! $customer->is_active) {
                throw ValidationException::withMessages(['b2b_customer_id' => 'Customer sedang nonaktif.']);
            }

            $orderDate = now();
            $dueDate = $this->dueDate($data['due_date'] ?? null, $orderDate);
            // Varian dikunci supaya penanda stok kurang konsisten dengan pengurangan stok di bawah.
            $variants = $this->pricing->loadVariants($outletId, array_column($data['items'], 'variant_id'), lock: true);
            $quote = $this->pricing->quote($customer, $outletId, $data['items'], $orderDate, $variants);

            // Satu alamat customer dipakai untuk penagihan dan pengiriman.
            if ($data['delivery_method'] === 'internal' && ! $customer->hasAddress()) {
                throw ValidationException::withMessages([
                    'delivery_method' => 'Customer belum punya alamat. Lengkapi alamat customer dulu untuk pengiriman internal.',
                ]);
            }

            $order = new Order([
                'b2b_customer_id' => $customer->id,
                'b2b_contract_id' => $quote['contract']?->id,
                'outlet_id' => $outletId,
                'user_id' => $user->id,
                'order_date' => $orderDate,
                'due_date' => $dueDate,
                'status' => Order::STATUS_CONFIRMED,
                'subtotal' => $quote['subtotal'],
                'discount_total' => $quote['discount_total'],
                'tax_total' => 0,
                'shipping_cost' => 0,
                'grand_total' => $quote['grand_total'],
                'delivery_method' => $data['delivery_method'],
                'shipping_address_snapshot' => $data['delivery_method'] === 'internal' ? $customer->addressSnapshot() : null,
                'confirmed_by' => $user->id,
                'confirmed_at' => $orderDate,
                'notes' => $data['notes'] ?? null,
            ]);
            $order->order_number = $this->sequences->orderNumber($orderDate);
            $order->save();

            foreach ($quote['items'] as $item) {
                $order->items()->create([
                    'product_id' => $item['product_id'],
                    'variant_id' => $item['variant_id'],
                    'product_name' => $item['product_name'],
                    'variant_name' => $item['variant_name'],
                    'qty' => $item['qty'],
                    'base_price' => $item['base_price'],
                    'b2b_contract_rule_id' => $item['b2b_contract_rule_id'],
                    'discount_type' => $item['discount_type'],
                    'discount_value' => $item['discount_value'],
                    'discount_per_unit' => $item['discount_per_unit'],
                    'final_price' => $item['final_price'],
                    'line_total' => $item['line_total'],
                    'stock_insufficient' => $item['stock_insufficient'],
                    'notes' => $item['notes'],
                ]);

                // Sama dengan POS: stok boleh minus, cukup diberi penanda.
                VariantProduct::whereKey($item['variant_id'])->decrement('stok', $item['qty']);
            }

            $invoice = $this->createInvoice($order, $customer->addressSnapshot());

            $this->logger->log('order.placed', $order, [
                'invoice_number' => $invoice->invoice_number,
                'grand_total' => $order->grand_total,
                'due_date' => $dueDate->toDateString(),
                'contract_id' => $order->b2b_contract_id,
                'insufficient_stock' => $quote['has_insufficient_stock'],
            ], $user->id);

            return $order->load(['items', 'invoice.schedules', 'customer', 'outlet']);
        });
    }

    public function complete(User $user, Order $order): Order
    {
        return DB::transaction(function () use ($user, $order) {
            $order = Order::query()->whereKey($order->id)->lockForUpdate()->firstOrFail();
            if ($order->status !== Order::STATUS_CONFIRMED) {
                throw ValidationException::withMessages(['status' => 'Hanya pesanan berstatus dikonfirmasi yang bisa diselesaikan.']);
            }

            $order->forceFill([
                'status' => Order::STATUS_COMPLETED,
                'completed_by' => $user->id,
                'completed_at' => now(),
            ])->save();
            $this->logger->log('order.completed', $order, null, $user->id);

            return $order;
        });
    }

    /** Batalkan pesanan: stok dikembalikan dan invoice dibatalkan (void). Ditolak kalau invoice sudah ada pembayaran. */
    public function cancel(User $user, Order $order, string $reason): Order
    {
        return DB::transaction(function () use ($user, $order, $reason) {
            $order = Order::query()->with('items')->whereKey($order->id)->lockForUpdate()->firstOrFail();
            if ($order->status !== Order::STATUS_CONFIRMED) {
                throw ValidationException::withMessages(['status' => 'Hanya pesanan berstatus dikonfirmasi yang bisa dibatalkan.']);
            }

            $invoice = Invoice::query()->where('b2b_order_id', $order->id)->lockForUpdate()->first();
            if ($invoice && $invoice->paid_amount > 0) {
                throw ValidationException::withMessages(['status' => 'Invoice pesanan ini sudah menerima pembayaran, jadi pesanan tidak bisa dibatalkan.']);
            }

            foreach ($order->items as $item) {
                if ($item->variant_id) {
                    VariantProduct::withTrashed()->whereKey($item->variant_id)->increment('stok', $item->qty);
                }
            }

            if ($invoice) {
                $invoice->forceFill([
                    'status' => Invoice::STATUS_VOID,
                    'voided_by' => $user->id,
                    'voided_at' => now(),
                    'void_reason' => 'Pesanan dibatalkan: '.$reason,
                ])->save();
                $invoice->schedules()->update(['status' => Invoice::STATUS_VOID]);
            }

            $order->forceFill([
                'status' => Order::STATUS_CANCELLED,
                'cancelled_by' => $user->id,
                'cancelled_at' => now(),
                'cancel_reason' => $reason,
            ])->save();
            $this->logger->log('order.cancelled', $order, ['reason' => $reason, 'invoice_voided' => (bool) $invoice], $user->id);

            return $order;
        });
    }

    /** Jatuh tempo dipilih kasir: minimal tanggal pesanan, maksimal B2bSettings::MAX_DUE_DAYS hari ke depan. */
    private function dueDate(?string $value, Carbon $orderDate): Carbon
    {
        $orderDay = $orderDate->copy()->startOfDay();

        try {
            $dueDate = $value ? Carbon::createFromFormat('Y-m-d', $value)->startOfDay() : null;
        } catch (\Throwable) {
            $dueDate = null;
        }

        if (! $dueDate || $dueDate->lt($orderDay) || $dueDate->gt($this->settings->maxDueDate($orderDay))) {
            throw ValidationException::withMessages([
                'due_date' => 'Jatuh tempo harus antara hari ini dan '.B2bSettings::MAX_DUE_DAYS.' hari ke depan.',
            ]);
        }

        return $dueDate;
    }

    private function createInvoice(Order $order, ?array $billingAddress): Invoice
    {
        $issueDate = $order->order_date->copy()->startOfDay();
        $dueDate = $order->due_date->copy();

        $invoice = new Invoice([
            'b2b_order_id' => $order->id,
            'b2b_customer_id' => $order->b2b_customer_id,
            'issue_date' => $issueDate,
            'due_date' => $dueDate,
            'amount' => $order->grand_total,
            'unique_code_amount' => 0,
            'total_to_pay' => $order->grand_total,
            'paid_amount' => 0,
            'status' => Invoice::STATUS_UNPAID,
            'billing_address_snapshot' => $billingAddress,
        ]);
        $invoice->invoice_number = $this->sequences->invoiceNumber($order->order_date);
        $invoice->save();

        $invoice->schedules()->create([
            'sequence' => 1,
            'due_date' => $dueDate,
            'amount' => $order->grand_total,
            'unique_code' => 0,
            'amount_to_pay' => $order->grand_total,
            'paid_amount' => 0,
            'status' => Invoice::STATUS_UNPAID,
        ]);

        return $invoice;
    }
}
