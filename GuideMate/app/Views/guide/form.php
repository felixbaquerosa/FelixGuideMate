<?php
/**
 * @var ?array<string,mixed> $listing
 * @var array<int,array<string,mixed>> $categories
 * @var array<string,string> $errors
 */
$errors = $errors ?? [];
$editing = $listing !== null;
$action = $editing ? url('/dashboard/listings/' . $listing['id']) : url('/dashboard/listings');
$val = fn(string $k, string $d = '') => e($editing ? (string) ($listing[$k] ?? $d) : old($k, $d));
?>
<div class="container">
    <div class="dash-layout">
        <?= \App\Core\View::partial('partials/dash-nav') ?>
        <div>
            <div class="breadcrumb"><a href="<?= e(url('/dashboard/listings')) ?>">My listings</a> › <?= $editing ? 'Edit' : 'Create' ?></div>
            <h1><?= $editing ? 'Edit listing' : 'Create a listing' ?></h1>

            <div class="panel" style="max-width:760px;">
                <div class="panel-body">
                    <form method="post" action="<?= e($action) ?>" enctype="multipart/form-data">
                        <?= csrf_field() ?>
                        <?php if ($editing): ?><input type="hidden" name="listing_id" value="<?= (int) $listing['id'] ?>"><?php endif; ?>

                        <div class="field-row">
                            <label for="title">Title</label>
                            <input class="input" id="title" name="title" value="<?= $val('title') ?>" placeholder="e.g. Kawasan Falls Canyoneering Adventure">
                            <?php if (isset($errors['title'])): ?><div class="field-error"><?= e($errors['title']) ?></div><?php endif; ?>
                        </div>

                        <div class="form-grid-2">
                            <div class="field-row">
                                <label for="category_id">Category</label>
                                <select class="input" id="category_id" name="category_id">
                                    <option value="">Choose...</option>
                                    <?php foreach ($categories as $c):
                                        $sel = (string) ($editing ? $listing['category_id'] : old('category_id')) === (string) $c['id']; ?>
                                        <option value="<?= (int) $c['id'] ?>" <?= $sel ? 'selected' : '' ?>><?= e($c['name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <?php if (isset($errors['category_id'])): ?><div class="field-error"><?= e($errors['category_id']) ?></div><?php endif; ?>
                            </div>
                            <div class="field-row">
                                <label for="area">Area in Cebu</label>
                                <input class="input" id="area" name="area" value="<?= $val('area') ?>" placeholder="e.g. Moalboal, Cebu">
                                <?php if (isset($errors['area'])): ?><div class="field-error"><?= e($errors['area']) ?></div><?php endif; ?>
                            </div>
                        </div>

                        <div class="field-row">
                            <label for="summary">Short summary</label>
                            <input class="input" id="summary" name="summary" value="<?= $val('summary') ?>" placeholder="One sentence that sells it" maxlength="255">
                        </div>

                        <div class="field-row">
                            <label for="description">Full description</label>
                            <textarea class="input" id="description" name="description" rows="6" placeholder="Describe the experience, what's included, what to bring..."><?= $val('description') ?></textarea>
                            <?php if (isset($errors['description'])): ?><div class="field-error"><?= e($errors['description']) ?></div><?php endif; ?>
                        </div>

                        <div class="guide-docs" style="margin-bottom:1rem;">
                            <div class="guide-docs-head">
                                <strong>What's included in the price</strong>
                                <span class="guide-docs-badge">Required for trust</span>
                            </div>
                            <p class="hint mb-0" style="margin-bottom:.75rem;">List everything covered by your booking price. Guides must not ask for extra payment after a tourist has paid unless it was clearly listed below.</p>
                        </div>

                        <div class="field-row">
                            <label for="included">Included (one item per line)</label>
                            <textarea class="input" id="included" name="included" rows="4" placeholder="e.g. Guide fee&#10;Pickup from hotel&#10;Snorkeling gear&#10;Life vest"><?= $val('included') ?></textarea>
                        </div>
                        <div class="field-row">
                            <label for="not_included">Not included — optional extras (one item per line)</label>
                            <textarea class="input" id="not_included" name="not_included" rows="3" placeholder="e.g. Lunch&#10;Park entrance fee (₱100)"><?= $val('not_included') ?></textarea>
                            <span class="hint">Only costs listed here may be requested separately. Everything else must be covered by the booking price.</span>
                        </div>

                        <div class="form-grid-2">
                            <div class="field-row">
                                <label for="price">Price (₱)</label>
                                <input class="input" id="price" name="price" type="number" step="0.01" min="0" value="<?= $val('price') ?>">
                                <?php if (isset($errors['price'])): ?><div class="field-error"><?= e($errors['price']) ?></div><?php endif; ?>
                            </div>
                            <div class="field-row">
                                <label for="price_unit">Price unit</label>
                                <select class="input" id="price_unit" name="price_unit">
                                    <?php foreach (['per person','per night','per tour','per day','per table'] as $u):
                                        $sel = ($editing ? $listing['price_unit'] : old('price_unit', 'per person')) === $u; ?>
                                        <option value="<?= e($u) ?>" <?= $sel ? 'selected' : '' ?>><?= e($u) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <div class="form-grid-2">
                            <div class="field-row">
                                <label for="duration">Duration (optional)</label>
                                <input class="input" id="duration" name="duration" value="<?= $val('duration') ?>" placeholder="e.g. 4-5 hours">
                            </div>
                            <div class="field-row">
                                <label for="address">Address (optional)</label>
                                <input class="input" id="address" name="address" value="<?= $val('address') ?>" placeholder="Landmark / address">
                            </div>
                        </div>

                        <div class="field-row">
                            <label for="cover_file">Cover photo</label>
                            <?php $currentCover = $editing ? (string) ($listing['cover_image'] ?? '') : ''; ?>
                            <?php if ($currentCover !== ''): ?>
                                <img id="coverPreview" src="<?= e(img_src($currentCover, 'listing' . $listing['id'])) ?>" alt="" style="width:100%;max-width:280px;height:160px;object-fit:cover;border-radius:12px;margin-bottom:.6rem;" onerror="this.onerror=null;this.src='https://picsum.photos/seed/listing<?= (int) $listing['id'] ?>/600/400';">
                            <?php else: ?>
                                <img id="coverPreview" src="" alt="" style="display:none;width:100%;max-width:280px;height:160px;object-fit:cover;border-radius:12px;margin-bottom:.6rem;">
                            <?php endif; ?>
                            <input class="file-input" type="file" id="cover_file" name="cover_file" accept=".jpg,.jpeg,.png,.webp">
                            <span class="hint">Upload a JPG, PNG or WEBP (up to 5MB). <?= $editing ? 'Leave blank to keep the current photo.' : 'Leave blank to use an automatic placeholder.' ?></span>
                            <?php if (isset($errors['cover_file'])): ?><div class="field-error"><?= e($errors['cover_file']) ?></div><?php endif; ?>
                        </div>

                        <?php if (!$editing): ?>
                        <div class="field-row">
                            <label for="cover_image">…or paste a direct image URL (optional)</label>
                            <input class="input" id="cover_image" name="cover_image" value="<?= e(old('cover_image')) ?>" placeholder="https://example.com/photo.jpg">
                            <span class="hint">Must be a direct link ending in .jpg, .png, etc. — not a share/page link. An uploaded photo takes priority.</span>
                            <?php if (isset($errors['cover_image'])): ?><div class="field-error"><?= e($errors['cover_image']) ?></div><?php endif; ?>
                        </div>
                        <?php endif; ?>

                        <div class="mt-2" style="display:flex;gap:.6rem;">
                            <button class="btn btn-primary btn-lg" type="submit"><?= $editing ? 'Save changes' : 'Submit for review' ?></button>
                            <a class="btn btn-ghost btn-lg" href="<?= e(url('/dashboard/listings')) ?>">Cancel</a>
                        </div>
                        <?php if (!$editing): ?><p class="hint mt-2">New listings are reviewed by an admin before going live.</p><?php endif; ?>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
