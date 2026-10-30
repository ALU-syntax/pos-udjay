<?php

namespace App\Services\OrderTable;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class OrderTableNodeClient
{
    public function enabled(): bool
    {
        return (bool) config('order-table.node.enabled') && filled(config('order-table.node.base_url'))
            && filled(config('order-table.node.internal_api_key'));
    }

    public function serve(int $orderId): array
    {
        return $this->post("/api/v1/internal/orders/{$orderId}/serve");
    }

    public function cancel(int $orderId, string $reason): array
    {
        return $this->post("/api/v1/internal/orders/{$orderId}/cancel", ['reason' => $reason]);
    }

    public function closeSession(int $sessionId, ?string $reason = null): array
    {
        return $this->post("/api/v1/internal/sessions/{$sessionId}/close", array_filter([
            'reason' => $reason,
        ], fn ($value) => $value !== null));
    }

    public function settle(int $orderId, int $posTransactionId, string $idempotencyKey): array
    {
        return $this->post(
            "/api/v1/internal/orders/{$orderId}/settle",
            ['posTransactionId' => $posTransactionId],
            $idempotencyKey,
        );
    }

    public function rebridge(int $orderId): array
    {
        return $this->post("/api/v1/internal/orders/{$orderId}/rebridge");
    }

    /**
     * Pratinjau total order setelah edit kasir (Node tidak menulis apa pun).
     *
     * @param  array<int, array<string, mixed>>  $items  daftar item AKHIR
     */
    public function quoteEdit(int $orderId, array $items, bool $dropVoucher): array
    {
        $body = $this->send('post', "/api/v1/internal/orders/{$orderId}/quote", [
            'items' => $items, 'dropVoucher' => $dropVoucher,
        ]);

        // Hanya angka & nama item yang diteruskan ke kasir, bukan body Node apa adanya.
        return [
            'previousGrandTotal' => (int) ($body['previousGrandTotal'] ?? 0),
            'changed' => (bool) ($body['changed'] ?? false),
            'priceChanged' => (bool) ($body['priceChanged'] ?? false),
            'voucherDropped' => (bool) ($body['voucherDropped'] ?? false),
            'pricing' => collect(['subtotal', 'discountItemTotal', 'voucherDiscount', 'modifierTotal',
                'taxTotal', 'rounding', 'grandTotal'])
                ->mapWithKeys(fn ($key) => [$key => (int) ($body['pricing'][$key] ?? 0)])->all(),
            'taxBreakdown' => collect($body['pricing']['taxBreakdown'] ?? [])->map(fn ($tax) => [
                'name' => (string) ($tax['name'] ?? ''), 'amount' => (float) ($tax['amount'] ?? 0),
                'total' => (int) ($tax['total'] ?? 0),
            ])->values()->all(),
            'voucher' => isset($body['voucher']['code']) ? [
                'code' => (string) $body['voucher']['code'], 'discount' => (int) ($body['voucher']['discount'] ?? 0),
            ] : null,
            'items' => collect($body['items'] ?? [])->map(fn ($item) => [
                'productId' => (int) ($item['productId'] ?? 0), 'variantId' => (int) ($item['variantId'] ?? 0),
                'productName' => (string) ($item['productName'] ?? ''), 'variantName' => $item['variantName'] ?? null,
                'unitPrice' => (int) ($item['unitPrice'] ?? 0), 'qty' => (int) ($item['quantity'] ?? 0),
                'modifierTotal' => (int) ($item['modifierTotal'] ?? 0), 'lineTotal' => (int) ($item['lineTotal'] ?? 0),
                'notes' => $item['note'] ?? null,
                'modifiers' => collect($item['modifiers'] ?? [])->map(fn ($modifier) => [
                    'modifierId' => (int) ($modifier['modifierId'] ?? 0), 'name' => (string) ($modifier['name'] ?? ''),
                    'harga' => (int) ($modifier['price'] ?? 0), 'qty' => (int) ($modifier['quantity'] ?? 0),
                ])->values()->all(),
            ])->values()->all(),
        ];
    }

    /** Simpan edit kasir secara atomik di Node; idempoten per $idempotencyKey. */
    public function updateItems(int $orderId, array $items, bool $dropVoucher, string $idempotencyKey): array
    {
        $body = $this->send('put', "/api/v1/internal/orders/{$orderId}/items", [
            'items' => $items, 'dropVoucher' => $dropVoucher,
        ], $idempotencyKey);

        return ['changed' => (bool) ($body['changed'] ?? false), 'grandTotal' => (int) ($body['grandTotal'] ?? 0)];
    }

