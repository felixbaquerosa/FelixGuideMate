<?php
/** @var array<int,array<string,mixed>> $bookings @var bool $isWarned @var string $warningNote */
$isWarned = $isWarned ?? false;
$warningNote = $warningNote ?? '';
?>
<div class="container">
    <div class="dash-layout">
        <?= \App\Core\View::partial('partials/dash-nav') ?>
        <div>
            <h1>Bookings received</h1>

            <?php if ($isWarned): ?>
                <div class="status-banner banner-red" style="margin-bottom:1.2rem;">
                    <span class="status-icon">⚠️</span>
                    <div>
                        <strong>Administrator warning — actions restricted</strong>
                        <p class="mb-0">
                            You received a warning from the Administrator<?= $warningNote !== '' ? ': ' . e($warningNote) : '.' ?>
                            You cannot use booking actions (Confirm, Complete, or Cancel) until an admin clears this warning.
                            <a href="<?= e(url('/messages')) ?>">View message →</a>
                        </p>
                    </div>
                </div>
            <?php endif; ?>

            <div class="panel">
                <div class="panel-body" style="padding:0;overflow-x:auto;">
                    <?php if ($bookings === []): ?>
                        <div class="empty-state" style="padding:3rem 1rem;"><div class="big">📅</div><p>No bookings yet.</p></div>
                    <?php else: ?>
                        <table class="table">
                            <thead><tr><th>Customer</th><th>Listing</th><th>Date</th><th>Guests</th><th>Total</th><th>Payment</th><th>Status</th><th>Action</th></tr></thead>
                            <tbody>
                            <?php foreach ($bookings as $b): ?>
                                <tr>
                                    <td><?= e($b['customer_name']) ?></td>
                                    <td><a href="<?= e(url('/listing/' . $b['listing_slug'])) ?>"><?= e($b['listing_title']) ?></a></td>
                                    <td><?= e(date('M j, Y', strtotime($b['booking_date']))) ?></td>
                                    <td><?= (int) $b['guests'] ?></td>
                                    <td><?= money($b['total_amount']) ?></td>
                                    <td><span class="pill pill-<?= e($b['payment_status'] ?? 'pending') ?>"><?= e(ucfirst($b['payment_status'] ?? 'unpaid')) ?></span></td>
                                    <td><span class="pill pill-<?= e($b['status']) ?>"><?= e(ucfirst($b['status'])) ?></span></td>
                                    <td style="white-space:nowrap;">
                                        <?php if ($isWarned): ?>
                                            <span class="hint">Restricted</span>
                                        <?php elseif (!in_array($b['status'], ['completed', 'cancelled', 'refunded', 'disputed'], true)): ?>
                                            <form method="post" action="<?= e(url('/dashboard/bookings/' . $b['id'] . '/status')) ?>" style="display:inline;" class="guide-booking-action" data-paid="<?= ($b['payment_status'] ?? '') === 'paid' ? '1' : '0' ?>" data-amount="<?= e((string) $b['total_amount']) ?>">
                                                <?= csrf_field() ?>
                                                <select name="status" class="input guide-status-select" style="padding:.3rem .5rem;width:auto;display:inline-block;">
                                                    <?php if ($b['status'] === 'pending'): ?><option value="confirmed">Confirm</option><?php endif; ?>
                                                    <option value="completed">Complete</option>
                                                    <option value="cancelled">Cancel</option>
                                                </select>
                                                <button class="btn btn-ghost btn-sm" type="submit">Go</button>
                                            </form>
                                        <?php else: ?>—<?php endif; ?>
                                    </td>
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
