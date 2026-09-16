<?php
/**
 * @var array<string,mixed> $user
 * @var array<int,array<string,mixed>> $listings
 * @var array<int,array<string,mixed>> $recentBookings
 * @var int $bookingCount @var float $revenue @var int $unread
 * @var array<string,mixed> $guideStats
 */
$approved = array_filter($listings, fn($l) => $l['status'] === 'approved');
$status = (string) ($user['guide_status'] ?? 'none');
$isVerified = $status === 'approved';

$earningsByMonth = $earningsByMonth ?? [];
$curCode = strtoupper((string) ($_SESSION['_currency'] ?? 'USD'));
$curRow = \App\Core\LocaleCatalog::currency($curCode);
$curRate = $curRow ? (float) $curRow['php_per_unit'] : 1.0;
$curSymbol = $curRow['symbol'] ?? '₱';
$earnLabels = array_map(static fn ($r) => date('M', strtotime(((string) $r['month']) . '-01')), $earningsByMonth);
$earnValues = array_map(static fn ($r) => round(((float) $r['total']) / ($curRate ?: 1), 2), $earningsByMonth);
$hasEarnings = array_sum($earnValues) > 0;
$jsonFlags = JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT;
?>
<div class="container">
    <div class="dash-layout">
        <?= \App\Core\View::partial('partials/dash-nav') ?>
        <div>
            <div class="section-head" style="margin-bottom:1rem;">
                <div>
                    <h1 class="mb-0">Welcome back, <?= e(explode(' ', (string) $user['name'])[0]) ?></h1>
                    <p class="hint mb-0">Hotel Partner dashboard</p>
                </div>
                <?php if ($isVerified): ?>
                    <a href="<?= e(url('/dashboard/listings/create')) ?>" class="btn btn-primary">+ New hotel listing</a>
                <?php else: ?>
                    <a href="<?= e(url('/dashboard/verification')) ?>" class="btn btn-ghost"><?= admin_icon('guides', 16) ?>Verification</a>
                <?php endif; ?>
            </div>

            <?php if (!$isVerified): ?>
                <div class="status-banner <?= $status === 'rejected' ? 'banner-red' : 'banner-amber' ?>" style="margin-bottom:1.2rem;">
                    <span class="status-icon"><?= $status === 'rejected' ? admin_icon('disputes', 22) : admin_icon('clock', 22) ?></span>
                    <div>
                        <strong><?= $status === 'rejected' ? 'Application not approved' : 'Verification pending' ?></strong>
                        <p class="mb-0">
                            <?= $status === 'rejected'
                                ? 'Your hotel partner application was not approved. '
                                : 'An admin is reviewing your documents. ' ?>
                            You can publish hotel listings once verified.
                            <a href="<?= e(url('/dashboard/verification')) ?>">View status →</a>
                        </p>
                    </div>
                </div>
            <?php endif; ?>

            <div class="stat-grid">
                <div class="stat stat-ico-card"><span class="stat-ico"><?= admin_icon('listings', 18) ?></span><div class="label">Listings</div><div class="value"><?= count($listings) ?></div></div>
                <div class="stat stat-ico-card"><span class="stat-ico"><?= admin_icon('check-circle', 18) ?></span><div class="label">Live</div><div class="value"><?= count($approved) ?></div></div>
                <div class="stat stat-ico-card"><span class="stat-ico"><?= admin_icon('bookings', 18) ?></span><div class="label">Bookings</div><div class="value"><?= $bookingCount ?></div></div>
                <div class="stat stat-ico-card"><span class="stat-ico"><?= admin_icon('revenue', 18) ?></span><div class="label">Revenue</div><div class="value" style="font-size:1.4rem;"><?= money($revenue) ?></div></div>
            </div>

            <?php if ($isVerified): ?>
            <div class="stat-grid" style="margin-top:1rem;">
                <div class="stat stat-ico-card"><span class="stat-ico"><?= admin_icon('star', 18) ?></span><div class="label">Avg rating</div><div class="value"><?= e((string) ($guideStats['avg_rating'] ?? 0)) ?></div></div>
                <div class="stat stat-ico-card"><span class="stat-ico"><?= admin_icon('feedback', 18) ?></span><div class="label">Reviews</div><div class="value"><?= (int) ($guideStats['review_count'] ?? 0) ?></div></div>
                <div class="stat stat-ico-card"><span class="stat-ico"><?= admin_icon('check-circle', 18) ?></span><div class="label">Completed stays</div><div class="value"><?= (int) ($guideStats['completed'] ?? 0) ?></div></div>
                <div class="stat stat-ico-card"><span class="stat-ico"><?= admin_icon('trend', 18) ?></span><div class="label">Completion rate</div><div class="value"><?= e((string) ($guideStats['conversion'] ?? 0)) ?>%</div></div>
            </div>
            <?php endif; ?>

            <?php if ($hasEarnings): ?>
            <div class="panel" style="margin-top:1.5rem;">
                <div class="panel-head"><h3>Earnings (last 6 months)</h3></div>
                <div class="panel-body"><div class="chart-box"><canvas id="chartEarnings"></canvas></div></div>
            </div>
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
                                    <td><?= e(loc_date((string) $b['booking_date'])) ?></td>
                                    <td><?= money($b['total_amount']) ?></td>
                                    <td><span class="pill pill-<?= e($b['status']) ?>"><?= e(status_label((string) $b['status'])) ?></span></td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>
            </div>

            <div class="panel">
                <div class="panel-head"><h3>Your hotel listings</h3><a href="<?= e(url('/dashboard/listings')) ?>" class="btn btn-ghost btn-sm">View all</a></div>
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
                                    <td><span class="pill pill-<?= e($l['status']) ?>"><?= e(status_label((string) $l['status'])) ?></span></td>
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

<?php if ($hasEarnings): ?>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.9/dist/chart.umd.min.js" defer></script>
<script>
(function () {
    var labels = <?= json_encode($earnLabels, $jsonFlags) ?>;
    var values = <?= json_encode($earnValues, $jsonFlags) ?>;
    var symbol = <?= json_encode($curSymbol, $jsonFlags) ?>;
    function init() {
        if (typeof Chart === 'undefined') { return; }
        var el = document.getElementById('chartEarnings');
        if (!el) { return; }
        new Chart(el, {
            type: 'bar',
            data: { labels: labels, datasets: [{ data: values, backgroundColor: '#1faa4e', borderRadius: 6, maxBarThickness: 40 }] },
            options: {
                responsive: true, maintainAspectRatio: false,
                plugins: { legend: { display: false }, tooltip: { callbacks: { label: function (c) { return symbol + Number(c.parsed.y).toLocaleString(); } } } },
                scales: { x: { grid: { color: 'transparent' } }, y: { beginAtZero: true, ticks: { callback: function (v) { return symbol + Number(v).toLocaleString(); } } } }
            }
        });
    }
    if (document.readyState === 'loading') {
        window.addEventListener('DOMContentLoaded', function () { setTimeout(init, 0); });
    } else { setTimeout(init, 0); }
    window.addEventListener('load', init);
})();
</script>
<?php endif; ?>
