<?php
/** @var array<string,string> $errors */
$errors = $errors ?? [];
?>
<a href="<?= e(url('/login')) ?>" class="brand a-rise" style="margin-bottom:2rem;display:inline-flex;">
    <span class="brand-mark">◐</span><span class="brand-text">Guide<strong>Mate</strong></span>
</a>
<h1>Forgot password</h1>
<p class="auth-switch">Enter your email and we will send a reset link.</p>

<form method="post" action="<?= e(url('/forgot-password')) ?>" novalidate>
    <?= csrf_field() ?>
    <div class="field-row">
        <label for="email">Email</label>
        <input class="input" type="email" id="email" name="email" value="<?= e(old('email')) ?>" required autofocus>
        <?php if (isset($errors['email'])): ?><div class="field-error"><?= e($errors['email']) ?></div><?php endif; ?>
    </div>
    <button class="btn btn-primary btn-block" type="submit">Send reset link</button>
</form>
<p class="hint" style="margin-top:1rem;"><a href="<?= e(url('/login')) ?>">Back to log in</a></p>
