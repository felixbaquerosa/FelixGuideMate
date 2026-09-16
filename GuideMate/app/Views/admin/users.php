<?php
/** @var array<int,array<string,mixed>> $users */
?>
<h1>Manage users</h1>

<div class="panel">
    <div class="panel-body" style="padding:0;overflow-x:auto;">
        <table class="table table-users">
            <thead>
                <tr>
                    <th class="col-name">Name</th>
                    <th class="col-email">Email</th>
                    <th class="col-role">Role</th>
                    <th class="col-joined">Joined</th>
                    <th class="col-status">Status</th>
                    <th class="col-actions"></th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($users as $u): ?>
                <tr>
                    <td class="col-name">
                        <div class="table-user-cell">
                            <img src="<?= e(img_src($u['avatar'] ?? null, 'avatar' . $u['id'])) ?>" alt="">
                            <span class="table-user-name" title="<?= e($u['name']) ?>"><?= e($u['name']) ?></span>
                        </div>
                    </td>
                    <td class="col-email">
                        <?php if (!empty($u['oauth_provider'])): ?>
                            <?php $prov = $u['oauth_provider'] === 'facebook' ? 'Facebook' : 'Google'; ?>
                            <span class="table-user-email" title="Signed in with <?= e($prov) ?> — email kept private">🔒 <?= e($prov) ?> account (private)</span>
                        <?php else: ?>
                            <span class="table-user-email" title="<?= e($u['email']) ?>"><?= e($u['email']) ?></span>
                        <?php endif; ?>
                    </td>
                    <td class="col-role"><span class="pill pill-<?= $u['role'] === 'admin' ? 'completed' : ($u['role'] === 'guide' ? 'confirmed' : 'pending') ?>"><?= e(ucfirst($u['role'])) ?></span></td>
                    <td class="col-joined"><?= e(date('M j, Y', strtotime($u['created_at']))) ?></td>
                    <td class="col-status">
                        <?php if ((int) ($u['guide_warned'] ?? 0) === 1): ?>
                            <span class="pill pill-disputed">Warned</span>
                        <?php elseif ((int) $u['is_active']): ?>
                            <span class="pill pill-approved">Active</span>
                        <?php else: ?>
                            <span class="pill pill-cancelled">Suspended</span>
                        <?php endif; ?>
                    </td>
                    <td class="col-actions">
                        <?php if ($u['role'] !== 'admin'): ?>
                            <div class="user-actions">
                                <form method="post" action="<?= e(url('/admin/users/' . $u['id'] . '/toggle')) ?>">
                                    <?= csrf_field() ?>
                                    <button class="btn btn-ghost btn-sm"><?= (int) $u['is_active'] ? 'Suspend' : 'Activate' ?></button>
                                </form>
                                <?php if ($u['role'] === 'guide' && (int) ($u['guide_warned'] ?? 0) === 1): ?>
                                    <form method="post" action="<?= e(url('/admin/users/' . $u['id'] . '/clear-warning')) ?>">
                                        <?= csrf_field() ?>
                                        <button class="btn btn-primary btn-sm">Clear warning</button>
                                    </form>
                                <?php elseif ($u['role'] === 'guide' && ($u['guide_status'] ?? '') === 'approved'): ?>
                                    <form method="post" action="<?= e(url('/admin/guides/' . $u['id'] . '/revoke')) ?>" onsubmit="return confirm('Revoke this guide\'s verification? They will need to re-submit documents.');">
                                        <?= csrf_field() ?>
                                        <button class="btn btn-ghost btn-sm">Revoke</button>
                                    </form>
                                <?php endif; ?>
                            </div>
                        <?php else: ?><span class="hint">—</span><?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
