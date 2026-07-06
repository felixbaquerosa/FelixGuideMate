<?php
/** @var array<int,array<string,mixed>> $requests */
?>
<h1>Vehicle rental requests</h1>

<?php if ($requests === []): ?>
    <div class="panel">
        <div class="panel-body">
            <div class="empty-state" style="padding:2rem;">
                <p>No rental requests yet. Requests from the mobile app will appear here.</p>
            </div>
        </div>
    </div>
<?php else: ?>
    <div class="panel">
        <div class="panel-body" style="padding:0;overflow-x:auto;">
            <table class="table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Vehicle</th>
                        <th>Customer</th>
                        <th>Pickup</th>
                        <th>Days</th>
                        <th>Total</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($requests as $r): ?>
                    <tr>
                        <td><?= (int) $r['id'] ?></td>
                        <td>
                            <strong><?= e($r['vehicle_name']) ?></strong><br>
                            <small class="hint"><?= e($r['vehicle_type']) ?> · <?= e($r['shop_name']) ?></small>
                        </td>
                        <td>
                            <?= e($r['customer_name']) ?><br>
                            <small class="hint"><?= e($r['customer_email']) ?></small><br>
                            <small class="hint"><?= e((string) ($r['customer_phone'] ?? '')) ?></small>
                        </td>
                        <td><?= e(date('M j, Y', strtotime((string) $r['pickup_date']))) ?></td>
                        <td><?= (int) $r['rental_days'] ?></td>
                        <td><?= money((float) $r['total_amount']) ?></td>
                        <td><span class="pill pill-<?= e($r['status']) ?>"><?= e(\App\Models\RentalRequest::statusLabel((string) $r['status'])) ?></span></td>
                        <td>
                            <?php if (!empty($r['notes'])): ?>
                                <p class="hint" style="max-width:14rem;"><?= e($r['notes']) ?></p>
                            <?php endif; ?>
                            <form method="post" action="<?= e(url('/admin/rentals/' . $r['id'] . '/status')) ?>" class="dispute-btns" style="margin-top:.5rem;">
                                <?= csrf_field() ?>
                                <input class="input" type="text" name="admin_note" placeholder="Admin note" value="<?= e((string) ($r['admin_note'] ?? '')) ?>">
                                <button class="btn btn-primary btn-sm" type="submit" name="status" value="contacted">Mark contacted</button>
                                <button class="btn btn-ghost btn-sm" type="submit" name="status" value="approved">Approve</button>
                                <button class="btn btn-ghost btn-sm" type="submit" name="status" value="completed">Complete</button>
                                <button class="btn btn-ghost btn-sm" type="submit" name="status" value="cancelled">Cancel</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
<?php endif; ?>
