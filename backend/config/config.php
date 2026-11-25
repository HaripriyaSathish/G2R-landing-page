<?php
/**
 * G2R Global Solutions - backend configuration.
 * Only database + app basics live here. SMTP, site content and theme are
 * managed from the admin panel and stored in the database.
 */
return [
    'db' => [
        'host'     => '127.0.0.1',
        'port'     => 3306,
        'name'     => 'g2r_landing',
        'user'     => 'root',
        'pass'     => 'Root123$',
        'charset'  => 'utf8mb4',
    ],

    // Leave empty to auto-detect from the request (recommended).
    // Example for production: 'https://api.g2rglobalsolutions.com'
    'app_url' => '',

    // Frontend origins allowed to call the API (React + Vite dev server etc.)
    'cors_origins' => [
        'http://localhost:5173',
        'http://127.0.0.1:5173',
        'http://localhost:4173',
        'http://127.0.0.1:4173',
    ],

    'upload' => [
        'max_size' => 10 * 1024 * 1024, // 10 MB
        'allowed'  => [
            'image/jpeg' => 'jpg',
            'image/png'  => 'png',
            'image/webp' => 'webp',
            'image/gif'  => 'gif',
            'image/avif' => 'avif',
            'image/svg+xml' => 'svg',
        ],
    ],

    'timezone' => 'Asia/Kolkata',
];
