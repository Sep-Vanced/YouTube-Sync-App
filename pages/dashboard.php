<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/db.php';

require_login();

$flash = get_flash();
$user = current_user();

$channelsStatement = db()->query(
    'SELECT c.channel_id, c.title, c.description, c.thumbnail, c.created_at, COUNT(v.id) AS video_count
     FROM channels c
     LEFT JOIN videos v ON v.channel_id = c.channel_id
     GROUP BY c.channel_id, c.title, c.description, c.thumbnail, c.created_at
     ORDER BY c.created_at DESC, c.title ASC'
);
$channels = $channelsStatement->fetchAll();
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
    <div class="app-shell">
        <header class="topbar">
            <div>
                <p class="eyebrow">YouTube Channel Sync</p>
                <h1>Dashboard</h1>
                <p class="muted">Save channels and browse their latest synced videos.</p>
            </div>
            <div class="user-panel">
                <?php if (!empty($user['profile_picture'])): ?>
                    <img class="avatar" src="<?= e($user['profile_picture']); ?>" alt="<?= e($user['name']); ?>">
                <?php endif; ?>
                <div>
                    <strong><?= e($user['name'] ?? ''); ?></strong>
                    <p class="muted small"><?= e($user['email'] ?? ''); ?></p>
                </div>
                <a class="button button-secondary" href="<?= e(app_url('auth/logout.php')); ?>">Logout</a>
            </div>
        </header>

        <?php if ($flash): ?>
            <div class="alert alert-<?= e($flash['type']); ?>">
                <?= e($flash['message']); ?>
            </div>
        <?php endif; ?>

        <main class="grid-layout">
            <section class="card">
                <div class="section-heading">
                    <h2>Add a channel</h2>
                    <p class="muted">Paste a YouTube Channel ID to fetch channel details and up to 100 videos.</p>
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

                    <button class="button" type="submit">Sync Channel</button>
                </form>
            </section>

            <section class="card">
                <div class="section-heading">
                    <h2>Saved channels</h2>
                    <p class="muted"><?= count($channels); ?> channel(s) available.</p>
                </div>

                <?php if (empty($channels)): ?>
                    <div class="empty-state">
                        <p>No channels have been saved yet.</p>
                    </div>
                <?php else: ?>
                    <div class="channel-list">
                        <?php foreach ($channels as $channel): ?>
                            <article class="channel-card">
                                <div class="channel-card-head">
                                    <?php if (!empty($channel['thumbnail'])): ?>
                                        <img class="channel-thumb" src="<?= e($channel['thumbnail']); ?>" alt="<?= e($channel['title']); ?>">
                                    <?php endif; ?>
                                    <div>
                                        <h3><?= e($channel['title']); ?></h3>
                                        <p class="muted small"><?= e($channel['channel_id']); ?></p>
                                    </div>
                                </div>
                                <?php $description = (string) $channel['description']; ?>
                                <p class="channel-description"><?= e(strlen($description) > 140 ? substr($description, 0, 137) . '...' : $description); ?></p>
                                <div class="channel-card-foot">
                                    <span class="badge"><?= (int) $channel['video_count']; ?> videos</span>
                                    <a class="text-link" href="<?= e(app_url('pages/channel.php?id=' . urlencode((string) $channel['channel_id']))); ?>">View channel</a>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </section>
        </main>
    </div>
</body>
</html>
