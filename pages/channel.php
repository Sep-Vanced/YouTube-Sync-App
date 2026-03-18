<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../api/youtube.php';

require_login();

$flash = get_flash();
$channelId = trim((string) ($_GET['id'] ?? ''));

if ($channelId === '' || !validate_channel_id($channelId)) {
    set_flash('error', 'Please choose a valid channel.');
    redirect('pages/dashboard.php');
}

$pdo = db();

$channelStatement = $pdo->prepare(
    'SELECT channel_id, title, description, thumbnail, created_at
     FROM channels
     WHERE channel_id = :channel_id
     LIMIT 1'
);
$channelStatement->execute([':channel_id' => $channelId]);
$channel = $channelStatement->fetch();

if (!$channel) {
    set_flash('error', 'That channel has not been saved yet.');
    redirect('pages/dashboard.php');
}

$allChannels = $pdo->query('SELECT channel_id, title FROM channels ORDER BY title ASC')->fetchAll();

$perPage = 20;
$page = max(1, (int) ($_GET['page'] ?? 1));

$countStatement = $pdo->prepare('SELECT COUNT(*) FROM videos WHERE channel_id = :channel_id');
$countStatement->execute([':channel_id' => $channelId]);
$totalVideos = (int) $countStatement->fetchColumn();
$totalPages = max(1, (int) ceil($totalVideos / $perPage));

if ($page > $totalPages) {
    $page = $totalPages;
}

$offset = ($page - 1) * $perPage;

$videosStatement = $pdo->prepare(
    'SELECT video_id, title, thumbnail, published_at
     FROM videos
     WHERE channel_id = :channel_id
     ORDER BY published_at DESC
     LIMIT :limit OFFSET :offset'
);
$videosStatement->bindValue(':channel_id', $channelId, PDO::PARAM_STR);
$videosStatement->bindValue(':limit', $perPage, PDO::PARAM_INT);
$videosStatement->bindValue(':offset', $offset, PDO::PARAM_INT);
$videosStatement->execute();
$videos = $videosStatement->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($channel['title']); ?> | YouTube Channel Sync</title>
    <link rel="stylesheet" href="<?= e(app_url('public/assets/styles.css')); ?>">
</head>
<body>
    <div class="app-shell">
        <header class="topbar">
            <div>
                <p class="eyebrow">Channel View</p>
                <h1><?= e($channel['title']); ?></h1>
                <p class="muted">Browse synced videos for this saved channel.</p>
            </div>
            <div class="inline-actions">
                <a class="button button-secondary" href="<?= e(app_url('pages/dashboard.php')); ?>">Back to dashboard</a>
                <a class="button button-secondary" href="<?= e(app_url('auth/logout.php')); ?>">Logout</a>
            </div>
        </header>

        <?php if ($flash): ?>
            <div class="alert alert-<?= e($flash['type']); ?>">
                <?= e($flash['message']); ?>
            </div>
        <?php endif; ?>

        <main class="channel-layout">
            <aside class="card sidebar-card">
                <div class="channel-profile">
                    <?php if (!empty($channel['thumbnail'])): ?>
                        <img class="channel-hero-thumb" src="<?= e($channel['thumbnail']); ?>" alt="<?= e($channel['title']); ?>">
                    <?php endif; ?>
                    <div>
                        <h2><?= e($channel['title']); ?></h2>
                        <p class="muted small"><?= e($channel['channel_id']); ?></p>
                    </div>
                </div>

                <p class="channel-description full"><?= nl2br(e((string) $channel['description'])); ?></p>

                <div class="meta-list">
                    <span class="badge"><?= $totalVideos; ?> synced videos</span>
                    <span class="badge">Page <?= $page; ?> of <?= $totalPages; ?></span>
                </div>

                <hr>

                <h3>Saved channels</h3>
                <div class="channel-nav">
                    <?php foreach ($allChannels as $savedChannel): ?>
                        <?php $active = $savedChannel['channel_id'] === $channelId; ?>
                        <a class="channel-nav-link<?= $active ? ' active' : ''; ?>" href="<?= e(app_url('pages/channel.php?id=' . urlencode((string) $savedChannel['channel_id']))); ?>">
                            <?= e($savedChannel['title']); ?>
                        </a>
                    <?php endforeach; ?>
                </div>
            </aside>

            <section class="card">
                <div class="section-heading">
                    <h2>Videos</h2>
                    <p class="muted">20 videos per page.</p>
                </div>

                <?php if (empty($videos)): ?>
                    <div class="empty-state">
                        <p>No videos were found for this channel.</p>
                    </div>
                <?php else: ?>
                    <div class="video-grid">
                        <?php foreach ($videos as $video): ?>
                            <article class="video-card">
                                <?php if (!empty($video['thumbnail'])): ?>
                                    <img class="video-thumb" src="<?= e($video['thumbnail']); ?>" alt="<?= e($video['title']); ?>">
                                <?php endif; ?>
                                <div class="video-card-body">
                                    <h3><?= e($video['title']); ?></h3>
                                    <p class="muted small"><?= e(date('M d, Y H:i', strtotime((string) $video['published_at']))); ?></p>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <?php if ($totalVideos > $perPage): ?>
                    <nav class="pagination" aria-label="Video pagination">
                        <?php if ($page > 1): ?>
                            <a class="button button-secondary" href="<?= e(app_url('pages/channel.php?id=' . urlencode($channelId) . '&page=' . ($page - 1))); ?>">Previous</a>
                        <?php else: ?>
                            <span class="button button-secondary is-disabled">Previous</span>
                        <?php endif; ?>

                        <span class="pagination-status">Page <?= $page; ?> of <?= $totalPages; ?></span>

                        <?php if ($page < $totalPages): ?>
                            <a class="button button-secondary" href="<?= e(app_url('pages/channel.php?id=' . urlencode($channelId) . '&page=' . ($page + 1))); ?>">Next</a>
                        <?php else: ?>
                            <span class="button button-secondary is-disabled">Next</span>
                        <?php endif; ?>
                    </nav>
                <?php endif; ?>
            </section>
        </main>
    </div>
</body>
</html>
