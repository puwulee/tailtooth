<?php

return [
    'payment' => [
        'driver' => env('PAY_DRIVER', 'manual'), // manual | linepay
        'linepay' => [
            'channel_id' => env('LINEPAY_CHANNEL_ID'),
            'channel_secret' => env('LINEPAY_CHANNEL_SECRET'),
            'base_url' => env('LINEPAY_BASE_URL', 'https://sandbox-api-pay.line.me'),
            'confirm_url' => env('LINEPAY_CONFIRM_URL'),
            'cancel_url' => env('LINEPAY_CANCEL_URL'),
        ],
    ],
    'invoice' => [
        'driver' => env('INVOICE_DRIVER', 'null'), // null | guangmao
        'guangmao' => [
            'endpoint' => env('GUANGMAO_ENDPOINT'),
            'merchant_id' => env('GUANGMAO_MERCHANT_ID'),
            'api_key' => env('GUANGMAO_API_KEY'),
        ],
    ],
    // 退費政策：賽前幾天前可全額退費（折讓）
    'refund_full_days_before' => (int) env('REFUND_FULL_DAYS_BEFORE', 7),
];
