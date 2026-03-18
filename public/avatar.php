<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/db.php';

require_login();

$user = current_user();
$googleId = trim((string) ($user['google_id'] ?? ''));
$email = trim((string) ($user['email'] ?? ''));

if ($googleId === '' && $email === '') {
    http_response_code(404);
    exit;
}

$pdo = db();

if ($googleId !== '') {
    $statement = $pdo->prepare('SELECT profile_picture FROM users WHERE google_id = :google_id LIMIT 1');
    $statement->execute([':google_id' => $googleId]);
} else {
    $statement = $pdo->prepare('SELECT profile_picture FROM users WHERE email = :email LIMIT 1');
    $statement->execute([':email' => $email]);
}

$profilePicture = trim((string) $statement->fetchColumn());

if ($profilePicture === '') {
    http_response_code(404);
    exit;
}

function output_avatar_file(string $path): never
{
    $mime = mime_content_type($path);
    if (!is_string($mime) || !str_starts_with($mime, 'image/')) {
        $mime = 'image/jpeg';
    }

    header('Content-Type: ' . $mime);
    header('Cache-Control: private, max-age=600');
    readfile($path);
    exit;
}

if (!filter_var($profilePicture, FILTER_VALIDATE_URL)) {
    $localPath = realpath(__DIR__ . '/..') . DIRECTORY_SEPARATOR . ltrim(str_replace('/', DIRECTORY_SEPARATOR, $profilePicture), DIRECTORY_SEPARATOR);
    if ($localPath !== false && is_file($localPath)) {
        output_avatar_file($localPath);
    }

    http_response_code(404);
    exit;
}

$avatarsDir = __DIR__ . '/uploads/avatars';
if (!is_dir($avatarsDir)) {
    mkdir($avatarsDir, 0777, true);
}

$fileKey = $googleId !== '' ? $googleId : md5($email);
$cachedPath = $avatarsDir . '/' . $fileKey . '.jpg';

if (is_file($cachedPath)) {
    output_avatar_file($cachedPath);
}

$ch = curl_init($profilePicture);
if ($ch === false) {
    http_response_code(404);
    exit;
}

curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_TIMEOUT => 20,
    CURLOPT_HTTPHEADER => ['Accept: image/*'],
    CURLOPT_USERAGENT => 'Mozilla/5.0',
]);

$imageData = curl_exec($ch);
$statusCode = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
$contentType = (string) curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
curl_close($ch);

if (!is_string($imageData) || $imageData === '' || $statusCode >= 400) {
    http_response_code(404);
    exit;
}

file_put_contents($cachedPath, $imageData);

$relativePath = 'public/uploads/avatars/' . basename($cachedPath);
$update = $pdo->prepare('UPDATE users SET profile_picture = :profile_picture WHERE google_id = :google_id');
if ($googleId !== '') {
    $update->execute([
        ':profile_picture' => $relativePath,
        ':google_id' => $googleId,
    ]);
}

header('Content-Type: ' . (($contentType !== '' && str_starts_with($contentType, 'image/')) ? $contentType : 'image/jpeg'));
header('Cache-Control: private, max-age=600');
echo $imageData;