<?php

declare(strict_types=1);

return [
    'app_url' => 'http://localhost:8000',
    'db' => [
        'host' => '127.0.0.1',
        'port' => '3306',
        'name' => 'youtube_sync_app',
        'user' => 'root',
        'pass' => '',
        'charset' => 'utf8mb4',
    ],
    'google' => [
        'client_id' => 'YOUR_GOOGLE_CLIENT_ID',
        'client_secret' => 'YOUR_GOOGLE_CLIENT_SECRET',
        'redirect_uri' => 'http://localhost:8000/auth/callback.php',
    ],
    'youtube' => [
        'api_key' => 'YOUR_YOUTUBE_API_KEY',
    ],
];
