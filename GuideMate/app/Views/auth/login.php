<?php
/** @var array<string,string> $errors @var string $redirect */
$errors = $errors ?? [];
$redirect = $redirect ?? '';
?>
<a href="<?= e(url('/')) ?>" class="brand a-rise" style="margin-bottom:2rem;display:inline-flex;animation-delay:.05s;">
    <span class="brand-mark">◐</span><span class="brand-text">Guide<strong>Mate</strong></span>
</a>
<h1 class="a-rise" style="animation-delay:.12s;">Welcome back</h1>
<p class="auth-switch a-rise" style="animation-delay:.18s;">New here? <a href="<?= e(url('/register')) ?>">Create an account</a></p>

<form id="loginForm" method="post" action="<?= e(url('/login')) ?>" novalidate>
    <?= csrf_field() ?>
    <?php if ($redirect !== ''): ?><input type="hidden" name="redirect" value="<?= e($redirect) ?>"><?php endif; ?>

    <div id="formError" class="form-alert a-rise" role="alert" style="display:none;animation-delay:.2s;"></div>

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

<script>
(function () {
    // Client-side "fill everything" guard. The form uses novalidate so we show
    // one clear message and highlight the empty fields. The server still
    // re-validates as the source of truth.
    var form = document.getElementById('loginForm');
    var box = document.getElementById('formError');
    if (!form || !box) { return; }

    var required = ['email', 'password'];
    function mark(el, bad) {
        if (!el) { return; }
        var wrap = el.closest('.input-wrap') || el;
        wrap.style.outline = bad ? '2px solid #EF4444' : '';
        wrap.style.borderRadius = bad ? '10px' : '';
    }
    required.forEach(function (id) {
        var el = document.getElementById(id);
        if (el) { el.addEventListener('input', function () { mark(el, false); }); }
    });

    form.addEventListener('submit', function (e) {
        var missing = [];
        required.forEach(function (id) {
            var el = document.getElementById(id);
            var empty = !el || String(el.value || '').trim() === '';
            mark(el, empty);
            if (empty) { missing.push(el); }
        });
        if (missing.length > 0) {
            e.preventDefault();
            box.textContent = 'Please enter your email and password, and try again.';
            box.style.display = 'block';
            if (missing[0]) {
                try { missing[0].focus({ preventScroll: true }); } catch (err) { missing[0].focus(); }
            }
        }
    });
})();
</script>
