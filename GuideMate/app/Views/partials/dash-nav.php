<?php
/** @var array<string,mixed> $user */
$user = auth_user();
$role = $user['role'] ?? 'tourist';
?>
<nav class="dash-nav">
    <a href="<?= e(url('/dashboard')) ?>" class="<?= active_when('/dashboard') ?>"><?= admin_icon('home') ?><span class="nav-label"><?= e(__('d_overview', 'Overview')) ?></span></a>
    <?php if ($role === 'guide'): ?>
        <a href="<?= e(url('/dashboard/listings')) ?>" class="<?= active_when('/dashboard/listings') ?>"><?= admin_icon('listings') ?><span class="nav-label"><?= e(__('d_my_listings', 'My Listings')) ?></span></a>
        <a href="<?= e(url('/dashboard/bookings')) ?>" class="<?= active_when('/dashboard/bookings') ?>"><?= admin_icon('bookings') ?><span class="nav-label"><?= e(__('d_bookings', 'Bookings')) ?></span></a>
        <a href="<?= e(url('/dashboard/bookings/verify')) ?>" class="<?= active_when('/dashboard/bookings/verify') ?>"><?= admin_icon('verifyqr') ?><span class="nav-label"><?= e(__('d_verify_qr', 'Verify QR')) ?></span></a>
        <?php $gs = (string) ($user['guide_status'] ?? 'none'); ?>
        <a href="<?= e(url('/dashboard/verification')) ?>" class="<?= active_when('/dashboard/verification') ?>">
            <?= admin_icon('guides') ?><span class="nav-label"><?= e(__('d_verification', 'Verification')) ?></span>
            <?php if ($gs === 'pending'): ?><span class="badge badge-amber"><?= e(__('d_pending', 'Pending')) ?></span>
            <?php elseif ($gs === 'rejected'): ?><span class="badge badge-red"><?= e(__('d_action', 'Action')) ?></span>
            <?php elseif ($gs === 'approved'): ?><span class="badge badge-green">✓</span><?php endif; ?>
        </a>
    <?php endif; ?>
    <?php if ($role === 'hotel_admin'): ?>
        <a href="<?= e(url('/dashboard/listings')) ?>" class="<?= active_when('/dashboard/listings') ?>"><?= admin_icon('listings') ?><span class="nav-label">My Hotels</span></a>
        <a href="<?= e(url('/dashboard/bookings')) ?>" class="<?= active_when('/dashboard/bookings') ?>"><?= admin_icon('bookings') ?><span class="nav-label">Bookings</span></a>
        <a href="<?= e(url('/dashboard/bookings/verify')) ?>" class="<?= active_when('/dashboard/bookings/verify') ?>"><?= admin_icon('verifyqr') ?><span class="nav-label">Verify QR</span></a>
        <?php $hs = (string) ($user['guide_status'] ?? 'none'); ?>
        <a href="<?= e(url('/dashboard/verification')) ?>" class="<?= active_when('/dashboard/verification') ?>">
            <?= admin_icon('guides') ?><span class="nav-label">Verification</span>
            <?php if ($hs === 'pending'): ?><span class="badge badge-amber">Pending</span>
            <?php elseif ($hs === 'rejected'): ?><span class="badge badge-red">Action</span>
            <?php elseif ($hs === 'approved'): ?><span class="badge badge-green">✓</span><?php endif; ?>
        </a>
    <?php endif; ?>
    <?php if ($role === 'rental_admin'): ?>
        <a href="<?= e(url('/dashboard/rentals')) ?>" class="<?= active_when('/dashboard/rentals') ?>"><?= admin_icon('rentals') ?><span class="nav-label">Rental Requests</span></a>
        <?php $rs = (string) ($user['guide_status'] ?? 'none'); ?>
        <a href="<?= e(url('/dashboard/verification')) ?>" class="<?= active_when('/dashboard/verification') ?>">
            <?= admin_icon('guides') ?><span class="nav-label">Verification</span>
            <?php if ($rs === 'pending'): ?><span class="badge badge-amber">Pending</span>
            <?php elseif ($rs === 'rejected'): ?><span class="badge badge-red">Action</span>
            <?php elseif ($rs === 'approved'): ?><span class="badge badge-green">✓</span><?php endif; ?>
        </a>
    <?php endif; ?>
    <?php if ($role === 'admin'): ?>
        <a href="<?= e(url('/admin/listings')) ?>" class="<?= active_when('/admin/listings') ?>"><?= admin_icon('listings') ?><span class="nav-label"><?= e(__('d_listings', 'Listings')) ?></span></a>
        <a href="<?= e(url('/admin/users')) ?>" class="<?= active_when('/admin/users') ?>"><?= admin_icon('users') ?><span class="nav-label"><?= e(__('d_users', 'Users')) ?></span></a>
    <?php endif; ?>
    <?php if ($role === 'tourist'): ?>
        <a href="<?= e(url('/bookings')) ?>" class="<?= active_when('/bookings') ?>"><?= admin_icon('trips') ?><span class="nav-label"><?= e(__('d_my_bookings', 'My Bookings')) ?></span></a>
        <a href="<?= e(url('/bookings/map')) ?>" class="<?= active_when('/bookings/map') ?>"><?= admin_icon('map') ?><span class="nav-label"><?= e(__('d_trip_map', 'Trip Map')) ?></span></a>
        <a href="<?= e(url('/favorites')) ?>" class="<?= active_when('/favorites') ?>"><?= admin_icon('saved') ?><span class="nav-label"><?= e(__('d_saved', 'Saved')) ?></span></a>
    <?php endif; ?>
    <a href="<?= e(url('/messages')) ?>" class="<?= active_when('/messages') ?>"><?= admin_icon('feedback') ?><span class="nav-label"><?= e(__('d_messages', 'Messages')) ?></span></a>
    <a href="<?= e(url('/profile')) ?>" class="<?= active_when('/profile') ?>"><?= admin_icon('profile') ?><span class="nav-label"><?= e(__('d_profile', 'Profile')) ?></span></a>
</nav>
