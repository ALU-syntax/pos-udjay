<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'mailgun' => [
        'domain' => env('MAILGUN_DOMAIN'),
        'secret' => env('MAILGUN_SECRET'),
        'endpoint' => env('MAILGUN_ENDPOINT', 'api.mailgun.net'),
        'scheme' => 'https',
    ],

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    // Gateway WhatsApp (Baileys) untuk reminder tagihan B2B, repo terpisah udjaya-wa-gateway.
    // Kosong = channel WhatsApp tidak aktif. Lokal: http://127.0.0.1:3100; production: URL HTTPS gateway.
    'b2b_wa_gateway' => [
        'url' => env('B2B_WA_GATEWAY_URL'),
        'key' => env('B2B_WA_GATEWAY_KEY'),
        // Gateway mengantrekan pesan dengan jeda 4–9 detik, jadi satu request kirim bisa menunggu cukup lama.
        'timeout' => (int) env('B2B_WA_GATEWAY_TIMEOUT', 120),
    ],

];
