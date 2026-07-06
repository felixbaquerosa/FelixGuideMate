<?php
/** @var array<string,mixed> $listing @var array<int,array<string,mixed>> $blocked */
?>
<div class="container">
    <div class="dash-layout">
        <?= \App\Core\View::partial('partials/dash-nav') ?>
        <div>
            <h1>Availability</h1>
            <p class="hint"><?= e($listing['title']) ?> — block dates when you are unavailable.</p>

            <div class="panel">
                <div class="panel-head"><h3>Block a date</h3></div>
                <div class="panel-body">
                    <form method="post" action="<?= e(url('/dashboard/listings/' . $listing['id'] . '/availability/block')) ?>" class="form-grid-2">
                        <?= csrf_field() ?>
                        <div class="field-row">
                            <label for="blocked_date">Date</label>
                            <input class="input" type="date" id="blocked_date" name="blocked_date" required>
                        </div>
                        <div class="field-row">
                            <label for="note">Note (optional)</label>
                            <input class="input" type="text" id="note" name="note" placeholder="e.g. Personal leave">
                        </div>
                        <div class="field-row" style="grid-column:1/-1;">
                            <button class="btn btn-primary" type="submit">Block date</button>
                            <a class="btn btn-ghost" href="<?= e(url('/dashboard/listings')) ?>">Back to listings</a>
                        </div>
                    </form>
                </div>
            </div>

            <div class="panel">
                <div class="panel-head"><h3>Blocked dates</h3></div>
                <div class="panel-body" style="padding:0;">
                    <?php if ($blocked === []): ?>
                        <p class="hint" style="padding:1.3rem;">No blocked dates.</p>
                    <?php else: ?>
                        <table class="table">
                            <thead><tr><th>Date</th><th>Note</th><th></th></tr></thead>
                            <tbody>
                            <?php foreach ($blocked as $b): ?>
                                <tr>
                                    <td><?= e(date('M j, Y', strtotime($b['blocked_date']))) ?></td>
                                    <td><?= e($b['note'] ?? '—') ?></td>
                                    <td>
                                        <form method="post" action="<?= e(url('/dashboard/listings/' . $listing['id'] . '/availability/unblock')) ?>">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="blocked_date" value="<?= e($b['blocked_date']) ?>">
                                            <button class="btn btn-ghost btn-sm" type="submit">Remove</button>
                                        </form>
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
