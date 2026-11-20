<?php
/** @var ?array $listing */
/** @var bool $editable */
/** @var array $photos */
/** @var array $form */
/** @var array $errors */
/** @var array $leaves */
/** @var array $towns */
/** @var ?array $moderation */
/** @var array $drafts */

$conditions = ['new', 'like_new', 'used'];
$empty = max(0, 3 - count($photos));
?>
<section class="wrap page-head">
    <p class="eyebrow">SELLER WORKSPACE / <?= $listing ? 'EDIT LISTING' : 'NEW LISTING' ?></p>
    <h1 class="h1">Give your next sale a head start.</h1>
    <p class="lead">Upload photos, add the details, and review AI suggestions before publishing.</p>
    <ol class="stepper" id="stepper">
        <li class="active" data-step="1">01 Photos &amp; details</li>
        <li data-step="2">02 Review suggestions</li>
        <li data-step="3">03 Submit for moderation</li>
    </ol>
</section>

<section class="wrap pad-bottom">
    <?php if ($moderation): ?>
        <div class="flash flash-info"><strong>The admin team asked for changes:</strong> <?= e($moderation['note'] ?: 'Please review your listing and submit it again.') ?></div>
    <?php endif; ?>
    <?php if ($listing && !$editable): ?>
        <div class="flash flash-info">This listing is <?= e(strtolower(listing_status_label($listing['status']))) ?> and can’t be edited. <a class="link" href="<?= e(url('listing.php?id=' . (int) $listing['id'])) ?>">View it</a> or <a class="link" href="<?= e(url('sell.php')) ?>">start a new listing</a>.</div>
    <?php endif; ?>
    <?php if ($listing && $listing['status'] === 'approved'): ?>
        <div class="flash flash-info">Editing a live listing sends it back for admin review and temporarily hides it from buyers.</div>
    <?php endif; ?>
    <?php if ($errors): ?>
        <div class="flash flash-error" role="alert"><ul><?php foreach ($errors as $er): ?><li><?= e($er) ?></li><?php endforeach; ?></ul></div>
    <?php endif; ?>

    <div class="panel top-gap">
        <div class="panel-head">
            <h2 class="h2">Your drafts</h2>
            <span class="small muted"><?= count($drafts) ?> saved</span>
        </div>
        <?php if ($drafts === []): ?>
            <p class="muted">Saved drafts will appear here so you can continue them later.</p>
        <?php else: ?>
            <?php foreach ($drafts as $draft): ?>
                <div class="row-item">
                    <div class="draft-summary">
                        <?php if (!empty($draft['cover'])): ?>
                            <img src="<?= e(url((string) $draft['cover'])) ?>" alt="">
                        <?php endif; ?>
                        <div>
                            <strong><?= e($draft['title'] !== '' ? $draft['title'] : 'Untitled draft') ?></strong><br>
                            <span class="small muted">
                                <?= $draft['price'] > 0 ? e(money($draft['price'])) . ' · ' : '' ?>
                                <?= e(town_label($draft['town_name'])) ?> · Updated <?= e(time_ago($draft['updated_at'])) ?>
                            </span>
                        </div>
                    </div>
                    <div class="btn-row">
                        <a class="btn btn-sm" href="<?= e(url('sell.php?id=' . (int) $draft['id'])) ?>"><?= (int) ($listing['id'] ?? 0) === (int) $draft['id'] ? 'Continue editing' : 'Resume draft' ?></a>
                        <form method="post" action="<?= e(url('sell.php')) ?>" class="inline-form" data-confirm="Discard this draft? Its saved details and photos will be permanently deleted.">
                            <?= csrf_field() ?>
                            <input type="hidden" name="id" value="<?= (int) $draft['id'] ?>">
                            <button class="btn btn-sm" type="submit" name="action" value="discard_draft">Discard</button>
                        </form>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <div class="sell-grid">
        <form class="panel form" id="sell-form" method="post" enctype="multipart/form-data" action="<?= e(url('sell.php')) ?>"
              data-endpoint="<?= e(url('api/ai-listing.php')) ?>" data-listing="<?= (int) ($listing['id'] ?? 0) ?>"
              data-existing="<?= count($photos) ?>" data-max="<?= (int) MAX_PHOTOS ?>">
            <?= csrf_field() ?>
            <?php if ($listing): ?><input type="hidden" name="id" value="<?= (int) $listing['id'] ?>"><?php endif; ?>
            <h2 class="h2">Product information</h2>

            <fieldset <?= $editable ? '' : 'disabled' ?> class="plain">
                <div class="dropzone">
                    <p class="h3">Add clear photos of your product</p>
                    <p class="small muted">Up to <?= (int) MAX_PHOTOS ?> photos · JPG or PNG · Maximum 5 MB each</p>
                    <p class="small muted">Selected photos are saved when you save the draft or submit the listing.</p>
                    <label class="btn">Upload photos
                        <input type="file" name="photos[]" id="photo-input" accept="image/jpeg,image/png" multiple hidden>
                    </label>
                    <p class="small bad" id="photo-msg" role="status" aria-live="polite" hidden></p>
                </div>

                <div class="photo-grid" id="photo-grid">
                    <?php foreach ($photos as $i => $p): ?>
                        <div class="tile">
                            <div class="photo"><img src="<?= e(url($p['file_path'])) ?>" alt="Photo <?= $i + 1 ?>"></div>
                            <div class="tile-meta">
                                <span>PHOTO <?= $i + 1 ?></span>
                                <?php if ($i === 0): ?><span class="muted">Cover photo</span>
                                <?php else: ?><label class="link-btn"><input type="radio" name="cover" value="<?= (int) $p['id'] ?>" hidden>Make cover</label><?php endif; ?>
                                <span class="tile-actions">
                                    <label class="link-btn">Replace<input type="file" name="replace[<?= (int) $p['id'] ?>]" accept="image/jpeg,image/png" hidden></label> /
                                    <label class="link-btn remove-toggle"><input type="checkbox" name="remove[]" value="<?= (int) $p['id'] ?>" hidden>Remove</label>
                                </span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                    <?php for ($n = 0; $n < $empty; $n++): $num = count($photos) + $n + 1; ?>
                        <div class="tile empty" data-empty>
                            <div class="photo"><span>PHOTO <?= $num ?></span></div>
                            <div class="tile-meta"><span class="muted"><?= $num === 1 ? 'Cover photo' : 'Optional' ?></span></div>
                        </div>
                    <?php endfor; ?>
                </div>

                <div class="field">
                    <label for="title">Name of your product</label>
                    <input id="title" name="title" maxlength="150" placeholder="Product name" value="<?= e($form['title']) ?>" required>
                </div>
                <div class="field-row">
                    <div class="field">
                        <label for="category_id">Category</label>
                        <select id="category_id" name="category_id">
                            <option value="">Select a category</option>
                            <?php foreach ($leaves as $c): ?>
                                <option value="<?= (int) $c['id'] ?>"<?= (int) $form['category_id'] === (int) $c['id'] ? ' selected' : '' ?>><?= e($c['label']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="field">
                        <label for="item_condition">Condition</label>
                        <select id="item_condition" name="item_condition">
                            <option value="">Select condition</option>
                            <?php foreach ($conditions as $c): ?>
                                <option value="<?= e($c) ?>"<?= $form['item_condition'] === $c ? ' selected' : '' ?>><?= e(condition_label($c)) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="field">
                    <label for="description">Description</label>
                    <textarea id="description" name="description" rows="3" maxlength="2000" placeholder="Lightly used. Describe what is included and mention any defects."><?= e($form['description']) ?></textarea>
                </div>
                <div class="field-row">
                    <div class="field">
                        <label for="price">Selling price (PHP)</label>
                        <input id="price" name="price" inputmode="decimal" placeholder="₱12,500" value="<?= e($form['price_raw']) ?>">
                    </div>
                    <div class="field">
                        <label for="stock">Stocks available</label>
                        <input id="stock" name="stock" type="number" min="0" max="1000000" step="1" value="<?= e($form['stock_raw']) ?>" required>
                        <span class="small muted">Set to 0 to mark the listing out of stock.</span>
                    </div>
                </div>
                <div class="field">
                    <label for="town_id">Pickup location</label>
                    <select id="town_id" name="town_id">
                        <?php foreach ($towns as $t): ?>
                            <option value="<?= (int) $t['id'] ?>"<?= (int) $form['town_id'] === (int) $t['id'] ? ' selected' : '' ?>><?= e(town_label($t['name'])) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <p class="small muted">Listing rules: Use your own photos, describe defects, and do not post prohibited items.</p>
                <div class="btn-row">
                    <?php if ($listing && $listing['status'] === 'approved'): ?>
                        <button class="btn btn-primary" type="submit" name="action" value="submit" data-confirm-click="Submit these changes for admin review? The live listing will be hidden until reviewed.">Save changes &amp; submit for review</button>
                    <?php else: ?>
                        <button class="btn" type="submit" name="action" value="save_draft">Save draft</button>
                        <button class="btn btn-primary" type="submit" name="action" value="submit" data-confirm-click="Submit this listing for admin review? Any photos marked for removal will also be removed.">Submit for review</button>
                    <?php endif; ?>
                    <?php if ($listing && $listing['status'] === 'draft'): ?>
                        <button class="btn" type="submit" name="action" value="discard_draft" formnovalidate data-confirm-click="Discard this draft? Its saved details and photos will be permanently deleted.">Discard draft</button>
                    <?php endif; ?>
                </div>
            </fieldset>
        </form>

        <aside class="panel panel-soft assistant" id="ai-panel">
            <h2 class="h2">Your AI selling assistant</h2>
            <p class="small muted">Suggestions are editable. You decide what goes into your listing.</p>

            <div class="ai-card" id="ai-cat">
                <h3 class="h3">01 &nbsp;Identify &amp; categorize</h3>
                <p class="ai-text" data-out>Add photos or a title and we will suggest a category.</p>
                <button type="button" class="btn" data-apply hidden>Apply category</button>
            </div>

            <div class="ai-card" id="ai-write">
                <h3 class="h3">02 &nbsp;Write a better listing</h3>
                <p class="muted small">Generate a title and description using your photos, condition, and product details.</p>
                <button type="button" class="btn" data-run>Generate title &amp; description</button>
                <p class="ai-text" data-out hidden></p>
                <ul class="ai-tips small muted" data-tips></ul>
                <button type="button" class="btn" data-apply hidden>Use generated text</button>
            </div>

            <div class="ai-card" id="ai-price">
                <h3 class="h3">03 &nbsp;Find a fair price</h3>
                <p class="small muted">Suggested range</p>
                <p class="ai-range" data-range>—</p>
                <p class="small muted" data-basis>Choose a category and add a title to see comparable local listings.</p>
                <button type="button" class="btn" data-apply hidden>Use</button>
            </div>

            <p class="small muted">After submission, an admin checks the listing against community guidelines. You’ll receive a status update.</p>
        </aside>
    </div>
</section>
