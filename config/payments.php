<?php

return [
    'provider' => 'midtrans',
    'enabled' => (bool) env('MIDTRANS_ENABLED', false),
    'is_production' => (bool) env('MIDTRANS_IS_PRODUCTION', false),
    'server_key' => env('MIDTRANS_SERVER_KEY'),
    'client_key' => env('MIDTRANS_CLIENT_KEY'),
    'merchant_id' => env('MIDTRANS_MERCHANT_ID'),
    'snap_url' => 'https://app.sandbox.midtrans.com/snap/v1/transactions',
    'va_methods' => [
        'bni_va' => 'BNI',
        'echannel' => 'Mandiri',
        'bri_va' => 'BRI',
        'bca_va' => 'BCA',
        'permata_va' => 'Permata',
    ],
    'default_va_method' => 'bni_va',
    'minimum_payment' => 600000,
    'default_service_fee' => (int) env('MIDTRANS_DEFAULT_SERVICE_FEE', 4000),
    'invoice_duration' => 86400,
    'max_amount' => 1000000000,
    'import_limit' => 500,
];
