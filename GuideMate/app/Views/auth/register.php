<?php
/** @var array<string,string> $errors @var string $role */

use App\Models\GuideDocument;
use App\Models\User;

$errors = $errors ?? [];
// The web portal is for service providers. The applicant picks which kind.
$selectedRole = old('role') !== '' ? old('role') : ($role ?? 'guide');
if (!User::isProviderRole((string) $selectedRole)) {
    $selectedRole = 'guide';
}
$providerLabels = User::PROVIDER_LABELS;
?>
<a href="<?= e(url('/')) ?>" class="brand a-rise" style="margin-bottom:1.5rem;display:inline-flex;animation-delay:.05s;">
    <span class="brand-mark">◐</span><span class="brand-text">Guide<strong>Mate</strong></span>
</a>
<h1 class="a-rise" style="animation-delay:.12s;">Create your account</h1>
<p class="auth-switch a-rise" style="animation-delay:.18s;">Already have one? <a href="<?= e(url('/login')) ?>">Log in</a></p>

<form method="post" action="<?= e(url('/register')) ?>" enctype="multipart/form-data" novalidate>
    <?= csrf_field() ?>

    <div class="field-row a-rise" style="animation-delay:.24s;">
        <label for="role">I want to join as</label>
        <div class="input-wrap">
            <span class="input-ic">🧭</span>
            <select class="input has-ic" id="role" name="role">
                <?php foreach ($providerLabels as $key => $label): ?>
                    <option value="<?= e($key) ?>" <?= $selectedRole === $key ? 'selected' : '' ?>><?= e($label) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <p class="hint mb-0" id="roleHint"></p>
    </div>

    <div class="field-row a-rise" style="animation-delay:.3s;">
        <label for="name">Full name</label>
        <div class="input-wrap">
            <span class="input-ic">👤</span>
            <input class="input has-ic" type="text" id="name" name="name" value="<?= e(old('name')) ?>" placeholder="Juan Dela Cruz">
        </div>
        <?php if (isset($errors['name'])): ?><div class="field-error"><?= e($errors['name']) ?></div><?php endif; ?>
    </div>
    <div class="field-row a-rise" style="animation-delay:.36s;">
        <label for="email">Email</label>
        <div class="input-wrap">
            <span class="input-ic">✉️</span>
            <input class="input has-ic" type="email" id="email" name="email" value="<?= e(old('email')) ?>" placeholder="you@example.com">
        </div>
        <?php if (isset($errors['email'])): ?><div class="field-error"><?= e($errors['email']) ?></div><?php endif; ?>
    </div>
    <div class="form-grid-2 a-rise" style="animation-delay:.42s;">
        <div class="field-row">
            <label for="password">Password</label>
            <div class="input-wrap">
                <span class="input-ic">🔒</span>
                <input class="input has-ic" type="password" id="password" name="password" placeholder="8–12 characters" minlength="8" maxlength="12">
            </div>
            <?php if (isset($errors['password'])): ?><div class="field-error"><?= e($errors['password']) ?></div><?php endif; ?>
        </div>
        <div class="field-row">
            <label for="password_confirm">Confirm</label>
            <div class="input-wrap">
                <span class="input-ic">🔒</span>
                <input class="input has-ic" type="password" id="password_confirm" name="password_confirm" placeholder="Repeat password" minlength="8" maxlength="12">
            </div>
            <?php if (isset($errors['password_confirm'])): ?><div class="field-error"><?= e($errors['password_confirm']) ?></div><?php endif; ?>
        </div>
    </div>
    <p class="hint a-rise" style="margin-top:-.4rem;margin-bottom:1.1rem;animation-delay:.48s;">
        Password must be <strong>8–12 characters</strong> and include an uppercase letter, a number, and a special character.
    </p>

    <!-- Provider verification — every provider role is reviewed by an admin -->
    <div id="guideDocs" class="guide-docs a-rise" style="animation-delay:.54s;">
        <div class="guide-docs-head">
            <span class="guide-docs-badge">🛡️ Partner verification</span>
            <p class="hint mb-0">To keep travelers safe, every partner is reviewed by our admin team before going live. Upload clear photos or PDFs (max 5MB each). Accepted: ID, business permit, license, accreditation, certificates, employment verification, or association membership.</p>
        </div>

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
                    <option value="<?= e($key) ?>" <?= old('credential_type') === $key ? 'selected' : '' ?>><?= e($label) ?></option>
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
    </div>

    <div class="field-row a-rise" style="animation-delay:.58s;">
        <label class="checkbox">
            <input type="checkbox" name="accept_terms" value="1" required>
            I agree to the <a href="<?= e(url('/terms')) ?>" target="_blank">Terms of Service</a> and <a href="<?= e(url('/privacy')) ?>" target="_blank">Privacy Policy</a>
        </label>
        <?php if (isset($errors['accept_terms'])): ?><div class="field-error"><?= e($errors['accept_terms']) ?></div><?php endif; ?>
    </div>

    <button class="btn btn-primary btn-block btn-lg a-rise" style="animation-delay:.6s;" type="submit">Create account</button>
</form>

<script>
(function () {
    var hints = {
        guide: 'Lead tours and manage your own experience listings and bookings.',
        rental_admin: 'List rental vehicles and handle incoming rental requests.',
        hotel_admin: 'List your hotel / accommodation and manage guest bookings.'
    };
    var select = document.getElementById('role');
    var hint = document.getElementById('roleHint');
    function sync() { if (select && hint) { hint.textContent = hints[select.value] || ''; } }
    if (select) { select.addEventListener('change', sync); sync(); }
})();
</script>
