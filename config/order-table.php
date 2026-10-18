<?php

return [
    'pay_at_cashier_enabled' => env('ORDER_TABLE_PAY_AT_CASHIER_ENABLED', false),
    'claim_seconds' => 120,
    'cash_category_id' => env('ORDER_TABLE_CASH_CATEGORY_ID', 1),
    'fcm' => [
        'enabled' => env('ORDER_TABLE_FCM_ENABLED', false),
        'project_id' => env('ORDER_TABLE_FCM_PROJECT_ID'),
        'credentials' => env('ORDER_TABLE_FCM_CREDENTIALS'),
        'event_key' => env('ORDER_TABLE_EVENT_KEY'),
        'queue' => env('ORDER_TABLE_FCM_QUEUE', 'order-table'),
        'connection' => env('ORDER_TABLE_FCM_QUEUE_CONNECTION', 'database'),
        // Kirim push segera setelah event diterima; scheduler tetap menjadi fallback retry.
        'dispatch_immediately' => env('ORDER_TABLE_FCM_DISPATCH_IMMEDIATELY', true),
    ],

    'node' => [
        'enabled' => env('ORDER_TABLE_NODE_ENABLED', false),
        'base_url' => env('ORDER_TABLE_NODE_URL'),
        'token' => env('ORDER_TABLE_NODE_TOKEN'),
        'internal_api_key' => env('ORDER_TABLE_NODE_INTERNAL_API_KEY', env('ORDER_TABLE_NODE_TOKEN')),
        'timeout' => env('ORDER_TABLE_NODE_TIMEOUT', 15),
    ],
];
