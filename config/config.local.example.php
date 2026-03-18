<?php

// For real-service testing, replace the placeholder below with your private
// credentials document or message and paste the real values into the Google
// and YouTube sections further down in this file.
// Credentials link: PASTE_PRIVATE_CREDENTIALS_LINK_HERE

return [
    'app_url' => 'http://localhost/technical-exam',
    'db' => [
        'host' => '127.0.0.1',
        'port' => '3306',
        'name' => 'youtube_sync_app',
        'user' => 'root',
        'pass' => '',
        'charset' => 'utf8mb4',
    ],
    'demo' => [
        'enabled' => true,
        'seed_on_login' => true,
        'user' => [
            'google_id' => 'demo-local-user',
            'name' => 'Evaluator Demo',
            'email' => 'demo@localhost.test',
            'profile_picture' => '',
        ],
    ],
    'google' => [
        'client_id' => 'PASTE_GOOGLE_CLIENT_ID_HERE',
        'client_secret' => 'PASTE_GOOGLE_CLIENT_SECRET_HERE',
        'redirect_uri' => 'http://localhost/technical-exam/auth/callback.php',
    ],
    'youtube' => [
        'api_key' => 'PASTE_YOUTUBE_API_KEY_HERE',
    ],
];
