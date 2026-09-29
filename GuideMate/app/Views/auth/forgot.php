<?php
/** @var array<string,string> $errors */
$errors = $errors ?? [];
?>
<a href="<?= e(url('/login')) ?>" class="brand a-rise" style="margin-bottom:2rem;display:inline-flex;">
    <span class="brand-mark">◐</span><span class="brand-text">Guide<strong>Mate</strong></span>
</a>
<h1>Forgot password</h1>
<p class="auth-switch">Enter your email and we will send a reset link.</p>

<form id="forgotForm" method="post" action="<?= e(url('/forgot-password')) ?>" novalidate>
    <?= csrf_field() ?>

    <div id="formError" class="form-alert" role="alert" style="display:none;"></div>

    <div class="field-row">
        <label for="email">Email</label>
        <input class="input" type="email" id="email" name="email" value="<?= e(old('email')) ?>" required autofocus>
        <?php if (isset($errors['email'])): ?><div class="field-error"><?= e($errors['email']) ?></div><?php endif; ?>
    </div>
    <button class="btn btn-primary btn-block" type="submit">Send reset link</button>
</form>
<p class="hint" style="margin-top:1rem;"><a href="<?= e(url('/login')) ?>">Back to log in</a></p>

<script>
(function () {
    // Client-side "email required" guard (form uses novalidate). The server
    // still re-validates as the source of truth.
    var form = document.getElementById('forgotForm');
    var box = document.getElementById('formError');
    if (!form || !box) { return; }

    var email = document.getElementById('email');
    function mark(bad) {
        if (!email) { return; }
        email.style.outline = bad ? '2px solid #EF4444' : '';
        email.style.borderRadius = bad ? '10px' : '';
    }
    if (email) { email.addEventListener('input', function () { mark(false); }); }

    form.addEventListener('submit', function (e) {
        var empty = !email || String(email.value || '').trim() === '';
        mark(empty);
        if (empty) {
            e.preventDefault();
            box.textContent = 'Please enter your email, and try again.';
            box.style.display = 'block';
            if (email) {
                try { email.focus({ preventScroll: true }); } catch (err) { email.focus(); }
            }
        }
    });
})();
</script>
