<?php
/** @var array<int,array<string,mixed>> $users */

// Labels + pill colours for each account type so the roles read clearly and
// are visually distinct in the table.
$roleLabels = [
    'tourist' => 'Tourist',
    'guide' => 'Tour Guide',
    'hotel_admin' => 'Hotel Partner',
    'rental_admin' => 'Rental Partner',
    'admin' => 'Admin',
];
$rolePillMap = [
    'admin' => 'completed',
    'guide' => 'confirmed',
    'hotel_admin' => 'refunded',
    'rental_admin' => 'resolved_warning',
    'tourist' => 'pending',
];

// Count how many users fall under each role for the filter tabs.
$roleCounts = [];
$warnedCount = 0;
foreach (array_keys($roleLabels) as $rk) {
    $roleCounts[$rk] = 0;
}
foreach ($users as $u) {
    $r = (string) ($u['role'] ?? 'tourist');
    if (!isset($roleCounts[$r])) {
        $roleCounts[$r] = 0;
    }
    $roleCounts[$r]++;
    if (\App\Models\User::isProviderRole($r) && (int) ($u['guide_warned'] ?? 0) === 1) {
        $warnedCount++;
    }
}
?>
<h1>Manage users</h1>

<div class="role-tabs" role="tablist">
    <button type="button" class="role-tab is-active" data-role-filter="all">
        All <span class="count"><?= count($users) ?></span>
    </button>
    <?php foreach ($roleLabels as $roleKey => $roleName): ?>
        <?php if (($roleCounts[$roleKey] ?? 0) > 0): ?>
            <button type="button" class="role-tab" data-role-filter="<?= e($roleKey) ?>">
                <?= e($roleName) ?>s <span class="count"><?= (int) $roleCounts[$roleKey] ?></span>
            </button>
        <?php endif; ?>
    <?php endforeach; ?>
    <?php if ($warnedCount > 0): ?>
        <button type="button" class="role-tab" data-role-filter="warned">
            Restricted <span class="count"><?= (int) $warnedCount ?></span>
        </button>
    <?php endif; ?>
</div>

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
                <?php $role = (string) ($u['role'] ?? 'tourist'); ?>
                <?php $isWarned = \App\Models\User::isProviderRole($role) && (int) ($u['guide_warned'] ?? 0) === 1; ?>
                <tr data-role="<?= e($role) ?>" data-warned="<?= $isWarned ? '1' : '0' ?>">
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
                    <td class="col-role"><span class="pill pill-<?= e($rolePillMap[$role] ?? 'pending') ?>"><?= e($roleLabels[$role] ?? ucfirst(str_replace('_', ' ', $role))) ?></span></td>
                    <td class="col-joined"><?= e(date('M j, Y', strtotime($u['created_at']))) ?></td>
                    <td class="col-status">
                        <?php if ($isWarned): ?>
                            <span class="pill pill-disputed">Restricted</span>
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
                                <?php if ($isWarned): ?>
                                    <?= \App\Core\View::partial('partials/admin-unrestrict', [
                                        'id' => (int) $u['id'],
                                        'name' => (string) $u['name'],
                                        'return' => '/admin/users',
                                    ]) ?>
                                <?php elseif (\App\Models\User::isProviderRole($role) && ($u['guide_status'] ?? '') === 'approved'): ?>
                                    <form method="post" action="<?= e(url('/admin/guides/' . $u['id'] . '/revoke')) ?>" onsubmit="return confirm('Revoke this partner\'s verification? They will need to re-submit documents.');">
                                        <?= csrf_field() ?>
                                        <button class="btn btn-ghost btn-sm">Revoke</button>
                                    </form>
                                <?php endif; ?>
                            </div>
                        <?php else: ?><span class="hint">—</span><?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
                <tr id="usersEmptyRow" hidden>
                    <td colspan="6" class="empty-state" style="text-align:center;padding:2rem;">No users in this group yet.</td>
                </tr>
            </tbody>
        </table>
    </div>
</div>

<script>
(function () {
    var tabs = document.querySelectorAll('.role-tab[data-role-filter]');
    var rows = document.querySelectorAll('tr[data-role]');
    var emptyRow = document.getElementById('usersEmptyRow');

    function applyFilter(filter) {
        var visible = 0;
        rows.forEach(function (row) {
            var show = filter === 'all'
                || (filter === 'warned' && row.getAttribute('data-warned') === '1')
                || row.getAttribute('data-role') === filter;
            row.hidden = !show;
            if (show) { visible++; }
        });
        if (emptyRow) { emptyRow.hidden = visible > 0; }
    }

    tabs.forEach(function (tab) {
        tab.addEventListener('click', function () {
            tabs.forEach(function (t) { t.classList.remove('is-active'); });
            tab.classList.add('is-active');
            applyFilter(tab.getAttribute('data-role-filter'));
        });
    });
})();
</script>
