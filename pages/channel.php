<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../api/youtube.php';

require_login();

$flash = get_flash();
$user = current_user();
$userName = trim((string) ($user['name'] ?? 'Channel Manager'));
$userEmail = trim((string) ($user['email'] ?? ''));
$userAvatar = user_avatar_url($user);
$userInitial = strtoupper(substr($userName !== '' ? $userName : ($userEmail !== '' ? $userEmail : 'U'), 0, 1));
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

$channelDescription = trim((string) $channel['description']);
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
    <div class="admin-shell">
        <aside class="admin-sidebar">
            <p class="eyebrow">Channel View</p>

            <div class="sidebar-user-block">
                <div class="avatar-stack">
                    <?php if ($userAvatar !== ''): ?>
                        <img class="sidebar-user-avatar" src="<?= e($userAvatar); ?>" alt="<?= e($userName); ?>">
                    <?php else: ?>
                        <span class="sidebar-user-fallback"><?= e($userInitial); ?></span>
                    <?php endif; ?>
                </div>
                <span class="sidebar-role">Library</span>
                <strong><?= e($userName); ?></strong>
                <?php if ($userEmail !== ''): ?>
                    <span><?= e($userEmail); ?></span>
                <?php endif; ?>
                <small><?= count($allChannels); ?> saved channel(s)</small>
            </div>

            <nav class="sidebar-menu" aria-label="Channel navigation">
                <a class="sidebar-menu-link" href="<?= e(app_url('pages/dashboard.php')); ?>">Dashboard</a>
                <?php foreach ($allChannels as $savedChannel): ?>
                    <?php $active = $savedChannel['channel_id'] === $channelId; ?>
                    <a class="sidebar-menu-link<?= $active ? ' active' : ''; ?>" href="<?= e(app_url('pages/channel.php?id=' . urlencode((string) $savedChannel['channel_id']))); ?>">
                        <?= e($savedChannel['title']); ?>
                    </a>
                <?php endforeach; ?>
            </nav>

            <div class="sidebar-footnote">
                <p>Review synced uploads and move across the saved channels from the left sidebar.</p>
                <a class="button button-secondary" href="<?= e(app_url('auth/logout.php')); ?>">Logout</a>
            </div>
        </aside>

        <main class="admin-content">
            <header class="admin-header">
                <div class="brand-badge">
                    <span class="brand-badge-dot"></span>
                    <span><?= e($channel['title']); ?></span>
                </div>

                <div class="header-actions">
                    <a class="button button-secondary" href="<?= e(app_url('pages/dashboard.php')); ?>">Back to dashboard</a>
                    <details class="account-menu">
                        <summary class="account-trigger compact-trigger">
                            <div class="account-meta">
                                <strong><?= e($userName); ?></strong>
                                <span><?= e($userEmail !== '' ? $userEmail : 'Logged in user'); ?></span>
                            </div>
                            <?php if ($userAvatar !== ''): ?>
                                <img class="header-avatar" src="<?= e($userAvatar); ?>" alt="<?= e($userName); ?>">
                            <?php else: ?>
                                <span class="header-avatar-fallback"><?= e($userInitial); ?></span>
                            <?php endif; ?>
                            <span class="account-caret">Menu</span>
                        </summary>
                        <div class="account-dropdown">
                            <div class="account-dropdown-head">
                                <strong><?= e($userName); ?></strong>
                                <span><?= e($userEmail !== '' ? $userEmail : 'Logged in user'); ?></span>
                            </div>
                            <div class="account-dropdown-actions">
                                <a class="account-dropdown-item account-dropdown-item-muted" href="<?= e(app_url('pages/dashboard.php')); ?>">Dashboard</a>
                                <a class="account-dropdown-item account-dropdown-item-danger" href="<?= e(app_url('auth/logout.php')); ?>">Logout</a>
                            </div>
                        </div>
                    </details>
                </div>
            </header>

            <?php if ($flash): ?>
                <div class="alert alert-<?= e($flash['type']); ?>">
                    <?= e($flash['message']); ?>
                </div>
            <?php endif; ?>

            <section class="overview-panel reveal-up">
                <div class="page-heading">
                    <p class="section-chip">Saved Channel</p>
                    <h1><?= e($channel['title']); ?></h1>
                    <p>Browse the latest synced uploads for this channel and move between the rest of your saved library.</p>
                </div>

                <div class="metric-grid">
                    <article class="metric-card">
                        <span>Synced videos</span>
                        <strong><?= $totalVideos; ?></strong>
                        <p class="muted">Stored videos available for this channel.</p>
                    </article>
                    <article class="metric-card">
                        <span>Current page</span>
                        <strong><?= $page; ?></strong>
                        <p class="muted">Viewing page <?= $page; ?> of <?= $totalPages; ?>.</p>
                    </article>
                    <article class="metric-card">
                        <span>Saved on</span>
                        <strong><?= e(date('M d', strtotime((string) $channel['created_at']))); ?></strong>
                        <p class="muted"><?= e($channel['channel_id']); ?></p>
                    </article>
                </div>
            </section>

            <div class="channel-page-grid">
                <aside class="panel-card reveal-up delay-1">
                    <div class="channel-profile clean-channel-profile">
                        <?php if (!empty($channel['thumbnail'])): ?>
                            <img class="channel-hero-thumb" src="<?= e($channel['thumbnail']); ?>" alt="<?= e($channel['title']); ?>">
                        <?php else: ?>
                            <span class="sidebar-user-fallback"><?= e(strtoupper(substr((string) $channel['title'], 0, 1))); ?></span>
                        <?php endif; ?>
                        <div>
                            <h2><?= e($channel['title']); ?></h2>
                            <p class="muted"><?= e($channel['channel_id']); ?></p>
                        </div>
                    </div>

                    <p class="channel-description">
                        <?= nl2br(e($channelDescription !== '' ? $channelDescription : 'No channel description was provided.')); ?>
                    </p>

                    <div class="meta-list">
                        <span class="mini-tag"><?= $totalVideos; ?> synced videos</span>
                        <span class="mini-tag">Page <?= $page; ?> of <?= $totalPages; ?></span>
                    </div>

                    <div class="panel-head clean-channel-nav">
                        <h3>Saved channels</h3>
                        <p>Switch between every stored channel without returning to the dashboard.</p>
                    </div>

                    <div class="channel-nav">
                        <?php foreach ($allChannels as $savedChannel): ?>
                            <?php $active = $savedChannel['channel_id'] === $channelId; ?>
                            <a class="channel-nav-link<?= $active ? ' active' : ''; ?>" href="<?= e(app_url('pages/channel.php?id=' . urlencode((string) $savedChannel['channel_id']))); ?>">
                                <?= e($savedChannel['title']); ?>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </aside>

                <section class="panel-card reveal-up delay-2">
                    <div class="panel-head-row">
                        <div class="panel-head">
                            <h2>Videos</h2>
                            <p><?= $perPage; ?> videos per page, sorted by latest publish date first.</p>
                        </div>
                        <span class="mini-tag"><?= $totalVideos; ?> total videos</span>
                    </div>

                    <?php if (empty($videos)): ?>
                        <div class="empty-state">
                            <p>No videos were found for this channel.</p>
                        </div>
                    <?php else: ?>
                        <div class="video-grid clean-video-grid">
                            <?php foreach ($videos as $video): ?>
                                <article class="clean-video-card">
                                    <?php if (!empty($video['thumbnail'])): ?>
                                        <img class="video-thumb" src="<?= e($video['thumbnail']); ?>" alt="<?= e($video['title']); ?>">
                                    <?php endif; ?>
                                    <div class="video-card-body">
                                        <h3><?= e($video['title']); ?></h3>
                                        <p class="muted"><?= e(date('M d, Y H:i', strtotime((string) $video['published_at']))); ?></p>
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
            </div>
        </main>
    </div>
</body>
</html>
