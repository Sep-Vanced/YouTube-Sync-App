# YouTube Channel Sync App

Framework-free PHP technical exam project for signing in, saving YouTube channels, and syncing uploaded videos.

## Entry Point

Open the app at:

`http://localhost/technical-exam/`

The public entry file is:

`public/index.php`

## Run It Quickly

1. Clone or copy the repository into your local web root, for example:
   `C:\xampp\htdocs\technical-exam`
2. Create the database and tables by importing:
   `database/schema.sql`
3. Start Apache and MySQL.
4. Open:
   `http://localhost/technical-exam/`
5. Use the landing page to enter the app.

## Local Configuration

The app loads base settings from `config/config.php` and local overrides from:

`config/config.local.php`

In this repository, `config/config.local.php` is tracked and currently enables local demo mode with these database settings:

- Host: `127.0.0.1`
- Port: `3306`
- Database: `youtube_sync_app`
- User: `root`
- Password: empty string

## Important Notes

- This repository is evaluator-ready in local demo mode.
- Demo mode is enabled through `config/config.local.php`.
- The landing page can route into a local demo workspace when Google OAuth is not configured.
- Demo mode seeds bundled sample channels and videos for local evaluation.
- The original Google OAuth and YouTube sync flow is still supported by the codebase when real credentials are configured.
- All database queries use prepared statements through PDO.
- Output is escaped with `htmlspecialchars()` to reduce XSS risk.
- The channel detail page shows 20 videos per page.

## How The System Works

1. The user opens the landing page.
2. In demo mode, the app signs the evaluator into a local demo account and can seed sample channels and videos.
3. In real-service mode, the app signs the user in with Google OAuth and stores or updates the user in the `users` table.
4. On the dashboard, the user enters a YouTube Channel ID.
5. The app validates the Channel ID and requests channel details from either the demo data layer or the YouTube Data API.
6. The app reads uploaded videos and saves or updates records in the `channels` and `videos` tables.
7. The dashboard lists saved channels, and the channel page shows synced videos with pagination.

## Project Structure

- `config/` application config and PDO bootstrap
- `auth/` login, callback, logout, and demo login flow
- `api/` YouTube integration and demo data layer
- `actions/` form actions such as channel sync
- `pages/` dashboard and channel views
- `database/` schema file
- `public/` entry page, assets, and public routes

## Real-Service Mode

To use real Google OAuth and YouTube API requests instead of demo mode:

1. Update `config/config.local.php`.
2. Set demo mode to disabled.
3. Provide real Google OAuth and YouTube API credentials.
4. Use the normal Google sign-in flow from the landing page.

## Evaluator Setup Guide

If the evaluator will test the real Google OAuth and YouTube API flow instead of demo mode, send the credentials separately by email and ask them to place the values in:

`config/config.local.php`

They can use this structure:

```php
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
        'enabled' => false,
        'seed_on_login' => true,
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
```

After updating `config/config.local.php`, the evaluator should:

1. Import `database/schema.sql`.
2. Start Apache and MySQL in XAMPP.
3. Open `http://localhost/technical-exam/`.
4. Click `Continue with Google`.
5. Sign in and continue to the dashboard.
