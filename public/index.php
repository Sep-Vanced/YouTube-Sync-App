<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';

if (is_logged_in()) {
    redirect('pages/dashboard.php');
}

$flash = get_flash();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>YouTube Channel Sync</title>
    <link rel="stylesheet" href="<?= e(app_url('public/assets/styles.css')); ?>">
</head>
<body>
    <main class="auth-shell">
        <section class="auth-card">
            <p class="eyebrow">Plain PHP + MySQL</p>
            <h1>YouTube Channel Sync</h1>
            <p class="lead">
                Sign in with Google, save YouTube channels, and browse up to 100 synced videos per channel.
            </p>

            <?php if ($flash): ?>
                <div class="alert alert-<?= e($flash['type']); ?>">
                    <?= e($flash['message']); ?>
                </div>
            <?php endif; ?>

            <a class="button auth-button" href="<?= e(app_url('auth/login.php')); ?>">Continue with Google</a>

            <div class="auth-note">
                <p>Before logging in, update <code>config/config.php</code> with your Google OAuth and YouTube API credentials.</p>
            </div>
        </section>
    </main>
</body>
</html>
