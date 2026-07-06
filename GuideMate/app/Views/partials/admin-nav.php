<?php
$pendingGuides = \App\Models\User::countGuidesByStatus('pending');
$openDisputes = \App\Models\Dispute::countOpen();
$newFeedback = \App\Models\Feedback::countNew();
?>
<nav class="dash-nav">
    <a href="<?= e(url('/admin')) ?>" class="<?= active_when('/admin') ?>">📊 Overview</a>
    <a href="<?= e(url('/admin/listings')) ?>" class="<?= active_when('/admin/listings') ?>">📋 Listings</a>
    <a href="<?= e(url('/admin/guides')) ?>" class="<?= active_when('/admin/guides') ?>"><span class="nav-label">🛡️ Guides</span><?php if ($pendingGuides > 0): ?><span class="badge"><?= (int) $pendingGuides ?></span><?php endif; ?></a>
    <a href="<?= e(url('/admin/disputes')) ?>" class="<?= active_when('/admin/disputes') ?>"><span class="nav-label">⚠️ Disputes</span><?php if ($openDisputes > 0): ?><span class="badge"><?= (int) $openDisputes ?></span><?php endif; ?></a>
    <a href="<?= e(url('/admin/rentals')) ?>" class="<?= active_when('/admin/rentals') ?>">🧾 Rentals</a>
    <a href="<?= e(url('/admin/feedback')) ?>" class="<?= active_when('/admin/feedback') ?>"><span class="nav-label">💬 Feedback</span><?php if ($newFeedback > 0): ?><span class="badge"><?= (int) $newFeedback ?></span><?php endif; ?></a>
    <a href="<?= e(url('/admin/analytics')) ?>" class="<?= active_when('/admin/analytics') ?>">📈 Analytics</a>
    <a href="<?= e(url('/admin/users')) ?>" class="<?= active_when('/admin/users') ?>">👥 Users</a>
    <a href="<?= e(url('/admin/security')) ?>" class="<?= active_when('/admin/security') ?>">🔐 Security</a>
</nav>
