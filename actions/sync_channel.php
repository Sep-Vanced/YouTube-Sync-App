<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../api/youtube.php';

require_login();

if (!is_post_request()) {
    redirect('pages/dashboard.php');
}

if (!verify_csrf($_POST['csrf_token'] ?? null)) {
    set_flash('error', 'Your session form expired. Please try again.');
    redirect('pages/dashboard.php');
}

$channelId = trim((string) ($_POST['channel_id'] ?? ''));

if ($channelId === '') {
    set_flash('error', 'Please enter a YouTube Channel ID.');
    redirect('pages/dashboard.php');
}

if (!validate_channel_id($channelId)) {
    set_flash('error', 'Please enter a valid-looking YouTube Channel ID.');
    redirect('pages/dashboard.php');
}

$channelResponse = fetch_channel_details($channelId);

if (!$channelResponse['success']) {
    set_flash('error', $channelResponse['message']);
    redirect('pages/dashboard.php');
}

$videosResponse = fetch_channel_videos($channelId, 100);

if (!$videosResponse['success']) {
    set_flash('error', $videosResponse['message']);
    redirect('pages/dashboard.php');
}

$channel = $channelResponse['data'];
$videos = $videosResponse['data'];

$pdo = db();
$pdo->beginTransaction();

try {
    $channelStatement = $pdo->prepare(
        'INSERT INTO channels (channel_id, title, description, thumbnail)
         VALUES (:channel_id, :title, :description, :thumbnail)
         ON DUPLICATE KEY UPDATE
            title = VALUES(title),
            description = VALUES(description),
            thumbnail = VALUES(thumbnail)'
    );

    $channelStatement->execute([
        ':channel_id' => $channel['channel_id'],
        ':title' => $channel['title'],
        ':description' => $channel['description'],
        ':thumbnail' => $channel['thumbnail'],
    ]);

    if (!empty($videos)) {
        $videoStatement = $pdo->prepare(
            'INSERT INTO videos (video_id, channel_id, title, thumbnail, published_at)
             VALUES (:video_id, :channel_id, :title, :thumbnail, :published_at)
             ON DUPLICATE KEY UPDATE
                channel_id = VALUES(channel_id),
                title = VALUES(title),
                thumbnail = VALUES(thumbnail),
                published_at = VALUES(published_at)'
        );

        foreach ($videos as $video) {
            $videoStatement->execute([
                ':video_id' => $video['video_id'],
                ':channel_id' => $video['channel_id'],
                ':title' => $video['title'],
                ':thumbnail' => $video['thumbnail'],
                ':published_at' => $video['published_at'],
            ]);
        }
    }

    $pdo->commit();
} catch (Throwable $exception) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    set_flash('error', 'The channel could not be saved right now. Please try again.');
    redirect('pages/dashboard.php');
}

if (empty($videos)) {
    set_flash('success', 'Channel saved successfully. No public videos were found for this channel yet.');
    redirect('pages/channel.php?id=' . urlencode($channelId));
}

set_flash('success', 'Channel synced successfully with ' . count($videos) . ' video(s).');
redirect('pages/channel.php?id=' . urlencode($channelId));
