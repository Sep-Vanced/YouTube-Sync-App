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
        <section class="auth-hero reveal-up">
            <div class="auth-copy">
                <span class="eyebrow badge-soft">Creator Workflow</span>
                <h1>Bring your YouTube channels into one clean workspace.</h1>
                <p class="lead">
                    Sign in securely with Google, sync channel uploads in seconds, and review your saved content in a fast dashboard designed like a modern mobile app.
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
            </div>

            <section class="auth-card auth-card-modern reveal-up delay-1">
                <div class="auth-card-top">
                    <span class="brand-orb"></span>
                    <p class="eyebrow">YouTube Sync App</p>
                    <h2>Welcome back</h2>
                    <p class="muted auth-subcopy">
                        <?= $demoMode
                            ? 'Start the bundled demo workspace and test the application immediately in your local environment.'
                            : 'Continue with your Google account to manage saved channels and uploaded videos.'; ?>
                    </p>
                </div>

                <?php if ($flash): ?>
                    <div class="alert alert-<?= e($flash['type']); ?>">
                        <?= e($flash['message']); ?>
                    </div>
                <?php endif; ?>

                <?php if ($demoMode): ?>
                    <a class="button auth-button auth-button-google" href="<?= e(app_url('auth/demo_login.php')); ?>">
                        <span>Enter Demo Workspace</span>
                    </a>
                    <?php if ($googleConfigured): ?>
                        <a class="button button-secondary auth-button" href="<?= e(app_url('auth/login.php')); ?>">
                            <span>Continue with Google</span>
                        </a>
                    <?php endif; ?>
                <?php else: ?>
                    <a class="button auth-button auth-button-google" href="<?= e(app_url('auth/login.php')); ?>">
                        <span class="button-glow"></span>
                        <span>Continue with Google</span>
                    </a>
                <?php endif; ?>

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
                        <strong>24/7</strong>
                        <span>local access</span>
                    </div>
                </div>

                <div class="auth-note">
                    <?php if ($demoMode): ?>
                        <p>Evaluator-ready demo mode is enabled. Try syncing <code>UCDEMOCHANNEL000000000001</code> or <code>UCDEMOCHANNEL000000000002</code>.</p>
                    <?php else: ?>
                        <p>Use your Google account to sign in and start syncing public YouTube channels.</p>
                    <?php endif; ?>
                </div>
            </section>
        </section>
    </main>
</body>
</html>
