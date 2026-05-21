<?php

return [
    /*
    |--------------------------------------------------------------------------
    | CORS Configuration
    | Izinkan Next.js frontend (localhost:3000) mengakses API
    |--------------------------------------------------------------------------
    */

    'paths' => ['api/*', 'sanctum/csrf-cookie'],

    'allowed_methods' => ['*'],

    'allowed_origins' => [
        env('FRONTEND_URL', 'http://localhost:3000'),
        'http://localhost:3001',
    ],

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    // Sanctum membutuhkan credentials = true jika pakai cookie
    // Untuk token-based (Next.js), bisa false
    'supports_credentials' => true,
];
