<?php
/**
 * @var array<string,mixed> $user
 * @var array<int,array<string,mixed>> $listings
 * @var array<int,array<string,mixed>> $recentBookings
 * @var int $bookingCount @var float $revenue @var int $unread
 * @var bool $isWarned
 */
$approved = array_filter($listings, fn($l) => $l['status'] === 'approved');
$guideStatus = (string) ($user['guide_status'] ?? 'none');
$isVerified = $guideStatus === 'approved';
$isWarned = $isWarned ?? \App\Models\User::isGuideWarned((int) ($user['id'] ?? 0));
$warningNote = (string) ($user['guide_warning_note'] ?? '');
?>
<div class="container">
    <div class="dash-layout">
        <?= \App\Core\View::partial('partials/dash-nav') ?>
        <div>
            <div class="section-head" style="margin-bottom:1rem;">
                <div>
                    <h1 class="mb-0">Welcome back, <?= e(explode(' ', $user['name'])[0]) ?></h1>
                </div>
                <?php if ($isVerified): ?>
                    <a href="<?= e(url('/dashboard/listings/create')) ?>" class="btn btn-primary">+ New listing</a>
                <?php else: ?>
                    <a href="<?= e(url('/dashboard/verification')) ?>" class="btn btn-ghost">🛡️ Verification</a>
                <?php endif; ?>
            </div>

            <?php if ($isWarned): ?>
                <div class="status-banner banner-red" style="margin-bottom:1.2rem;">
                    <span class="status-icon">⚠️</span>
                    <div>
                        <strong>Administrator warning</strong>
                        <p class="mb-0">
                            <?= $warningNote !== '' ? e($warningNote) . ' ' : '' ?>
                            Booking actions are restricted until an admin clears this warning.
                            <a href="<?= e(url('/messages')) ?>">Read administrator message →</a>
                        </p>
                    </div>
                </div>
            <?php elseif (!$isVerified): ?>
                <div class="status-banner <?= $guideStatus === 'rejected' ? 'banner-red' : 'banner-amber' ?>" style="margin-bottom:1.2rem;">
                    <span class="status-icon"><?= $guideStatus === 'rejected' ? '⚠️' : '⏳' ?></span>
                    <div>
                        <strong><?= $guideStatus === 'rejected' ? 'Application not approved' : 'Verification pending' ?></strong>
                        <p class="mb-0">
                            <?= $guideStatus === 'rejected'
                                ? 'Your application was not approved. '
                                : 'An admin is reviewing your documents. ' ?>
                            You can publish listings once verified.
                            <a href="<?= e(url('/dashboard/verification')) ?>">View status →</a>
                        </p>
                    </div>
                </div>
            <?php endif; ?>

            <div class="stat-grid">
                <div class="stat"><div class="label">Listings</div><div class="value"><?= count($listings) ?></div></div>
                <div class="stat"><div class="label">Live</div><div class="value"><?= count($approved) ?></div></div>
                <div class="stat"><div class="label">Bookings</div><div class="value"><?= $bookingCount ?></div></div>
                <div class="stat"><div class="label">Revenue</div><div class="value" style="font-size:1.4rem;"><?= money($revenue) ?></div></div>
            </div>

            <?php if ($isVerified): ?>
            <div class="stat-grid" style="margin-top:1rem;">
                <div class="stat"><div class="label">Avg rating</div><div class="value"><?= e((string) ($guideStats['avg_rating'] ?? 0)) ?></div></div>
                <div class="stat"><div class="label">Reviews</div><div class="value"><?= (int) ($guideStats['review_count'] ?? 0) ?></div></div>
                <div class="stat"><div class="label">Completed tours</div><div class="value"><?= (int) ($guideStats['completed'] ?? 0) ?></div></div>
                <div class="stat"><div class="label">Completion rate</div><div class="value"><?= e((string) ($guideStats['conversion'] ?? 0)) ?>%</div></div>
            </div>
            <?php if (!empty($guideBadge)): ?>
                <p class="hint mt-1">Your badge: <strong><?= e($guideBadge) ?></strong></p>
            <?php endif; ?>
            <?php endif; ?>

            <div class="panel">
                <div class="panel-head"><h3>Recent bookings</h3><a href="<?= e(url('/dashboard/bookings')) ?>" class="btn btn-ghost btn-sm">Manage</a></div>
                <div class="panel-body" style="padding:0;overflow-x:auto;">
                    <?php if ($recentBookings === []): ?>
                        <div class="empty-state" style="padding:2.5rem 1rem;"><p>No bookings yet.</p></div>
                    <?php else: ?>
                        <table class="table">
                            <thead><tr><th>Customer</th><th>Listing</th><th>Date</th><th>Total</th><th>Status</th></tr></thead>
                            <tbody>
                            <?php foreach ($recentBookings as $b): ?>
                                <tr>
                                    <td><?= e($b['customer_name']) ?></td>
                                    <td><?= e($b['listing_title']) ?></td>
                                    <td><?= e(date('M j, Y', strtotime($b['booking_date']))) ?></td>
                                    <td><?= money($b['total_amount']) ?></td>
                                    <td><span class="pill pill-<?= e($b['status']) ?>"><?= e(ucfirst($b['status'])) ?></span></td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>
            </div>

            <div class="panel">
                <div class="panel-head"><h3>Your listings</h3><a href="<?= e(url('/dashboard/listings')) ?>" class="btn btn-ghost btn-sm">View all</a></div>
                <div class="panel-body" style="padding:0;overflow-x:auto;">
                    <?php if ($listings === []): ?>
                        <div class="empty-state" style="padding:2.5rem 1rem;"><p>You haven't created any listings yet. <a href="<?= e(url('/dashboard/listings/create')) ?>">Create your first →</a></p></div>
                    <?php else: ?>
                        <table class="table">
                            <thead><tr><th>Title</th><th>Category</th><th>Price</th><th>Rating</th><th>Status</th></tr></thead>
                            <tbody>
                            <?php foreach ($listings as $l): ?>
                                <tr>
                                    <td><a href="<?= e(url('/listing/' . $l['slug'])) ?>"><?= e($l['title']) ?></a></td>
                                    <td><?= e($l['category_name']) ?></td>
                                    <td><?= money($l['price']) ?></td>
                                    <td><?= (int) $l['review_count'] > 0 ? number_format((float) $l['avg_rating'], 1) . ' ★' : '—' ?></td>
                                    <td><span class="pill pill-<?= e($l['status']) ?>"><?= e(ucfirst($l['status'])) ?></span></td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>
