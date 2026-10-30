<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\OrderTable\Order;
use App\Models\Transaction;
use App\Services\OrderTable\OrderTableNodeClient;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OrderTableCashierController extends Controller
{
    private OrderTableNodeClient $nodeClient;

    public function __construct(OrderTableNodeClient $nodeClient, private \App\Services\OrderTable\CashierPaymentService $payments)
    {
        $this->nodeClient = $nodeClient;
        $this->abilities = [];
    }

    /**
     * GET /api/v1/order-table/orders
     *
     * Read-only queue for orders that must be paid at the cashier.
     */
    public function index(Request $request): JsonResponse
    {
        $outletId = $this->outletId($request);
        $validated = $request->validate([
            'status' => ['nullable', 'in:placed,received,expired,cancelled,preparing,served,draft'],
            'payment_mode' => ['nullable', 'in:qris,pay_at_cashier'],
            'updated_since' => ['nullable', 'date'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:100'],
            'cursor' => ['nullable', 'string', 'max:2048'],
            'sync_until' => ['nullable', 'date', 'before_or_equal:now'],
        ]);
        if (! empty($validated['cursor'])) {
            $cursor = \Illuminate\Pagination\Cursor::fromEncoded($validated['cursor']);
            if (! $cursor || ! isset($cursor->toArray()['id'], $cursor->toArray()['updated_at'])) {
                throw \Illuminate\Validation\ValidationException::withMessages(['cursor' => 'Invalid cursor.']);
            }
        }

        $until = \Carbon\Carbon::parse($validated['sync_until'] ?? now()->subSeconds(2))
            ->setTimezone(date_default_timezone_get())->startOfSecond();
        $query = Order::query()
            ->with(['table:id,code,name', 'items.modifiers', 'payments'])
            ->where('outlet_id', $outletId)
            ->where('updated_at', '<=', $until)
            ->when($validated['payment_mode'] ?? null, fn ($q, $mode) => $q->where('payment_mode', $mode))
            ->when($validated['status'] ?? null, fn ($q, $status) => $q->where('status', $status));

        if (! empty($validated['updated_since'])) {
            $query->where('updated_at', '>=', \Carbon\Carbon::parse($validated['updated_since'])->setTimezone(date_default_timezone_get())->format('Y-m-d H:i:s'));
        }

        $orders = $query->orderBy('updated_at')->orderBy('id')
            ->cursorPaginate($validated['limit'] ?? 50);

        return response()->json([
            'status' => 'success',
            'data' => collect($orders->items())->map(fn (Order $order) => $this->orderPayload($order)),
            'meta' => [
                'server_time' => now()->toIso8601String(),
                'sync_until' => $until->toIso8601String(),
                'next_cursor' => $orders->nextCursor()?->encode(),
                'has_more' => $orders->hasMorePages(),
            ],
        ]);
    }

    /** GET /api/v1/order-table/orders/{id} */
    public function show(Request $request, int $id): JsonResponse
    {
        $order = $this->order($request, $id);

        return response()->json(['status' => 'success', 'data' => $this->orderPayload($order)]);
    }

    public function claim(Request $request, int $id): JsonResponse
    {
        $data = $request->validate(['release' => ['nullable', 'boolean'], 'claimToken' => ['nullable', 'uuid']]);

        return response()->json(['status' => 'success', 'data' => $this->payments->claim(
            $id, $request->attributes->get('order_table_shift'), (bool) ($data['release'] ?? false), $data['claimToken'] ?? null
        )]);
    }

    public function pay(Request $request, int $id): JsonResponse
    {
        $data = $request->validate([
            'claimToken' => ['required', 'uuid'], 'paymentId' => ['present', 'nullable', 'integer', 'min:1'],
            'nominalBayar' => ['required', 'integer', 'min:0'],
            ...$this->editRules(required: false),
        ]);
        $data['items'] = empty($data['items']) ? null : $this->normalizeEditItems($data['items']);

        return response()->json(['status' => 'success', 'data' => $this->payments->pay(
            $id, $request->attributes->get('order_table_shift'), $data
        )]);
    }

    /**
     * POST /api/v1/order-table/orders/{id}/quote
     *
     * Pratinjau total setelah kasir mengubah item (wajib memegang claim). Tidak menyimpan apa pun.
     */
    public function quote(Request $request, int $id): JsonResponse
    {
        $data = $request->validate(['claimToken' => ['required', 'uuid'], ...$this->editRules(required: true)]);
        $data['items'] = $this->normalizeEditItems($data['items']);

        return response()->json(['status' => 'success', 'data' => $this->payments->quoteEdit(
            $id, $request->attributes->get('order_table_shift'), $data
        )]);
    }

    /** Daftar item AKHIR order (bukan selisih). `orderItemId` = baris lama yang diwakili. */
    private function editRules(bool $required): array
    {
        return [
            // Di pay(): kosong/tidak dikirim = bayar tanpa edit. Di quote(): wajib minimal 1 item.
            'items' => $required ? ['required', 'array', 'min:1', 'max:100'] : ['nullable', 'array', 'max:100'],
            'items.*.orderItemId' => ['nullable', 'integer', 'min:1'],
            'items.*.productId' => ['required', 'integer', 'min:1'],
            'items.*.variantId' => ['required', 'integer', 'min:1'],
            'items.*.qty' => ['required', 'integer', 'min:1', 'max:20'],
            'items.*.notes' => ['nullable', 'string', 'max:255'],
            'items.*.modifiers' => ['nullable', 'array', 'max:20'],
            'items.*.modifiers.*.modifierId' => ['required', 'integer', 'min:1'],
            'items.*.modifiers.*.qty' => ['required', 'integer', 'min:1', 'max:20'],
            'dropVoucher' => ['sometimes', 'boolean'],
        ];
    }

    /** Format kasir (qty/notes) → format Node (quantity/note). */
    private function normalizeEditItems(array $items): array
    {
        return array_map(fn (array $item) => array_filter([
            'orderItemId' => $item['orderItemId'] ?? null,
            'productId' => (int) $item['productId'],
            'variantId' => (int) $item['variantId'],
            'quantity' => (int) $item['qty'],
            'note' => isset($item['notes']) && trim($item['notes']) !== '' ? trim($item['notes']) : null,
            'modifiers' => array_map(fn (array $modifier) => [
                'modifierId' => (int) $modifier['modifierId'], 'quantity' => (int) $modifier['qty'],
            ], $item['modifiers'] ?? []),
        ], fn ($value) => $value !== null), array_values($items));
    }

    public function heartbeat(Request $request, int $id): JsonResponse
    {
        $data = $request->validate(['claimToken' => ['required', 'uuid']]);

        return response()->json(['status' => 'success', 'data' => $this->payments->heartbeat(
            $id, $request->attributes->get('order_table_shift'), $data['claimToken']
        )]);
    }

    public function paymentState(Request $request, int $id): JsonResponse
    {
        return response()->json(['status' => 'success', 'data' => $this->payments->state(
            $this->order($request, $id), $request->attributes->get('order_table_shift')
        )]);
    }

    public function co(Request $request, int $id): JsonResponse
    {
        $order = $this->order($request, $id);
        if ($order->payment_status !== 'paid') {
            throw new \App\Services\OrderTable\CashierError('ORDER_NOT_PAID');
        }

        return response()->json(['status' => 'success', 'data' => $this->orderPayload($order)]);
    }

    public function receipt(Request $request, int $id): JsonResponse
    {
        $order = $this->order($request, $id);
        $transaction = $this->payments->transaction($order);
        if (! $transaction) {
            throw new \App\Services\OrderTable\CashierError('POS_TRANSACTION_REQUIRED');
        }

        return response()->json(['status' => 'success', 'data' => [
            'order' => $this->orderPayload($order),
            'transaction' => $transaction->only(['id', 'reference_id', 'outlet_id', 'patty_cash_id', 'shift_session_id',
                'total', 'nominal_bayar', 'change', 'tipe_pembayaran', 'nama_tipe_pembayaran', 'total_pajak',
                'total_diskon', 'total_modifier', 'rounding_amount', 'tanda_rounding', 'atas_nama', 'created_at']),
            'items' => $transaction->itemTransaction->map(fn ($item) => $item->only([
                'id', 'product_id', 'variant_id', 'harga', 'modifier_id', 'discount_id', 'catatan',
            ])),
        ]]);
    }

    public function serve(Request $request, int $id): JsonResponse
    {
        $order = $this->order($request, $id);
        if ($order->payment_status !== 'paid' || ! in_array($order->status, ['received', 'preparing', 'served'], true)) {
            throw new \App\Services\OrderTable\CashierError('ORDER_NOT_SERVEABLE');
        }
        if ($order->status !== 'served') {
            app(\App\Services\OrderTable\AuditLogger::class)->log('order.serve-requested', $order,
                ['status' => $order->status], null, ['shift_session_id' => $request->attributes->get('order_table_shift')->id]);
            try {
                $this->nodeClient->serve($id);
            } catch (\App\Services\OrderTable\CashierError $e) {
                throw $e;
            } catch (\Throwable $e) {
                throw new \App\Services\OrderTable\CashierError('ORDER_TABLE_UNAVAILABLE', 503);
            }
        }

        return response()->json(['status' => 'success', 'data' => $this->orderPayload($order->fresh())]);
    }

    /**
     * POST /api/v1/order-table/orders/{id}/settle
     *
     * The POS transaction must already exist. Order Table remains the owner
     * of the payment/status transition.
     */
    public function settle(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'posTransactionId' => ['required', 'integer', 'min:1'],
        ]);
        $order = $this->order($request, $id);
        if ($order->payment_mode !== 'pay_at_cashier') {
            return response()->json(['status' => 'error', 'code' => 'CASHIER_MODE_REQUIRED', 'message' => 'Order bukan mode kasir.'], 409);
        }
        $transaction = $this->payments->transaction($order);

        if (! $transaction || (int) $transaction->id !== (int) $validated['posTransactionId']) {
            return response()->json([
                'status' => 'error',
                'code' => 'POS_TRANSACTION_NOT_FOUND',
                'message' => 'Transaksi POS tidak ditemukan atau reference_id tidak cocok.',
            ], 422);
        }

        if (isset($transaction->total) && (int) $transaction->total !== (int) $order->grand_total) {
            return response()->json([
                'status' => 'error',
                'code' => 'POS_TRANSACTION_AMOUNT_MISMATCH',
                'message' => 'Nominal transaksi POS berbeda dengan total order.',
            ], 422);
        }

        try {
            $result = $this->nodeClient->settle(
                $order->id,
                (int) $transaction->id,
                'cashier-settle-'.$order->id.'-'.$transaction->id,
            );
        } catch (\App\Services\OrderTable\CashierError $exception) {
            throw $exception;
        } catch (\Throwable $exception) {
            return response()->json([
                'status' => 'error',
                'code' => 'ORDER_TABLE_UNAVAILABLE',
                'message' => 'Layanan Order Table tidak tersedia. Silakan coba lagi.',
            ], 503);
        }

        return response()->json(['status' => 'success', 'data' => $this->payments->state(
            $order->fresh(), $request->attributes->get('order_table_shift')
        )]);
    }

    private function order(Request $request, int $id): Order
    {
        return Order::query()
            ->with(['table:id,code,name', 'items.modifiers', 'payments'])
            ->whereKey($id)
            ->where('outlet_id', $this->outletId($request))
            ->firstOrFail();
    }

    private function outletId(Request $request): int
    {
        $outletId = $request->attributes->get('order_table_shift')?->outlet_id;

        abort_if(! $outletId, 422, 'User tidak memiliki outlet yang terdaftar.');

        return (int) $outletId;
    }

    private function orderPayload(Order $order): array
    {
        return [
            'id' => $order->id,
            'orderId' => $order->id,
            'orderNo' => $order->order_no,
            'atasNama' => $order->atas_nama,
            'notes' => $order->notes,
            'outletId' => $order->outlet_id,
            'status' => $order->status,
            'paymentMode' => $order->payment_mode,
            'paymentStatus' => $order->payment_status,
            'posBridgeStatus' => $order->pos_bridge_status,
            'posTransactionId' => $order->pos_transaction_id,
            'subtotal' => $order->subtotal,
            'discountItemTotal' => $order->discount_item_total,
            'voucherDiscount' => $order->voucher_discount,
            'modifierTotal' => $order->modifier_total,
            'taxTotal' => $order->tax_total,
            'taxBreakdown' => $order->tax_breakdown,
            'rounding' => $order->rounding,
            'grandTotal' => $order->grand_total,
            'table' => $order->table ? [
                'id' => $order->table->id,
                'code' => $order->table->code,
                'name' => $order->table->name,
            ] : null,
            'items' => $order->items->map(fn ($item) => [
                'id' => $item->id,
                'productId' => $item->product_id,
                'variantId' => $item->variant_id,
                'productName' => $item->product_name,
                'variantName' => $item->variant_name,
                'unitPrice' => $item->unit_price,
                'qty' => $item->qty,
                'modifierTotal' => $item->modifier_total,
                'lineTotal' => $item->line_total,
                'notes' => $item->notes,
                'modifiers' => $item->modifiers->map(fn ($modifier) => [
                    'modifierId' => $modifier->modifier_id, 'name' => $modifier->name,
                    'harga' => $modifier->harga, 'qty' => $modifier->qty,
                ])->values(),
            ])->values(),
            'payments' => $order->payments->map(fn ($payment) => [
                'id' => $payment->id,
                'method' => $payment->method,
                'amount' => $payment->amount,
                'status' => $payment->status,
                'expiresAt' => $payment->expires_at ? $payment->expires_at->toIso8601String() : null,
                'paidAt' => $payment->paid_at ? $payment->paid_at->toIso8601String() : null,
            ])->values(),
            'placedAt' => $order->placed_at ? $order->placed_at->toIso8601String() : null,
            'paymentDueAt' => $order->payment_due_at ? $order->payment_due_at->toIso8601String() : null,
            'paidAt' => $order->paid_at ? $order->paid_at->toIso8601String() : null,
            'updatedAt' => $order->updated_at ? $order->updated_at->toIso8601String() : null,
        ];
    }
}
