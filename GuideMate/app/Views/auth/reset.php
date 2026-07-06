<?php
/** @var string $token @var array<string,string> $errors */
$errors = $errors ?? [];
?>
<a href="<?= e(url('/login')) ?>" class="brand a-rise" style="margin-bottom:2rem;display:inline-flex;">
    <span class="brand-mark">◐</span><span class="brand-text">Guide<strong>Mate</strong></span>
</a>
<h1>Reset password</h1>
<p class="auth-switch">Choose a new password for your account.</p>

<form method="post" action="<?= e(url('/reset-password/' . $token)) ?>" novalidate>
    <?= csrf_field() ?>
    <div class="field-row">
        <label for="password">New password</label>
        <input class="input" type="password" id="password" name="password" required>
        <?php if (isset($errors['password'])): ?><div class="field-error"><?= e($errors['password']) ?></div><?php endif; ?>
    </div>
    <div class="field-row">
        <label for="password_confirm">Confirm password</label>
        <input class="input" type="password" id="password_confirm" name="password_confirm" required>
        <?php if (isset($errors['password_confirm'])): ?><div class="field-error"><?= e($errors['password_confirm']) ?></div><?php endif; ?>
    </div>
    <button class="btn btn-primary btn-block" type="submit">Update password</button>
</form>
