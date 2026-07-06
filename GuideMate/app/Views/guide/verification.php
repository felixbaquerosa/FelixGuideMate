<?php
/**
 * @var array<string,mixed> $user
 * @var array<int,array<string,mixed>> $documents
 * @var array<string,string> $errors
 */

use App\Models\GuideDocument;

$errors = $errors ?? [];
$status = (string) ($user['guide_status'] ?? 'none');
$banner = [
    'pending' => ['cls' => 'banner-amber', 'icon' => '⏳', 'title' => 'Application under review', 'text' => 'Our admin team is reviewing your documents. You can publish listings once your account is approved.'],
    'approved' => ['cls' => 'banner-green', 'icon' => '', 'title' => '✅Verified', 'text' => ''],
    'rejected' => ['cls' => 'banner-red', 'icon' => '⚠️', 'title' => 'Application not approved', 'text' => 'Please review the note below, then upload updated documents to re-apply.'],
][$status] ?? ['cls' => 'banner-amber', 'icon' => 'ℹ️', 'title' => 'Verification', 'text' => 'Submit your documents to become a verified guide.'];
?>
<div class="container">
    <div class="dash-layout">
        <?= \App\Core\View::partial('partials/dash-nav') ?>
        <div>
            <h1 style="margin-bottom:1rem;">Guide verification</h1>

            <div class="status-banner <?= $banner['cls'] ?>">
                <?php if ($banner['icon'] !== ''): ?><span class="status-icon"><?= $banner['icon'] ?></span><?php endif; ?>
                <div>
                    <strong><?= e($banner['title']) ?></strong>
                    <?php if ($banner['text'] !== ''): ?><p class="mb-0"><?= e($banner['text']) ?></p><?php endif; ?>
                    <?php if ($status === 'rejected' && !empty($user['guide_review_note'])): ?>
                        <p class="note-box" style="margin-top:.6rem;"><strong>Admin note:</strong> <?= e($user['guide_review_note']) ?></p>
                    <?php endif; ?>
                </div>
            </div>

            <div class="panel">
                <div class="panel-head"><h3>Your submitted documents</h3></div>
                <div class="panel-body">
                    <?php if ($documents === []): ?>
                        <p class="hint mb-0">No documents on file yet.</p>
                    <?php else: ?>
                        <ul class="doc-list">
                            <?php foreach ($documents as $d): ?>
                                <li>
                                    <span class="doc-type"><?= e(GuideDocument::typeLabel($d['doc_type'])) ?></span>
                                    <a href="<?= e(url($d['file_path'])) ?>" target="_blank" rel="noopener" class="doc-link">📎 <?= e($d['label'] ?: basename($d['file_path'])) ?> ↗</a>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </div>
            </div>

            <?php if ($status !== 'approved'): ?>
                <div class="panel">
                    <div class="panel-head"><h3><?= $status === 'rejected' ? 'Re-submit documents' : 'Update documents' ?></h3></div>
                    <div class="panel-body">
                        <p class="hint">Upload clear photos or PDFs (max 5MB each). Re-submitting replaces your previous files and sends your application back for review.</p>
                        <form method="post" action="<?= e(url('/dashboard/verification')) ?>" enctype="multipart/form-data" novalidate>
                            <?= csrf_field() ?>
                            <div class="field-row">
                                <label for="valid_id">Valid government ID <span class="req">*</span></label>
                                <input class="file-input" type="file" id="valid_id" name="valid_id" accept=".pdf,.jpg,.jpeg,.png,.webp">
                                <?php if (isset($errors['valid_id'])): ?><div class="field-error"><?= e($errors['valid_id']) ?></div><?php endif; ?>
                            </div>
                            <div class="field-row">
                                <label for="credential_type">Credential type <span class="req">*</span></label>
                                <select class="input" id="credential_type" name="credential_type">
                                    <?php foreach (GuideDocument::TYPES as $key => $label): ?>
                                        <?php if ($key === 'valid_id' || $key === 'other') { continue; } ?>
                                        <option value="<?= e($key) ?>"><?= e($label) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="field-row">
                                <label for="credential">Credential document <span class="req">*</span></label>
                                <input class="file-input" type="file" id="credential" name="credential" accept=".pdf,.jpg,.jpeg,.png,.webp">
                                <?php if (isset($errors['credential'])): ?><div class="field-error"><?= e($errors['credential']) ?></div><?php endif; ?>
                            </div>
                            <div class="field-row">
                                <label for="extra_docs">Additional documents <span class="hint">(optional)</span></label>
                                <input class="file-input" type="file" id="extra_docs" name="extra_docs[]" accept=".pdf,.jpg,.jpeg,.png,.webp" multiple>
                                <?php if (isset($errors['extra_docs'])): ?><div class="field-error"><?= e($errors['extra_docs']) ?></div><?php endif; ?>
                            </div>
                            <button class="btn btn-primary" type="submit">Submit for review</button>
                        </form>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
