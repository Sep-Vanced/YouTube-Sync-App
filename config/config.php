<?php

declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (($_SERVER['SERVER_PORT'] ?? null) === '443');

    session_name('yt_channel_sync_session');
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'domain' => '',
        'secure' => $isHttps,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

$config = [
    'app_url' => 'http://localhost/technical-exam',
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
        'redirect_uri' => 'http://localhost/technical-exam/auth/callback.php',
    ],
    'youtube' => [
        'api_key' => 'YOUR_YOUTUBE_API_KEY',
    ],
];

$localConfigPath = __DIR__ . '/config.local.php';

if (file_exists($localConfigPath)) {
    $localConfig = require $localConfigPath;

    if (is_array($localConfig)) {
        $config = array_replace_recursive($config, $localConfig);
    }
}

function config(string $key, mixed $default = null): mixed
{
    global $config;

    $segments = explode('.', $key);
    $value = $config;

    foreach ($segments as $segment) {
        if (!is_array($value) || !array_key_exists($segment, $value)) {
            return $default;
        }

        $value = $value[$segment];
    }

    return $value;
}

function app_url(string $path = ''): string
{
    $baseUrl = rtrim((string) config('app_url', ''), '/');
    $path = ltrim($path, '/');

    return $path === '' ? $baseUrl : $baseUrl . '/' . $path;
}

function redirect(string $path): never
{
    header('Location: ' . app_url($path));
    exit;
}

function e(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function is_logged_in(): bool
{
    return !empty($_SESSION['user']);
}

function current_user(): ?array
{
    return $_SESSION['user'] ?? null;
}

function require_login(): void
{
    if (!is_logged_in()) {
        set_flash('error', 'Please sign in to continue.');
        redirect('public/index.php');
    }
}

function set_flash(string $type, string $message): void
{
    $_SESSION['flash'] = [
        'type' => $type,
        'message' => $message,
    ];
}

function get_flash(): ?array
{
    if (empty($_SESSION['flash'])) {
        return null;
    }

    $flash = $_SESSION['flash'];
    unset($_SESSION['flash']);

    return $flash;
}

function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}

function verify_csrf(?string $token): bool
{
    return is_string($token)
        && !empty($_SESSION['csrf_token'])
        && hash_equals($_SESSION['csrf_token'], $token);
}

function validate_email(string $email): bool
{
    return (bool) filter_var($email, FILTER_VALIDATE_EMAIL);
}

function is_post_request(): bool
{
    return strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST';
}