<?php
/**
 * @var array<string,mixed> $user
 * @var array<string,string> $errors
 * @var array<int,array<string,mixed>> $documents
 */
$errors = $errors ?? [];
$documents = $documents ?? [];
$isGuide = \App\Models\User::isProviderRole((string) ($user['role'] ?? ''));
$guideStatus = (string) ($user['guide_status'] ?? 'none');
$isVerified = $guideStatus === 'approved';
$verifyBadge = [
    'approved' => ['cls' => 'pill-confirmed', 'label' => '✅ Verified'],
    'pending' => ['cls' => 'pill-pending', 'label' => '⏳ Under review'],
    'rejected' => ['cls' => 'pill-cancelled', 'label' => '⚠️ Not approved'],
][$guideStatus] ?? ['cls' => 'pill-pending', 'label' => 'ℹ️ Not submitted'];
?>
<div class="container">
    <div class="dash-layout">
        <?= \App\Core\View::partial('partials/dash-nav') ?>
        <div>
            <h1>My profile</h1>
            <div class="panel" style="max-width:640px;">
                <div class="panel-body">
                    <div class="guide-mini" style="margin-bottom:1.5rem;">
                        <img id="avatarPreview" src="<?= e(img_src($user['avatar'] ?? null, 'avatar' . $user['id'])) ?>" alt="" style="width:64px;height:64px;border-radius:50%;object-fit:cover;">
                        <div>
                            <strong style="font-size:1.1rem;"><?= e($user['name']) ?></strong><br>
                            <span class="pill pill-confirmed"><?= e(ucfirst($user['role'])) ?></span>
                        </div>
                    </div>
                    <form method="post" action="<?= e(url('/profile')) ?>" enctype="multipart/form-data">
                        <?= csrf_field() ?>
                        <div class="field-row">
                            <label for="avatar">Profile photo</label>
                            <input class="file-input" type="file" id="avatar" name="avatar" accept=".jpg,.jpeg,.png,.webp">
                            <span class="hint">JPG, PNG or WEBP, up to 5MB.</span>
                            <?php if (isset($errors['avatar'])): ?><div class="field-error"><?= e($errors['avatar']) ?></div><?php endif; ?>
                        </div>
                        <div class="field-row">
                            <label for="name">Full name</label>
                            <input class="input" id="name" name="name" value="<?= e($user['name']) ?>">
                            <?php if (isset($errors['name'])): ?><div class="field-error"><?= e($errors['name']) ?></div><?php endif; ?>
                        </div>
                        <div class="field-row">
                            <label>Email</label>
                            <input class="input" value="<?= e($user['email']) ?>" disabled>
                            <span class="hint">Email cannot be changed.</span>
                        </div>
                        <div class="form-grid-2">
                            <div class="field-row">
                                <label for="phone">Phone</label>
                                <input class="input" id="phone" name="phone" value="<?= e($user['phone'] ?? '') ?>">
                            </div>
                            <div class="field-row">
                                <label for="location">Location</label>
                                <input class="input" id="location" name="location" value="<?= e($user['location'] ?? '') ?>">
                            </div>
                        </div>
                        <div class="field-row">
                            <label for="bio"><?= ($user['role'] === 'guide') ? 'About you (shown to travelers)' : 'Bio' ?></label>
                            <textarea class="input" id="bio" name="bio"><?= e($user['bio'] ?? '') ?></textarea>
                        </div>
                        <button class="btn btn-primary" type="submit">Save changes</button>
                    </form>
                </div>
            </div>

            <?php if ($isGuide): ?>
                <div class="panel" style="max-width:640px;margin-top:1.5rem;">
                    <div class="panel-head" style="display:flex;align-items:center;justify-content:space-between;gap:.75rem;">
                        <h3 style="margin:0;">Verification &amp; documents</h3>
                        <span class="pill <?= $verifyBadge['cls'] ?>"><?= e($verifyBadge['label']) ?></span>
                    </div>
                    <div class="panel-body">
                        <?php if ($isVerified): ?>
                            <p class="hint" style="margin-top:0;">These documents were reviewed and verified by our admin team.</p>
                        <?php elseif ($guideStatus === 'pending'): ?>
                            <p class="hint" style="margin-top:0;">Your documents are being reviewed by our admin team.</p>
                        <?php else: ?>
                            <p class="hint" style="margin-top:0;">Submit your documents to become a verified guide.</p>
                        <?php endif; ?>

                        <?php if ($documents === []): ?>
                            <p class="hint mb-0">No documents on file yet.</p>
                        <?php else: ?>
                            <ul class="doc-list">
                                <?php foreach ($documents as $d): ?>
                                    <li>
                                        <span class="doc-type"><?= e(\App\Models\GuideDocument::typeLabel($d['doc_type'])) ?></span>
                                        <a href="<?= e(url($d['file_path'])) ?>" target="_blank" rel="noopener" class="doc-link">📎 <?= e($d['label'] ?: basename($d['file_path'])) ?> ↗</a>
                                        <?php if ($isVerified): ?>
                                            <span class="pill pill-confirmed" style="margin-left:.5rem;">✓ Verified</span>
                                        <?php elseif ($guideStatus === 'pending'): ?>
                                            <span class="pill pill-pending" style="margin-left:.5rem;">Pending</span>
                                        <?php endif; ?>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>

                        <a href="<?= e(url('/dashboard/verification')) ?>" class="btn btn-ghost" style="margin-top:1rem;">
                            <?= $isVerified ? 'Manage documents' : 'Submit / update documents' ?>
                        </a>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
