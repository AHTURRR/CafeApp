<?php

return [
    /*
     | Masa berlaku token Sanctum (hari). Customer lebih lama daripada staf
     | (API_SPECIFICATION bagian 2).
     */
    'token_ttl_days' => [
        'customer' => (int) env('CAFE_TOKEN_TTL_CUSTOMER_DAYS', 30),
        'staff' => (int) env('CAFE_TOKEN_TTL_STAFF_DAYS', 7),
    ],

    /*
     | Zona waktu bisnis untuk penomoran order harian (dipakai mulai langkah I-3).
     */
    'business_timezone' => env('CAFE_BUSINESS_TIMEZONE', env('APP_TIMEZONE', 'UTC')),
];
