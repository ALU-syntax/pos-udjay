<?php

namespace App\Services\OrderTable;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class FcmSender
{
    private function accessToken(): string
    {
        $path = (string) config('order-table.fcm.credentials');
        $project = (string) config('order-table.fcm.project_id');
        $key = 'order-table-fcm-oauth-'.hash('sha256', $path.'|'.$project);

        return Cache::remember($key, 3000, function () use ($path) {
            $credentials = is_readable($path) ? json_decode(file_get_contents($path), true) : null;
            if (! is_array($credentials) || empty($credentials['client_email']) || empty($credentials['private_key'])) {
                throw new RuntimeException('FCM_CREDENTIALS_INVALID');
            }
            $encode = fn ($value) => rtrim(strtr(base64_encode(json_encode($value)), '+/', '-_'), '=');
            $unsigned = $encode(['alg' => 'RS256', 'typ' => 'JWT']).'.'.$encode([
                'iss' => $credentials['client_email'], 'scope' => 'https://www.googleapis.com/auth/firebase.messaging',
                'aud' => 'https://oauth2.googleapis.com/token', 'iat' => time(), 'exp' => time() + 3600,
            ]);
            if (! openssl_sign($unsigned, $signature, $credentials['private_key'], OPENSSL_ALGO_SHA256)) {
                throw new RuntimeException('FCM_CREDENTIALS_INVALID');
            }
            $jwt = $unsigned.'.'.rtrim(strtr(base64_encode($signature), '+/', '-_'), '=');
            $response = Http::asForm()->withoutRedirecting()->timeout(10)->post('https://oauth2.googleapis.com/token', [
                'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer', 'assertion' => $jwt,
            ]);
            if (! $response->successful() || ! is_string($response->json('access_token'))) {
                throw new RuntimeException('FCM_OAUTH_FAILED');
            }

            return $response->json('access_token');
        });
    }

    public function send(string $token, array $data): string
    {
        $project = (string) config('order-table.fcm.project_id');
        if (! preg_match('/^[a-zA-Z0-9-]+$/D', $project)) {
            throw new RuntimeException('FCM_PROJECT_INVALID');
        }
        $response = Http::withToken($this->accessToken())->withoutRedirecting()->timeout(15)
            ->post("https://fcm.googleapis.com/v1/projects/{$project}/messages:send", ['message' => [
                'token' => $token, 'data' => $data,
                'android' => ['priority' => 'high', 'ttl' => '60s'],
            ]]);
        if ($response->successful()) {
            return 'sent';
        }
        foreach ($response->json('error.details', []) as $detail) {
            if (($detail['@type'] ?? '') === 'type.googleapis.com/google.firebase.fcm.v1.FcmError'
                && ($detail['errorCode'] ?? '') === 'UNREGISTERED') {
                return 'invalid';
            }
        }
        if ($response->status() === 401) {
            Cache::forget('order-table-fcm-oauth-'.hash('sha256', config('order-table.fcm.credentials').'|'.$project));
        }
        // INVALID_ARGUMENT is not sufficient evidence to delete a token (payload may be invalid).
        throw new RuntimeException('FCM_DELIVERY_RETRY');
    }
}
