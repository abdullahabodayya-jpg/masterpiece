<?php

return [
    'payment_mode' => env('FRAME_PAYMENT_MODE', 'disabled'),
    'commission_percent' => env('FRAME_COMMISSION_PERCENT', '15'),
    'frontend_url' => env('FRAME_FRONTEND_URL', 'http://localhost:5173'),
    'stripe' => [
        'key' => env('STRIPE_KEY'),
        'secret' => env('STRIPE_SECRET'),
        'webhook_secret' => env('STRIPE_WEBHOOK_SECRET'),
    ],
];
