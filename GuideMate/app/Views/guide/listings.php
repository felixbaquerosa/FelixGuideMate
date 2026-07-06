<?php
/** @var array<int,array<string,mixed>> $listings */
?>
<div class="container">
    <div class="dash-layout">
        <?= \App\Core\View::partial('partials/dash-nav') ?>
        <div>
            <div class="section-head" style="margin-bottom:1rem;">
                <div><h1>My listings</h1></div>
                <a href="<?= e(url('/dashboard/listings/create')) ?>" class="btn btn-primary">+ New listing</a>
            </div>

            <div class="panel">
                <div class="panel-body" style="padding:0;overflow-x:auto;">
                    <?php if ($listings === []): ?>
                        <div class="empty-state" style="padding:3rem 1rem;">
                            <div class="big">📋</div>
                            <h3>No listings yet</h3>
                            <p>Create your first listing to start receiving bookings.</p>
                            <a href="<?= e(url('/dashboard/listings/create')) ?>" class="btn btn-primary mt-2">Create listing</a>
                        </div>
                    <?php else: ?>
                        <table class="table">
                            <thead><tr><th>Title</th><th>Category</th><th>Price</th><th>Status</th><th></th></tr></thead>
                            <tbody>
                            <?php foreach ($listings as $l): ?>
                                <tr>
                                    <td><a href="<?= e(url('/listing/' . $l['slug'])) ?>"><strong><?= e($l['title']) ?></strong></a><br><small class="hint"><?= e($l['area']) ?></small></td>
                                    <td><?= e($l['category_name']) ?></td>
                                    <td><?= money($l['price']) ?> <small class="hint"><?= e($l['price_unit']) ?></small></td>
                                    <td><span class="pill pill-<?= e($l['status']) ?>"><?= e(ucfirst($l['status'])) ?></span></td>
                                    <td style="white-space:nowrap;">
                                        <a class="btn btn-ghost btn-sm" href="<?= e(url('/dashboard/listings/' . $l['id'] . '/edit')) ?>">Edit</a>
                                        <a class="btn btn-ghost btn-sm" href="<?= e(url('/dashboard/listings/' . $l['id'] . '/availability')) ?>">Dates</a>
                                        <form method="post" action="<?= e(url('/dashboard/listings/' . $l['id'] . '/delete')) ?>" style="display:inline;" onsubmit="return confirm('Delete this listing? This cannot be undone.');">
                                            <?= csrf_field() ?>
                                            <button class="btn btn-ghost btn-sm" type="submit" style="color:var(--coral-600);">Delete</button>
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
