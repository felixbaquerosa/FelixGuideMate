<?php
/** @var array<int,array<string,mixed>> $bookingsByMonth @var array<int,array<string,mixed>> $topListings
 * @var array<int,array<string,mixed>> $usersByMonth @var array{bookings:int,disputes:int,rate:float} $disputeStats
 * @var float $revenue @var array<int,array<string,mixed>> $auditLog */
?>
<h1>Analytics</h1>

<div class="stat-grid" style="margin-bottom:1.5rem;">
    <div class="stat"><div class="label">Platform revenue</div><div class="value"><?= money($revenue) ?></div></div>
    <div class="stat"><div class="label">Dispute rate</div><div class="value"><?= e((string) $disputeStats['rate']) ?>%</div></div>
    <div class="stat"><div class="label">Total disputes</div><div class="value"><?= (int) $disputeStats['disputes'] ?></div></div>
    <div class="stat"><div class="label">Paid bookings base</div><div class="value"><?= (int) $disputeStats['bookings'] ?></div></div>
</div>

<div class="panel" style="margin-bottom:1rem;">
    <div class="panel-head"><h3>Bookings per month</h3></div>
    <div class="panel-body">
        <?php if ($bookingsByMonth === []): ?>
            <p class="hint mb-0">No booking data yet.</p>
        <?php else: ?>
            <table class="table">
                <thead><tr><th>Month</th><th>Bookings</th></tr></thead>
                <tbody>
                <?php foreach ($bookingsByMonth as $row): ?>
                    <tr><td><?= e($row['month']) ?></td><td><?= (int) $row['total'] ?></td></tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</div>

<div class="panel" style="margin-bottom:1rem;">
    <div class="panel-head"><h3>New users per month</h3></div>
    <div class="panel-body">
        <?php if ($usersByMonth === []): ?>
            <p class="hint mb-0">No user registrations yet.</p>
        <?php else: ?>
            <table class="table">
                <thead><tr><th>Month</th><th>Users</th></tr></thead>
                <tbody>
                <?php foreach ($usersByMonth as $row): ?>
                    <tr><td><?= e($row['month']) ?></td><td><?= (int) $row['total'] ?></td></tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</div>

<div class="panel" style="margin-bottom:1rem;">
    <div class="panel-head"><h3>Top listings</h3></div>
    <div class="panel-body" style="padding:0;overflow-x:auto;">
        <table class="table">
            <thead><tr><th>Listing</th><th>Area</th><th>Bookings</th><th>Revenue</th></tr></thead>
            <tbody>
            <?php foreach ($topListings as $l): ?>
                <tr>
                    <td><a href="<?= e(url('/listing/' . $l['slug'])) ?>"><?= e($l['title']) ?></a></td>
                    <td><?= e($l['area']) ?></td>
                    <td><?= (int) $l['booking_count'] ?></td>
                    <td><?= money((float) $l['revenue']) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="panel">
    <div class="panel-head"><h3>Recent admin actions</h3></div>
    <div class="panel-body" style="padding:0;overflow-x:auto;">
        <table class="table">
            <thead><tr><th>When</th><th>Admin</th><th>Action</th><th>Entity</th></tr></thead>
            <tbody>
            <?php foreach ($auditLog as $a): ?>
                <tr>
                    <td><?= e(date('M j, Y g:i a', strtotime($a['created_at']))) ?></td>
                    <td><?= e($a['admin_name']) ?></td>
                    <td><?= e($a['action']) ?></td>
                    <td><?= e($a['entity_type']) ?> #<?= (int) ($a['entity_id'] ?? 0) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<p class="hint mt-2">
    <a href="<?= e(url('/admin/export/bookings')) ?>">Export bookings CSV</a> ·
    <a href="<?= e(url('/admin/export/users')) ?>">Export users CSV</a> ·
    <a href="<?= e(url('/admin/export/disputes')) ?>">Export disputes CSV</a>
</p>
