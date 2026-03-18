<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../api/youtube.php';

if (!demo_mode_enabled()) {
    set_flash('error', 'Demo mode is disabled for this environment.');
    redirect('public/index.php');
}

function demo_user_record(): array
{
    return [
        'google_id' => trim((string) config('demo.user.google_id', 'demo-local-user')),
        'name' => trim((string) config('demo.user.name', 'Evaluator Demo')),
        'email' => trim((string) config('demo.user.email', 'demo@localhost.test')),
        'profile_picture' => trim((string) config('demo.user.profile_picture', '')),
    ];
}

function seed_demo_library(PDO $pdo): void
{
    if (!config('demo.seed_on_login', true)) {
        return;
    }

    $catalog = demo_channel_catalog();

    if ($catalog === []) {
        return;
    }

    $channelStatement = $pdo->prepare(
        'INSERT INTO channels (channel_id, title, description, thumbnail)
         VALUES (:channel_id, :title, :description, :thumbnail)
         ON DUPLICATE KEY UPDATE
            title = VALUES(title),
            description = VALUES(description),
            thumbnail = VALUES(thumbnail)'
    );

    $videoStatement = $pdo->prepare(
        'INSERT INTO videos (video_id, channel_id, title, thumbnail, published_at)
         VALUES (:video_id, :channel_id, :title, :thumbnail, :published_at)
         ON DUPLICATE KEY UPDATE
            channel_id = VALUES(channel_id),
            title = VALUES(title),
            thumbnail = VALUES(thumbnail),
            published_at = VALUES(published_at)'
    );

    foreach ($catalog as $channel) {
        $channelStatement->execute([
            ':channel_id' => $channel['channel_id'],
            ':title' => $channel['title'],
            ':description' => $channel['description'],
            ':thumbnail' => $channel['thumbnail'],
        ]);

        foreach ($channel['videos'] as $video) {
            $videoStatement->execute([
                ':video_id' => $video['video_id'],
                ':channel_id' => $video['channel_id'],
                ':title' => $video['title'],
                ':thumbnail' => $video['thumbnail'],
                ':published_at' => $video['published_at'],
            ]);
        }
    }
}

$demoUser = demo_user_record();

if ($demoUser['google_id'] === '' || $demoUser['name'] === '' || !validate_email($demoUser['email'])) {
    set_flash('error', 'The demo user is not configured correctly.');
    redirect('public/index.php');
}

$pdo = db();
$pdo->beginTransaction();

try {
    $statement = $pdo->prepare(
        'INSERT INTO users (google_id, name, email, profile_picture)
         VALUES (:google_id, :name, :email, :profile_picture)
         ON DUPLICATE KEY UPDATE
            name = VALUES(name),
            email = VALUES(email),
            profile_picture = VALUES(profile_picture)'
    );

    $statement->execute([
        ':google_id' => $demoUser['google_id'],
        ':name' => $demoUser['name'],
        ':email' => $demoUser['email'],
        ':profile_picture' => $demoUser['profile_picture'],
    ]);

    seed_demo_library($pdo);
    $pdo->commit();
} catch (Throwable) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    set_flash('error', 'The local demo workspace could not be prepared.');
    redirect('public/index.php');
}

$userStatement = $pdo->prepare(
    'SELECT id, google_id, name, email, profile_picture
     FROM users
     WHERE google_id = :google_id
     LIMIT 1'
);
$userStatement->execute([':google_id' => $demoUser['google_id']]);
$user = $userStatement->fetch();

if (!$user) {
    set_flash('error', 'The demo user could not be loaded.');
    redirect('public/index.php');
}

session_regenerate_id(true);
$_SESSION['user'] = $user;
set_flash('success', 'Demo workspace is ready.');

redirect('pages/dashboard.php');
