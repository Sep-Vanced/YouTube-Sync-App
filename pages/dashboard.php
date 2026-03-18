<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/db.php';

require_login();

$flash = get_flash();
$user = current_user();
$userName = trim((string) ($user['name'] ?? 'Channel Manager'));
$userEmail = trim((string) ($user['email'] ?? ''));
$userAvatar = user_avatar_url($user);
$userInitial = strtoupper(substr($userName !== '' ? $userName : ($userEmail !== '' ? $userEmail : 'U'), 0, 1));

$channelsStatement = db()->query(
    'SELECT c.channel_id, c.title, c.description, c.thumbnail, c.created_at, COUNT(v.id) AS video_count
     FROM channels c
     LEFT JOIN videos v ON v.channel_id = c.channel_id
     GROUP BY c.channel_id, c.title, c.description, c.thumbnail, c.created_at
     ORDER BY c.created_at DESC, c.title ASC'
);
$channels = $channelsStatement->fetchAll();

$channelCount = count($channels);
$totalVideos = 0;

foreach ($channels as $channel) {
    $totalVideos += (int) $channel['video_count'];
}

$latestChannel = $channels[0] ?? null;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard | YouTube Channel Sync</title>
    <link rel="stylesheet" href="<?= e(app_url('public/assets/styles.css')); ?>">
    <script defer src="<?= e(app_url('public/assets/script.js')); ?>"></script>
</head>
<body>
    <div class="admin-shell">
        <aside class="admin-sidebar">
            <p class="eyebrow">YouTube Sync</p>

            <div class="sidebar-user-block">
                <div class="avatar-stack">
                    <?php if ($userAvatar !== ''): ?>
                        <img class="sidebar-user-avatar" src="<?= e($userAvatar); ?>" alt="<?= e($userName); ?>">
                    <?php else: ?>
                        <span class="sidebar-user-fallback"><?= e($userInitial); ?></span>
                    <?php endif; ?>
                </div>
                <span class="sidebar-role">Signed in</span>
                <strong><?= e($userName); ?></strong>
                <?php if ($userEmail !== ''): ?>
                    <span><?= e($userEmail); ?></span>
                <?php endif; ?>
                <small><?= $channelCount; ?> saved channel(s)</small>
            </div>

            <nav class="sidebar-menu" aria-label="Dashboard navigation">
                <a class="sidebar-menu-link active" href="<?= e(app_url('pages/dashboard.php')); ?>">Dashboard</a>
                <?php if ($latestChannel): ?>
                    <a class="sidebar-menu-link" href="<?= e(app_url('pages/channel.php?id=' . urlencode((string) $latestChannel['channel_id']))); ?>">Latest channel</a>
                <?php endif; ?>
            </nav>

            <div class="sidebar-footnote">
                <p>Paste a YouTube Channel ID to fetch the channel profile and its latest uploaded videos.</p>
                <a class="button button-secondary" href="<?= e(app_url('auth/logout.php')); ?>">Logout</a>
            </div>
        </aside>

        <main class="admin-content">
            <header class="admin-header">
                <div class="brand-badge">
                    <span class="brand-badge-dot"></span>
                    <span>YouTube Channel Sync</span>
                </div>

                <div class="header-actions">
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
                    <p class="section-chip">Control Center</p>
                    <h1>Dashboard</h1>
                    <p>Save channels, sync their latest uploads, and jump into any saved library from one place.</p>
                </div>

                <div class="metric-grid">
                    <article class="metric-card">
                        <span>Saved channels</span>
                        <strong><?= $channelCount; ?></strong>
                        <p class="muted">Ready to browse from your dashboard.</p>
                    </article>
                    <article class="metric-card">
                        <span>Synced videos</span>
                        <strong><?= $totalVideos; ?></strong>
                        <p class="muted">Pulled from every stored channel in the database.</p>
                    </article>
                    <article class="metric-card">
                        <span>Latest save</span>
                        <strong><?= $latestChannel ? e(date('M d', strtotime((string) $latestChannel['created_at']))) : '--'; ?></strong>
                        <p class="muted"><?= $latestChannel ? e($latestChannel['title']) : 'No channels saved yet.'; ?></p>
                    </article>
                </div>
            </section>

            <div class="content-grid">
                <section class="panel-card reveal-up delay-1">
                    <div class="panel-head">
                        <h2>Add a channel</h2>
                        <p>Paste a YouTube Channel ID to fetch channel details and up to 100 uploaded videos.</p>
                    </div>

                    <form class="stack" action="<?= e(app_url('actions/sync_channel.php')); ?>" method="post">
                        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()); ?>">

                        <label for="channel_id">YouTube Channel ID</label>
                        <input
                            id="channel_id"
                            name="channel_id"
                            type="text"
                            maxlength="50"
                            placeholder="Example: UC_x5XG1OV2P6uZZ5FSM9Ttw"
                            required
                        >

                        <button class="button button-primary-wide" type="submit">Sync Channel</button>
                    </form>
                </section>

                <section class="panel-card reveal-up delay-2">
                    <div class="panel-head-row">
                        <div class="panel-head">
                            <h2>Saved channels</h2>
                            <p><?= $channelCount; ?> channel(s) available for review.</p>
                        </div>
                        <span class="mini-tag">Shared library</span>
                    </div>

                    <?php if (empty($channels)): ?>
                        <div class="empty-state">
                            <p>No channels have been saved yet.</p>
                        </div>
                    <?php else: ?>
                        <div class="channel-list clean-channel-list">
                            <?php foreach ($channels as $channel): ?>
                                <article class="clean-channel-card">
                                    <div class="channel-card-head">
                                        <?php if (!empty($channel['thumbnail'])): ?>
                                            <img class="channel-thumb" src="<?= e($channel['thumbnail']); ?>" alt="<?= e($channel['title']); ?>">
                                        <?php else: ?>
                                            <span class="header-avatar-fallback"><?= e(strtoupper(substr((string) $channel['title'], 0, 1))); ?></span>
                                        <?php endif; ?>
                                        <div>
                                            <h3><?= e($channel['title']); ?></h3>
                                            <p class="muted"><?= e($channel['channel_id']); ?></p>
                                        </div>
                                    </div>
                                    <?php $description = trim((string) $channel['description']); ?>
                                    <p class="channel-description">
                                        <?= e($description !== '' ? (strlen($description) > 140 ? substr($description, 0, 137) . '...' : $description) : 'No channel description was provided.'); ?>
                                    </p>
                                    <div class="channel-card-foot">
                                        <span class="mini-tag"><?= (int) $channel['video_count']; ?> videos</span>
                                        <a class="button button-secondary" href="<?= e(app_url('pages/channel.php?id=' . urlencode((string) $channel['channel_id']))); ?>">View channel</a>
                                    </div>
                                </article>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </section>
            </div>
        </main>
    </div>
</body>
</html>
