<?php
/** @var array<string,mixed> $user */
$user = auth_user();
$role = $user['role'] ?? 'tourist';
?>
<nav class="dash-nav">
    <a href="<?= e(url('/dashboard')) ?>" class="<?= active_when('/dashboard') ?>">🏠 Overview</a>
    <?php if ($role === 'guide'): ?>
        <a href="<?= e(url('/dashboard/listings')) ?>" class="<?= active_when('/dashboard/listings') ?>">📋 My Listings</a>
        <a href="<?= e(url('/dashboard/bookings')) ?>" class="<?= active_when('/dashboard/bookings') ?>">📅 Bookings</a>
        <a href="<?= e(url('/dashboard/bookings/verify')) ?>" class="<?= active_when('/dashboard/bookings/verify') ?>">🔳 Verify QR</a>
        <?php $gs = (string) ($user['guide_status'] ?? 'none'); ?>
        <a href="<?= e(url('/dashboard/verification')) ?>" class="<?= active_when('/dashboard/verification') ?>">
            <span class="nav-label">🛡️ Verification</span>
            <?php if ($gs === 'pending'): ?><span class="badge badge-amber">Pending</span>
            <?php elseif ($gs === 'rejected'): ?><span class="badge badge-red">Action</span>
            <?php elseif ($gs === 'approved'): ?><span class="badge badge-green">✓</span><?php endif; ?>
        </a>
    <?php endif; ?>
    <?php if ($role === 'admin'): ?>
        <a href="<?= e(url('/admin/listings')) ?>" class="<?= active_when('/admin/listings') ?>">📋 Listings</a>
        <a href="<?= e(url('/admin/users')) ?>" class="<?= active_when('/admin/users') ?>">👥 Users</a>
    <?php endif; ?>
    <?php if ($role === 'tourist'): ?>
        <a href="<?= e(url('/bookings')) ?>" class="<?= active_when('/bookings') ?>">🧳 My Bookings</a>
        <a href="<?= e(url('/bookings/map')) ?>" class="<?= active_when('/bookings/map') ?>">🗺️ Trip Map</a>
        <a href="<?= e(url('/favorites')) ?>" class="<?= active_when('/favorites') ?>">♡ Saved</a>
    <?php endif; ?>
    <a href="<?= e(url('/messages')) ?>" class="<?= active_when('/messages') ?>">💬 Messages</a>
    <a href="<?= e(url('/profile')) ?>" class="<?= active_when('/profile') ?>">⚙️ Profile</a>
</nav>
