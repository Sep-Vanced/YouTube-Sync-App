<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';

if (is_logged_in()) {
    redirect('pages/dashboard.php');
}

if (!google_oauth_configured()) {
    if (demo_mode_enabled()) {
        redirect('auth/demo_login.php');
    }

    set_flash('error', 'Google OAuth is not configured yet. Update config/config.php first.');
    redirect('public/index.php');
}

$clientId = (string) config('google.client_id');
$redirectUri = (string) config('google.redirect_uri');
$state = bin2hex(random_bytes(16));
$_SESSION['oauth_state'] = $state;

$query = http_build_query([
    'client_id' => $clientId,
    'redirect_uri' => $redirectUri,
    'response_type' => 'code',
    'scope' => 'openid email profile',
    'access_type' => 'online',
    'include_granted_scopes' => 'true',
    'prompt' => 'select_account',
    'state' => $state,
]);

header('Location: https://accounts.google.com/o/oauth2/v2/auth?' . $query);
exit;
