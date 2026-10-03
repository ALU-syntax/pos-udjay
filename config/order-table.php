<?php

return [
    'public_url' => env('ORDER_TABLE_PUBLIC_URL', env('APP_URL')),
    'pay_at_cashier_enabled' => env('ORDER_TABLE_PAY_AT_CASHIER_ENABLED', false),

    'node' => [
        'enabled' => env('ORDER_TABLE_NODE_ENABLED', false),
        'base_url' => env('ORDER_TABLE_NODE_URL'),
        'token' => env('ORDER_TABLE_NODE_TOKEN'),
        'timeout' => env('ORDER_TABLE_NODE_TIMEOUT', 15),
    ],
];
