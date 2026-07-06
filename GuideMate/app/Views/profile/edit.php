<?php
/** @var array<string,mixed> $user @var array<string,string> $errors */
$errors = $errors ?? [];
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
        </div>
    </div>
</div>
