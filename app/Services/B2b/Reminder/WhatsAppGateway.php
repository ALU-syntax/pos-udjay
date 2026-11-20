<?php

namespace App\Services\B2b\Reminder;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

/**
 * Klien HTTP untuk udjaya-wa-gateway (Baileys). Semua request memakai Bearer API key dari config services.b2b_wa_gateway.
 * Error gateway dilempar sebagai WhatsAppGatewayException dengan kode dari gateway (not_connected, not_on_whatsapp, …).
 */
class WhatsAppGateway
{
    public function isConfigured(): bool
    {
        return filled(config('services.b2b_wa_gateway.url')) && filled(config('services.b2b_wa_gateway.key'));
    }

    /** @return array{state: string, me: ?array, qr_available: bool, last_connected_at: ?string, last_disconnect: ?array, queue_pending?: int} */
    public function status(): array
    {
        return $this->call('get', '/status');
    }

    public function connect(): array
    {
        return $this->call('post', '/connect');
    }

    /** Data URL PNG QR untuk ditautkan dari HP. */
    public function qr(): string
    {
        return $this->call('get', '/qr')['qr'];
    }

    public function pairingCode(string $phone): string
    {
        return $this->call('post', '/pairing-code', ['phone' => $phone])['code'];
    }

    public function logout(): array
    {
        return $this->call('post', '/logout');
    }

    /**
     * Kirim teks, atau PDF dengan teks sebagai caption. Mengembalikan id pesan dari WhatsApp.
     *
     * @param  array{filename: string, content: string}|null  $pdf  content = isi biner PDF
     */
    public function send(string $phone, string $text, ?array $pdf = null): ?string
    {
        $payload = ['to' => $phone, 'text' => $text];
        if ($pdf) {
            $payload['document'] = ['filename' => $pdf['filename'], 'mimetype' => 'application/pdf', 'base64' => base64_encode($pdf['content'])];
        }

        return $this->call('post', '/send', $payload)['id'] ?? null;
    }

    private function call(string $method, string $path, array $payload = []): array
    {
        if (! $this->isConfigured()) {
            throw new WhatsAppGatewayException('Gateway WhatsApp belum dikonfigurasi (B2B_WA_GATEWAY_URL / B2B_WA_GATEWAY_KEY).', 'not_configured');
        }

        try {
            $response = $this->request()->{$method}($path, $method === 'get' ? null : $payload);
        } catch (ConnectionException $e) {
            throw new WhatsAppGatewayException('Gateway WhatsApp tidak bisa dihubungi: '.$e->getMessage(), 'unreachable', previous: $e);
        }

        if ($response->failed()) {
            throw $this->error($response);
        }

        return (array) $response->json();
    }

    private function request(): PendingRequest
    {
        return Http::baseUrl(rtrim((string) config('services.b2b_wa_gateway.url'), '/'))
            ->withToken((string) config('services.b2b_wa_gateway.key'))
            ->acceptJson()
            ->asJson()
            ->connectTimeout(10)
            ->timeout((int) config('services.b2b_wa_gateway.timeout', 120));
    }

    private function error(Response $response): WhatsAppGatewayException
    {
        $message = (string) ($response->json('error') ?: 'Gateway WhatsApp membalas HTTP '.$response->status());

        return new WhatsAppGatewayException($message, $response->json('code') ?: 'http_'.$response->status(), $response->status());
    }
}
