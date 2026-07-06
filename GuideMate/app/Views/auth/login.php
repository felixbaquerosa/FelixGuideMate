<?php
/** @var array<string,string> $errors */
$errors = $errors ?? [];
?>
<a href="<?= e(url('/')) ?>" class="brand a-rise" style="margin-bottom:2rem;display:inline-flex;animation-delay:.05s;">
    <span class="brand-mark">◐</span><span class="brand-text">Guide<strong>Mate</strong></span>
</a>
<h1 class="a-rise" style="animation-delay:.12s;">Welcome back</h1>
<p class="auth-switch a-rise" style="animation-delay:.18s;">New here? <a href="<?= e(url('/register')) ?>">Create an account</a></p>

<form method="post" action="<?= e(url('/login')) ?>" novalidate>
    <?= csrf_field() ?>
    <div class="field-row a-rise" style="animation-delay:.24s;">
        <label for="email">Email</label>
        <div class="input-wrap">
            <span class="input-ic">✉️</span>
            <input class="input has-ic" type="email" id="email" name="email" value="<?= e(old('email')) ?>" placeholder="you@example.com" autofocus>
        </div>
        <?php if (isset($errors['email'])): ?><div class="field-error"><?= e($errors['email']) ?></div><?php endif; ?>
    </div>
    <div class="field-row a-rise" style="animation-delay:.3s;">
        <label for="password">Password</label>
        <div class="input-wrap">
            <span class="input-ic">🔒</span>
            <input class="input has-ic" type="password" id="password" name="password" placeholder="••••••••">
        </div>
        <?php if (isset($errors['password'])): ?><div class="field-error"><?= e($errors['password']) ?></div><?php endif; ?>
    </div>
    <div class="auth-row a-rise" style="animation-delay:.36s;">
        <label class="checkbox"><input type="checkbox" name="remember" value="1"> Remember me</label>
        <a href="<?= e(url('/forgot-password')) ?>" class="hint">Forgot password?</a>
    </div>
    <button class="btn btn-primary btn-block btn-lg a-rise" style="animation-delay:.42s;" type="submit">Log in</button>
</form>

<p class="hint a-rise" style="text-align:center;margin-top:1.5rem;animation-delay:.5s;">
    Want to host tours? <a href="<?= e(url('/register?role=guide')) ?>">Become a guide</a>
</p>