    private function post(string $path, array $payload = [], ?string $idempotencyKey = null): array
    {
        $this->send('post', $path, $payload, $idempotencyKey);

        // Do not relay arbitrary Node bodies (gateway data, SQL errors, or secrets).
        return ['accepted' => true];
    }

    /** Kode bisnis Node yang aman diteruskan ke kasir. */
    private const RELAYED_CODES = ['ORDER_NOT_FOUND', 'NOT_FOUND', 'ORDER_NOT_SETTLEABLE', 'PAYMENT_NOT_PENDING',
        'POS_TRANSACTION_NOT_FOUND', 'POS_TRANSACTION_AMOUNT_MISMATCH', 'ORDER_NOT_SERVEABLE',
        'ORDER_NOT_EDITABLE', 'ORDER_ITEMS_CHANGED', 'VOUCHER_NO_LONGER_ELIGIBLE', 'OUT_OF_STOCK',
        'MODIFIER_OUT_OF_STOCK', 'INVALID_VARIANT', 'INVALID_MODIFIER', 'REQUIRED_MODIFIER_MISSING',
        'DUPLICATE_MODIFIER', 'ORDER_NOT_CANCELLABLE'];

    /** Kode yang pesannya (nama produk / alasan voucher) boleh ditampilkan ke kasir. */
    private const MESSAGE_CODES = ['VOUCHER_NO_LONGER_ELIGIBLE', 'OUT_OF_STOCK', 'MODIFIER_OUT_OF_STOCK',
        'REQUIRED_MODIFIER_MISSING', 'INVALID_VARIANT', 'INVALID_MODIFIER', 'DUPLICATE_MODIFIER'];

    private function send(string $method, string $path, array $payload = [], ?string $idempotencyKey = null): array
    {
        if (! $this->enabled()) {
            throw new CashierError('ORDER_TABLE_UNAVAILABLE', 503);
        }

        try {
            $response = $this->client($idempotencyKey)->{$method}($path, $payload);
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            throw new CashierError('ORDER_TABLE_UNAVAILABLE', 503);
        }

        if (! $response->successful()) {
            $code = $response->json('code') ?? $response->json('error.code');
            if (is_string($code) && in_array($code, self::RELAYED_CODES, true) && in_array($response->status(), [404, 409, 422], true)) {
                throw new CashierError($code, $response->status(), $this->safeDetails($code, $response));
            }
            throw new CashierError('ORDER_TABLE_UNAVAILABLE', 503);
        }

        return (array) $response->json();
    }

    private function safeDetails(string $code, \Illuminate\Http\Client\Response $response): array
    {
        if (! in_array($code, self::MESSAGE_CODES, true)) {
            return [];
        }
        $message = mb_substr(preg_replace('/[^\P{C}]/u', '', (string) $response->json('message')), 0, 160);
        $details = array_filter(['message' => $message ?: null]);
        if ($code === 'VOUCHER_NO_LONGER_ELIGIBLE') {
            $details += array_filter([
                'reason' => is_string($response->json('details.reason')) ? mb_substr($response->json('details.reason'), 0, 40) : null,
                'reasonMessage' => is_string($response->json('details.message')) ? mb_substr($response->json('details.message'), 0, 160) : null,
                'voucherCode' => is_string($response->json('details.voucherCode')) ? mb_substr($response->json('details.voucherCode'), 0, 50) : null,
            ]);
        }

        return $details;
    }

    private function client(?string $idempotencyKey = null): PendingRequest
    {
        $request = Http::baseUrl(rtrim((string) config('order-table.node.base_url'), '/'))
            ->acceptJson()
            ->withoutRedirecting()
            ->timeout((int) config('order-table.node.timeout', 15))
            ->withHeaders([
                'Idempotency-Key' => $idempotencyKey ?: (string) Str::uuid(),
                'X-Actor-Id' => (string) (auth()->id() ?? ''),
                'X-Actor-Name' => preg_replace('/[^\x20-\x7E]/', '', mb_substr((string) auth()->user()?->name, 0, 100)),
            ]);

        $token = config('order-table.node.internal_api_key');
        if (filled($token)) {
            $request = $request->withHeaders(['X-Internal-Api-Key' => $token]);
        }

        return $request;
    }
}
