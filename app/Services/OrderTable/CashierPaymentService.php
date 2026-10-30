<?php

namespace App\Services\OrderTable;

use App\Models\OrderTable\Order;
use App\Models\OrderTable\OrderItem;
use App\Models\OrderTable\Voucher;
use App\Models\Payment;
use App\Models\PettyCash;
use App\Models\Product;
use App\Models\ShiftSession;
use App\Models\Transaction;
use App\Models\TransactionItem;
use App\Models\VariantProduct;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CashierPaymentService
{
    public function order(int $id, int $outletId, bool $lock = false): Order
    {
        return Order::whereKey($id)->where('outlet_id', $outletId)
            ->when($lock, fn ($q) => $q->lockForUpdate())->firstOrFail();
    }

    public function transaction(Order $order): ?Transaction
    {
        $claim = DB::table('order_table_payment_claims')->where('order_id', $order->id)->first();
        $transactions = Transaction::withTrashed()->where('outlet_id', $order->outlet_id)
            ->where('reference_id', $order->order_no)->get();
        if ($transactions->count() > 1) {
            throw new CashierError('PAYMENT_RECONCILIATION_REQUIRED');
        }
        $transaction = $transactions->first();
        if (($claim?->transaction_id && (int) $claim->transaction_id !== (int) $transaction?->id)
            || ($order->pos_transaction_id && (int) $order->pos_transaction_id !== (int) $transaction?->id)
            || ($transaction && ($transaction->trashed() || (int) $transaction->total !== $order->grand_total))) {
            throw new CashierError('PAYMENT_RECONCILIATION_REQUIRED');
        }

        return $transaction;
    }

    public function state(Order $order, ShiftSession $shift): array
    {
        $transaction = $this->transaction($order);
        $claim = DB::table('order_table_payment_claims')->where('order_id', $order->id)->first();
        $active = $claim && Carbon::parse($claim->expires_at)->isFuture();
        $owned = $active && (int) $claim->shift_session_id === (int) $shift->id
            && (int) $claim->user_id === (int) $shift->user_id && $claim->device_id === $shift->android_id;

        return [
            'orderId' => $order->id, 'posTransactionId' => $transaction?->id,
            'paymentStatus' => $order->payment_status,
            'state' => $transaction ? ($order->payment_status === 'paid' ? 'settled' : 'pos_committed') : ($active ? 'claimed' : 'unclaimed'),
            'collectionAllowed' => (bool) (config('order-table.pay_at_cashier_enabled') && ! $transaction && $owned && $order->payment_mode === 'pay_at_cashier'
                && $order->status === 'placed' && $order->payment_status === 'unpaid'
                && $order->payment_due_at?->isFuture()),
            'claimToken' => $owned && ! $transaction ? $claim->claim_token : null,
            'claimExpiresAt' => $active ? Carbon::parse($claim->expires_at)->toIso8601String() : null,
            'claimOwned' => (bool) $owned,
            'settleRequired' => $transaction && $order->payment_mode === 'pay_at_cashier' && $order->payment_status !== 'paid',
            'receiptAvailable' => (bool) $transaction,
            'coAvailable' => $order->payment_status === 'paid',
            'posBridgeStatus' => $order->pos_bridge_status,
        ];
    }

    private function payable(Order $order): void
    {
        if (! config('order-table.pay_at_cashier_enabled')) {
            throw new CashierError('CASHIER_PAYMENT_DISABLED', 503);
        }
        if ($order->payment_mode !== 'pay_at_cashier') {
            throw new CashierError('CASHIER_MODE_REQUIRED');
        }
        if ($order->status !== 'placed' || $order->payment_status !== 'unpaid'
            || ! $order->payment_due_at?->isFuture()) {
            throw new CashierError('ORDER_NOT_PAYABLE');
        }
        $payments = $order->payments()->lockForUpdate()->get();
        $pending = $payments->where('method', 'cashier')->where('status', 'pending');
        if ($pending->count() !== 1 || $payments->contains('status', 'paid')
            || (int) $pending->first()->amount !== $order->grand_total
            || ($pending->first()->expires_at && ! $pending->first()->expires_at->isFuture())) {
            throw new CashierError('PAYMENT_NOT_PENDING');
        }
    }

    public function claim(int $id, ShiftSession $shift, bool $release = false, ?string $token = null): array
    {
        return DB::transaction(function () use ($id, $shift, $release, $token) {
            $order = $this->order($id, $shift->outlet_id, true);
            // Commit evidence always takes precedence over claim expiry or order status.
            if ($this->transaction($order)) {
                return $this->state($order, $shift);
            }
            $claim = DB::table('order_table_payment_claims')->where('order_id', $id)->first();
            $owned = $claim && (int) $claim->shift_session_id === (int) $shift->id
                && (int) $claim->user_id === (int) $shift->user_id && $claim->device_id === $shift->android_id;
            if ($release) {
                if (! $owned || ! hash_equals($claim->claim_token, (string) $token)) {
                    throw new CashierError('CLAIM_NOT_OWNED');
                }
                DB::table('order_table_payment_claims')->where('order_id', $id)->update(['expires_at' => now(), 'updated_at' => now()]);

                return $this->state($order, $shift);
            }
            $this->payable($order);
            $this->snapshot($order);
            if ($claim && Carbon::parse($claim->expires_at)->isFuture()) {
                if (! $owned) {
                    throw new CashierError('ORDER_CLAIMED');
                }

                return $this->state($order, $shift);
            }
            DB::table('order_table_payment_claims')->updateOrInsert(['order_id' => $id], [
                'user_id' => $shift->user_id, 'shift_session_id' => $shift->id, 'device_id' => $shift->android_id,
                'claim_token' => (string) Str::uuid(), 'expires_at' => now()->addSeconds(config('order-table.claim_seconds', 120)),
                'snapshot_hash' => $this->snapshotHash($order),
                'created_at' => now(), 'updated_at' => now(),
            ]);

            return $this->state($order, $shift);
        }, 3);
    }

    private function snapshot(Order $order): array
    {
        $items = $order->items()->orderBy('id')->lockForUpdate()->get();
        if ($items->isEmpty() || $items->sum('qty') > 1000 || $order->grand_total < 0
            || min($order->subtotal, $order->discount_item_total, $order->voucher_discount, $order->modifier_total, $order->tax_total) < 0
            || $order->discount_item_total + $order->voucher_discount > $order->subtotal) {
            throw new CashierError('SNAPSHOT_INVALID', 422);
        }
        $result = [];
        foreach ($items as $item) {
            if ($item->qty < 1 || $item->unit_price < 0 || $item->status === 'cancelled'
                || ! Product::whereKey($item->product_id)->where('outlet_id', $order->outlet_id)->where('status', true)->exists()
                || ! $item->variant_id || ! VariantProduct::whereKey($item->variant_id)->where('product_id', $item->product_id)->exists()) {
                throw new CashierError('SNAPSHOT_MAPPING_INVALID', 422);
            }
            $modifiers = [];
            foreach ($item->modifiers()->lockForUpdate()->get() as $modifier) {
                if ($modifier->qty < 1 || $modifier->qty > 100 || $modifier->harga < 0) {
                    throw new CashierError('SNAPSHOT_INVALID', 422);
                }
                for ($i = 0; $i < $modifier->qty; $i++) {
                    $modifiers[] = ['id' => $modifier->modifier_id, 'nama' => $modifier->name, 'harga' => $modifier->harga];
                }
            }
            if ($item->modifier_total !== array_sum(array_column($modifiers, 'harga')) || $item->line_total < 0) {
                throw new CashierError('SNAPSHOT_INVALID', 422);
            }
            $result[] = [$item, $modifiers];
        }
        // Design 6.4: subtotal already includes modifiers. Tax inclusion and per-line
        // discount allocation are not versioned in the OT schema; never reprice them here.
        if ($order->subtotal !== $items->sum(fn ($item) => ($item->unit_price + $item->modifier_total) * $item->qty)
            || $order->modifier_total !== $items->sum(fn ($item) => $item->modifier_total * $item->qty)) {
            throw new CashierError('SNAPSHOT_INVALID', 422);
        }
        foreach ($order->tax_breakdown ?? [] as $tax) {
            if (! is_array($tax) || ! isset($tax['id'], $tax['total']) || ! is_numeric($tax['total']) || $tax['total'] < 0) {
                throw new CashierError('SNAPSHOT_INVALID', 422);
            }
        }

        return $result;
    }

    /**
     * Voucher OT mengikuti konvensi diskon POS: rupiah dicatat per transaksi di diskon_all_item,
     * persen dicatat per item di transaction_items.discount_id. Potongan persen bisa dibatasi
     * max_discount, jadi result per unit dibagi proporsional (harga + modifier) dari voucher_discount
     * dengan sisa pembulatan terbesar, sehingga Σ result selalu sama dengan voucher_discount.
     *
     * @param  array<int, array{0: OrderItem, 1: array}>  $items  hasil snapshot()
     * @return array{0: array, 1: array<int, array<int, array>>} [diskon_all_item, discount_id per item per unit]
     */
    private function voucherDiscounts(Order $order, array $items): array
    {
        $amount = $order->voucher_discount;
        if (! $order->voucher_id || $amount <= 0) {
            return [[], []];
        }

        $voucher = Voucher::find($order->voucher_id);
        $id = 'ot-voucher:'.$order->voucher_id;
        $name = 'Voucher '.($voucher?->code ?? 'Order Table');
        $transactionLevel = [[['id' => $id, 'nama' => $name, 'satuan' => 'rupiah', 'value' => $amount]], []];
        if ($voucher?->type !== 'percent') {
            return $transactionLevel;
        }

        $units = [];
        foreach ($items as $index => [$item]) {
            if (! $this->voucherCovers($voucher, $item)) {
                continue;
            }
            for ($i = 0; $i < $item->qty; $i++) {
                $units[] = [$index, $i, $item->unit_price + $item->modifier_total];
            }
        }
        $weight = array_sum(array_column($units, 2));
        // Cakupan bisa bergeser setelah order dibuat (mis. produk pindah kategori); nominal tetap utuh di level transaksi.
        if ($weight < $amount) {
            return $transactionLevel;
        }

        $results = [];
        $remainders = [];
        foreach ($units as $key => [, , $unitWeight]) {
            $results[$key] = intdiv($amount * $unitWeight, $weight);
            $remainders[$key] = ($amount * $unitWeight) % $weight;
        }
        arsort($remainders);
        foreach (array_slice(array_keys($remainders), 0, $amount - array_sum($results)) as $key) {
            $results[$key]++;
        }

        $perUnit = [];
        foreach ($units as $key => [$index, $i]) {
            if ($results[$key] > 0) {
                $perUnit[$index][$i] = [['id' => $id, 'nama' => $name, 'result' => $results[$key],
                    'satuan' => 'percent', 'value' => $voucher->value]];
            }
        }

        return [[], $perUnit];
    }

    /** Sama dengan computeVoucherDiscount di pricing Node: kategori diambil dari produk. */
    private function voucherCovers(Voucher $voucher, OrderItem $item): bool
    {
        $scopeIds = array_map('intval', (array) $voucher->scope_ids);

        return match ($voucher->scope) {
            'product' => in_array((int) $item->product_id, $scopeIds, true),
            'category' => in_array((int) Product::whereKey($item->product_id)->value('category_id'), $scopeIds, true),
            default => true,
        };
    }

    private function snapshotHash(Order $order): string
    {
        return hash('sha256', json_encode([
            $order->only(['subtotal', 'discount_item_total', 'voucher_discount', 'modifier_total',
                'tax_total', 'tax_breakdown', 'rounding', 'grand_total']),
            $order->items()->with(['modifiers' => fn ($q) => $q->orderBy('id')])->orderBy('id')->get()->toArray(),
        ], JSON_THROW_ON_ERROR));
    }

    public function heartbeat(int $id, ShiftSession $shift, string $token): array
    {
        return DB::transaction(function () use ($id, $shift, $token) {
            $order = $this->order($id, $shift->outlet_id, true);
            $state = $this->state($order, $shift);
            if ($state['posTransactionId']) {
                return $state;
            }
            if (! $state['claimOwned'] || ! hash_equals((string) $state['claimToken'], $token)) {
                throw new CashierError('CLAIM_EXPIRED_OR_NOT_OWNED');
            }
            $this->payable($order);
            $claim = DB::table('order_table_payment_claims')->where('order_id', $id)->first();
            if (! $claim->snapshot_hash || ! hash_equals($claim->snapshot_hash, $this->snapshotHash($order))) {
                throw new CashierError('SNAPSHOT_CHANGED');
            }
            DB::table('order_table_payment_claims')->where('order_id', $id)->update([
                'expires_at' => now()->addSeconds(config('order-table.claim_seconds', 120))->min($order->payment_due_at),
                'updated_at' => now(),
            ]);

            return $this->state($order, $shift);
        }, 3);
    }

    /** Claim aktif milik kasir/perangkat/shift ini dengan token yang sama. */
    private function assertOwnedClaim(int $id, ShiftSession $context, string $token): object
    {
        $claim = DB::table('order_table_payment_claims')->where('order_id', $id)->first();
        if (! $claim || (int) $claim->shift_session_id !== (int) $context->id
            || (int) $claim->user_id !== (int) $context->user_id || $claim->device_id !== $context->android_id
            || ! hash_equals($claim->claim_token, $token) || ! Carbon::parse($claim->expires_at)->isFuture()) {
            throw new CashierError('CLAIM_EXPIRED_OR_NOT_OWNED');
        }

        return $claim;
    }

    /**
     * Pratinjau edit item (kurangi/hapus/tambah) sebelum bayar. Node menghitung ulang
     * SEMUA baris dengan harga terkini; tidak ada yang disimpan.
     */
    public function quoteEdit(int $id, ShiftSession $context, array $data): array
    {
        $order = $this->order($id, $context->outlet_id);
        if ($this->transaction($order)) {
            throw new CashierError('ORDER_NOT_EDITABLE');
        }
        $this->payable($order);
        $this->assertOwnedClaim($id, $context, $data['claimToken']);

        return app(OrderTableNodeClient::class)->quoteEdit($id, $data['items'], (bool) ($data['dropVoucher'] ?? false));
    }

    /**
     * Simpan edit item ke Node SEBELUM transaksi pembayaran. Node mengunci baris order yang sama,
     * jadi pemanggilan ini tidak boleh berada di dalam DB::transaction milik pay().
     * Setelah tersimpan, snapshot claim diperbarui agar pembayaran tidak gagal SNAPSHOT_CHANGED.
     */
    private function applyEdit(int $id, ShiftSession $context, array $data): void
    {
        $order = $this->order($id, $context->outlet_id);
        if ($this->transaction($order)) {
            return; // sudah ada transaksi POS: pay() akan mengembalikan state tanpa menagih ulang
        }
        $this->payable($order);
        $this->assertOwnedClaim($id, $context, $data['claimToken']);
        $dropVoucher = (bool) ($data['dropVoucher'] ?? false);
        $before = $this->itemSummary($order);
        // Payload sama → key sama (retry aman); edit berbeda dengan claim yang sama → key baru.
        $key = 'cashier-edit-'.$id.'-'.substr(hash('sha256', $data['claimToken'].json_encode([$data['items'], $dropVoucher])), 0, 40);
        $result = app(OrderTableNodeClient::class)->updateItems($id, $data['items'], $dropVoucher, $key);

        DB::transaction(function () use ($id, $context, $data, $before, $result, $dropVoucher) {
            $order = $this->order($id, $context->outlet_id, true);
            $this->assertOwnedClaim($id, $context, $data['claimToken']);
            DB::table('order_table_payment_claims')->where('order_id', $id)->update([
                'snapshot_hash' => $this->snapshotHash($order), 'updated_at' => now(),
            ]);
            if ($result['changed']) {
                app(AuditLogger::class)->log('order.items-edited-by-cashier', $order, $before, $this->itemSummary($order), [
                    'shift_session_id' => $context->id, 'device_id' => $context->android_id, 'drop_voucher' => $dropVoucher,
                ]);
            }
        }, 3);
    }

    private function itemSummary(Order $order): array
    {
        $order->unsetRelation('items');

        return [
            'grand_total' => $order->grand_total, 'voucher_id' => $order->voucher_id,
            'items' => $order->items()->with('modifiers')->orderBy('id')->get()->map(fn ($item) => [
                'id' => $item->id, 'product_id' => $item->product_id, 'variant_id' => $item->variant_id,
                'name' => trim($item->product_name.' '.$item->variant_name), 'qty' => $item->qty,
                'unit_price' => $item->unit_price, 'line_total' => $item->line_total, 'notes' => $item->notes,
                'modifiers' => $item->modifiers->map(fn ($m) => ['id' => $m->modifier_id, 'qty' => $m->qty])->values()->all(),
            ])->values()->all(),
        ];
    }

    /**
     * Batalkan order bayar-di-kasir yang belum dibayar. Claim dipasang atas nama kasir ini dulu
     * supaya kasir lain tidak bisa menagih selama Node memproses. Node mengunci baris order yang
     * sama, jadi pemanggilannya tidak boleh berada di dalam DB::transaction.
     */
    public function cancel(int $id, ShiftSession $context, string $reason): void
    {
        $token = DB::transaction(function () use ($id, $context) {
            $order = $this->order($id, $context->outlet_id, true);
            if ($this->transaction($order)) {
                throw new CashierError('POS_TRANSACTION_EXISTS');
            }
            if ($order->payment_mode !== 'pay_at_cashier') {
                throw new CashierError('CASHIER_MODE_REQUIRED');
            }
            if ($order->status !== 'placed' || $order->payment_status !== 'unpaid') {
                throw new CashierError('ORDER_NOT_CANCELLABLE');
            }
            $claim = DB::table('order_table_payment_claims')->where('order_id', $id)->first();
            $active = $claim && Carbon::parse($claim->expires_at)->isFuture();
            $owned = $active && (int) $claim->shift_session_id === (int) $context->id
                && (int) $claim->user_id === (int) $context->user_id && $claim->device_id === $context->android_id;
            if ($active && ! $owned) {
                throw new CashierError('ORDER_CLAIMED');
            }
            $token = $owned ? $claim->claim_token : (string) Str::uuid();
            DB::table('order_table_payment_claims')->updateOrInsert(['order_id' => $id], [
                'user_id' => $context->user_id, 'shift_session_id' => $context->id, 'device_id' => $context->android_id,
                'claim_token' => $token, 'expires_at' => now()->addSeconds(config('order-table.claim_seconds', 120)),
                'snapshot_hash' => $owned ? $claim->snapshot_hash : $this->snapshotHash($order),
                'created_at' => $claim->created_at ?? now(), 'updated_at' => now(),
            ]);

            return $token;
        }, 3);

        $releaseClaim = fn () => DB::table('order_table_payment_claims')->where('order_id', $id)
            ->where('claim_token', $token)->update(['expires_at' => now(), 'updated_at' => now()]);
        try {
            app(OrderTableNodeClient::class)->cancel($id, $reason);
        } catch (\Throwable $e) {
            $releaseClaim();
            throw $e instanceof CashierError ? $e : new CashierError('ORDER_TABLE_UNAVAILABLE', 503);
        }

        DB::transaction(function () use ($id, $context, $reason, $releaseClaim) {
            $releaseClaim();
            app(AuditLogger::class)->log('order.cancelled-by-cashier', $this->order($id, $context->outlet_id),
                ['status' => 'placed', 'payment_status' => 'unpaid'], ['status' => 'cancelled'], [
                    'reason' => $reason, 'shift_session_id' => $context->id, 'device_id' => $context->android_id,
                ]);
        }, 3);
    }

    public function pay(int $id, ShiftSession $context, array $data): array
    {
        if (! empty($data['items'])) {
            $this->applyEdit($id, $context, $data);
        }

        return DB::transaction(function () use ($id, $context, $data) {
            // Same lock used by claims, Node bridge, and (required) Node expiry/cancel workers.
            $order = $this->order($id, $context->outlet_id, true);
            if ($this->transaction($order)) {
                return $this->state($order, $context);
            }
            $this->payable($order);
            $claim = DB::table('order_table_payment_claims')->where('order_id', $id)->first();
            if (! $claim || (int) $claim->shift_session_id !== (int) $context->id
                || (int) $claim->user_id !== (int) $context->user_id || $claim->device_id !== $context->android_id
                || ! hash_equals($claim->claim_token, $data['claimToken']) || ! Carbon::parse($claim->expires_at)->isFuture()) {
                throw new CashierError('CLAIM_EXPIRED_OR_NOT_OWNED');
            }
            $petty = PettyCash::whereKey($context->petty_cash_id)->whereNull('close')->lockForUpdate()->first();
            $shift = ShiftSession::whereKey($context->id)->where('status', 'ACTIVE')->whereNull('closed_at')->lockForUpdate()->first();
            if (! $petty || ! $shift) {
                throw new CashierError('ACTIVE_SHIFT_REQUIRED', 403);
            }
            $payment = ! empty($data['paymentId']) ? Payment::find($data['paymentId']) : null;
            $cashCategory = DB::table('category_payments')->where('id', config('order-table.cash_category_id', 1))->first();
            if ((! empty($data['paymentId']) && (! $payment || ! $payment->status))
                || (! $payment && ! $cashCategory) || $data['nominalBayar'] < $order->grand_total) {
                throw new CashierError('PAYMENT_INPUT_INVALID', 422);
            }
            $items = $this->snapshot($order);
            if (! $claim->snapshot_hash || ! hash_equals($claim->snapshot_hash, $this->snapshotHash($order))) {
                throw new CashierError('SNAPSHOT_CHANGED');
            }
            [$diskonAllItem, $voucherPerUnit] = $this->voucherDiscounts($order, $items);
            $transaction = Transaction::create([
                'outlet_id' => $order->outlet_id, 'user_id' => $shift->user_id,
                'patty_cash_id' => $petty->id, 'shift_session_id' => $shift->id,
                'reference_id' => $order->order_no, 'total' => $order->grand_total,
                'nominal_bayar' => $data['nominalBayar'], 'change' => $data['nominalBayar'] - $order->grand_total,
                'category_payment_id' => $payment?->category_payment_id ?? $cashCategory->id, 'tipe_pembayaran' => $payment?->id,
                'nama_tipe_pembayaran' => $payment?->name ?? $cashCategory->name, 'total_pajak' => json_encode($order->tax_breakdown ?? []),
                'total_modifier' => $order->modifier_total, 'total_diskon' => $order->discount_item_total + $order->voucher_discount,
                'diskon_all_item' => json_encode($diskonAllItem), 'rounding_amount' => abs($order->rounding),
                'tanda_rounding' => $order->rounding < 0 ? '-' : '+', 'potongan_point' => 0,
                'atas_nama' => $order->atas_nama,
            ]);
            foreach ($items as $index => [$item, $modifiers]) {
                if ($item->variant_id) {
                    VariantProduct::whereKey($item->variant_id)->decrement('stok', $item->qty);
                }
                for ($i = 0; $i < $item->qty; $i++) {
                    TransactionItem::create([
                        'transaction_id' => $transaction->id, 'product_id' => $item->product_id,
                        'variant_id' => $item->variant_id, 'harga' => $item->unit_price,
                        'modifier_id' => json_encode($modifiers),
                        'discount_id' => json_encode($voucherPerUnit[$index][$i] ?? []), 'promo_id' => '[]',
                        'catatan' => mb_substr((string) $item->notes, 0, 255), 'reward_item' => 0,
                    ]);
                }
            }
            DB::table('order_table_payment_claims')->where('order_id', $id)->update([
                'transaction_id' => $transaction->id, 'updated_at' => now(),
            ]);

            return $this->state($order, $shift);
        }, 3);
    }
}
