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

    /*
     | Penyimpanan gambar produk. 'public' = storage/app/public (jalankan `php artisan storage:link`).
     | Penyedia lain (Cloudinary/S3) cukup mengganti implementasi ImageStorageInterface.
     */
    'image_disk' => env('CAFE_IMAGE_DISK', 'public'),
    'max_image_kb' => (int) env('CAFE_MAX_IMAGE_KB', 2048),
];
