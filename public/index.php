<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';

if (is_logged_in()) {
    redirect('pages/dashboard.php');
}

$flash = get_flash();
$demoMode = demo_mode_enabled();
$googleConfigured = google_oauth_configured();
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
    <main class="auth-shell auth-shell-enhanced">
        <section class="auth-hero">
            <div class="auth-copy reveal-up">
                <p class="eyebrow">Plain PHP + MySQL</p>
                <h1>YouTube Channel Sync</h1>
                <p class="lead">
                    <?= $demoMode
                        ? 'Explore the local demo workspace, review saved channels, and test the full flow without external services.'
                        : 'Sign in with Google, save YouTube channels, and browse up to 100 synced videos per channel.'; ?>
                </p>

                <div class="auth-feature-grid">
                    <article class="feature-chip">
                        <strong>Fast sync</strong>
                        <span>Save channel details and up to 100 uploaded videos.</span>
                    </article>
                    <article class="feature-chip">
                        <strong>Clean overview</strong>
                        <span>Browse channels, previews, and paginated videos in one place.</span>
                    </article>
                    <article class="feature-chip">
                        <strong>Secure login</strong>
                        <span>Google OAuth with protected sessions and server-side config.</span>
                    </article>
                </div>

                <div class="auth-mini-stats">
                    <div>
                        <strong>100</strong>
                        <span>videos per sync</span>
                    </div>
                    <div>
                        <strong>20</strong>
                        <span>videos per page</span>
                    </div>
                    <div>
                        <strong><?= $demoMode ? 'Demo' : 'OAuth'; ?></strong>
                        <span><?= $demoMode ? 'local ready mode' : 'Google sign-in'; ?></span>
                    </div>
                </div>
            </div>

            <section class="auth-card reveal-up delay-1">
                <p class="eyebrow">Start Here</p>
                <h2><?= $demoMode ? 'Enter the demo workspace' : 'Continue with Google'; ?></h2>
                <p class="lead">
                    <?= $demoMode
                        ? 'Demo mode is enabled, so you can open the dashboard immediately and test the local seeded workflow.'
                        : 'Access the dashboard, sync a channel ID, and open each saved library page with clean pagination.'; ?>
                </p>

                <?php if ($flash): ?>
                    <div class="alert alert-<?= e($flash['type']); ?>">
                        <?= e($flash['message']); ?>
                    </div>
                <?php endif; ?>

                <?php if ($demoMode): ?>
                    <a class="button auth-button" href="<?= e(app_url('auth/demo_login.php')); ?>">Enter Demo Workspace</a>
                    <?php if ($googleConfigured): ?>
                        <a class="button button-secondary auth-button" href="<?= e(app_url('auth/login.php')); ?>">Continue with Google</a>
                    <?php endif; ?>
                <?php else: ?>
                    <a class="button auth-button" href="<?= e(app_url('auth/login.php')); ?>">Continue with Google</a>
                <?php endif; ?>

                <div class="auth-note">
                    <?php if ($demoMode): ?>
                        <p>Demo mode is enabled through <code>config/config.local.php</code>. The demo login can seed sample channels for local evaluation.</p>
                    <?php else: ?>
                        <p>Before logging in, update <code>config/config.local.php</code> or <code>config/config.php</code> with your Google OAuth and YouTube API credentials.</p>
                    <?php endif; ?>
                </div>
            </section>
        </section>
    </main>
</body>
</html>
