<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/db.php';

function oauth_request(string $url, string $method = 'GET', array $data = []): array
{
    $ch = curl_init();

    if ($ch === false) {
        return [
            'success' => false,
            'message' => 'Could not initialize the OAuth request.',
        ];
    }

    $headers = ['Accept: application/json'];

    if ($method === 'POST') {
        $headers[] = 'Content-Type: application/x-www-form-urlencoded';
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
    } elseif (!empty($data)) {
        $url .= (str_contains($url, '?') ? '&' : '?') . http_build_query($data);
    }

    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_TIMEOUT => 20,
        CURLOPT_HTTPHEADER => $headers,
    ]);

    $response = curl_exec($ch);
    $statusCode = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);

    if ($response === false) {
        return [
            'success' => false,
            'message' => $curlError !== '' ? $curlError : 'The OAuth request failed.',
        ];
    }

    $decoded = json_decode($response, true);

    if (!is_array($decoded)) {
        return [
            'success' => false,
            'message' => 'Google returned an invalid response.',
        ];
    }

    if ($statusCode >= 400) {
        return [
            'success' => false,
            'message' => $decoded['error_description']
                ?? $decoded['error']['message']
                ?? 'Google sign-in could not be completed.',
        ];
    }

    return [
        'success' => true,
        'data' => $decoded,
    ];
}

if (!isset($_GET['code'], $_GET['state']) || !is_string($_GET['code']) || !is_string($_GET['state'])) {
    set_flash('error', 'Google sign-in was canceled or did not return a valid code.');
    redirect('public/index.php');
}

if (empty($_SESSION['oauth_state']) || !hash_equals($_SESSION['oauth_state'], $_GET['state'])) {
    unset($_SESSION['oauth_state']);
    set_flash('error', 'The Google sign-in request could not be verified. Please try again.');
    redirect('public/index.php');
}

unset($_SESSION['oauth_state']);

$tokenResponse = oauth_request('https://oauth2.googleapis.com/token', 'POST', [
    'code' => $_GET['code'],
    'client_id' => (string) config('google.client_id'),
    'client_secret' => (string) config('google.client_secret'),
    'redirect_uri' => (string) config('google.redirect_uri'),
    'grant_type' => 'authorization_code',
]);

if (!$tokenResponse['success']) {
    set_flash('error', 'Google sign-in failed. ' . $tokenResponse['message']);
    redirect('public/index.php');
}

$accessToken = $tokenResponse['data']['access_token'] ?? null;

if (!is_string($accessToken) || $accessToken === '') {
    set_flash('error', 'Google sign-in did not return an access token.');
    redirect('public/index.php');
}

$userResponse = oauth_request('https://www.googleapis.com/oauth2/v3/userinfo', 'GET', [
    'access_token' => $accessToken,
]);

if (!$userResponse['success']) {
    set_flash('error', 'Could not load your Google profile. ' . $userResponse['message']);
    redirect('public/index.php');
}

$googleUser = $userResponse['data'];
$googleId = $googleUser['sub'] ?? '';
$name = trim((string) ($googleUser['name'] ?? ''));
$email = trim((string) ($googleUser['email'] ?? ''));
$picture = trim((string) ($googleUser['picture'] ?? ''));

if ($googleId === '' || $name === '' || !validate_email($email)) {
    set_flash('error', 'Google returned incomplete account details.');
    redirect('public/index.php');
}

$pdo = db();
$statement = $pdo->prepare(
    'INSERT INTO users (google_id, name, email, profile_picture)
     VALUES (:google_id, :name, :email, :profile_picture)
     ON DUPLICATE KEY UPDATE
        name = VALUES(name),
        email = VALUES(email),
        profile_picture = VALUES(profile_picture)'
);

$statement->execute([
    ':google_id' => $googleId,
    ':name' => $name,
    ':email' => $email,
    ':profile_picture' => $picture,
]);

$userStatement = $pdo->prepare(
    'SELECT id, google_id, name, email, profile_picture
     FROM users
     WHERE google_id = :google_id
     LIMIT 1'
);
$userStatement->execute([':google_id' => $googleId]);
$user = $userStatement->fetch();

if (!$user) {
    set_flash('error', 'Your account could not be loaded after sign-in.');
    redirect('public/index.php');
}

session_regenerate_id(true);
$_SESSION['user'] = $user;

set_flash('success', 'Welcome, ' . $user['name'] . '!');
redirect('pages/dashboard.php');
