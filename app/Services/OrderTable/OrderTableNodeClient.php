<?php

namespace App\Services\OrderTable;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use RuntimeException;

class OrderTableNodeClient
{
    public function enabled(): bool
    {
        return (bool) config('order-table.node.enabled') && filled(config('order-table.node.base_url'));
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

    public function rebridge(int $orderId): array
    {
        return $this->post("/api/v1/internal/orders/{$orderId}/rebridge");
    }

    private function post(string $path, array $payload = []): array
    {
        if (! $this->enabled()) {
            throw new RuntimeException('Integrasi Order Table ke Node belum diaktifkan.');
        }

        $response = $this->client()->post($path, $payload);

        $response->throw();

        return $response->json() ?? [];
    }

    private function client(): PendingRequest
    {
        $request = Http::baseUrl(rtrim((string) config('order-table.node.base_url'), '/'))
            ->acceptJson()
            ->timeout((int) config('order-table.node.timeout', 15))
            ->withHeaders([
                'Idempotency-Key' => (string) Str::uuid(),
            ]);

        $token = config('order-table.node.token');
        if (filled($token)) {
            $request = $request->withToken($token);
        }

        return $request;
    }
}
