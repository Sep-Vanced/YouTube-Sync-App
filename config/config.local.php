<?php
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
];
