<?php
/** @var array $listing */
/** @var array $photos */
/** @var array $ratings */
/** @var ?array $latestReview */
/** @var string $state guest|unverified|owner|unavailable|ready */
/** @var bool $isOwner */
/** @var ?array $conversation */
/** @var array $messages */
/** @var array $reasons */
/** @var bool $alreadyReported */
/** @var ?array $me */

$sellerName = full_name($listing, 'seller_');
$sellerFirst = $listing['seller_firstname'];
$verified = !empty($listing['seller_verified_at']);
$sr = $ratings['seller'];
$br = $ratings['buyer'];
$self = 'listing.php?id=' . (int) $listing['id'];
$statusNote = match ($listing['status']) {
    'sold' => 'This item has been sold.',
    'pending' => 'This listing is waiting for admin review. Only you and the admin team can see it.',
    'draft' => 'This listing is a draft. Only you can see it.',
    'changes_requested' => 'The admin team asked for changes. Only you can see this listing.',
    'rejected' => 'This listing was rejected and is not public.',
    'removed' => 'This listing was removed for breaking the community guidelines.',
    default => '',
};
?>
<div class="wrap">
    <nav class="breadcrumb hide-mobile" aria-label="Breadcrumb">
        <a href="<?= e(url('index.php')) ?>">Marketplace</a>
        <?php if (!empty($listing['parent_name'])): ?> / <a href="<?= e(url('index.php?category=' . rawurlencode($listing['parent_slug']))) ?>"><?= e($listing['parent_name']) ?></a><?php endif; ?>
        <?php if (!empty($listing['category_name'])): ?> / <a href="<?= e(url('index.php?category=' . rawurlencode($listing['category_slug']))) ?>"><?= e($listing['category_name']) ?></a><?php endif; ?>
    </nav>
    <p class="m-crumb m-only"><?= e(trim(($listing['parent_name'] ?? '') . ' / ' . ($listing['category_name'] ?? ''), ' /')) ?></p>

    <?php if ($statusNote !== ''): ?>
        <div class="flash flash-info"><?= e($statusNote) ?></div>
    <?php endif; ?>

    <div class="detail-grid">
        <div class="gallery" data-gallery>
            <div class="gallery-main photo">
                <?php if ($photos): ?>
                    <img src="<?= e(url($photos[0]['file_path'])) ?>" alt="<?= e($listing['title']) ?>" data-gallery-main>
                <?php else: ?>
                    <span>PRODUCT PHOTO · 1 OF 1</span>
                <?php endif; ?>
            </div>
            <?php if (count($photos) > 1): ?>
                <div class="thumbs">
                    <?php foreach ($photos as $i => $p): ?>
                        <button type="button" class="thumb photo<?= $i === 0 ? ' active' : '' ?>" data-src="<?= e(url($p['file_path'])) ?>" aria-label="Photo <?= $i + 1 ?>">
                            <img src="<?= e(url($p['file_path'])) ?>" alt="" loading="lazy">
                            <span class="t-num"><?= sprintf('%02d', $i + 1) ?></span>
                        </button>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <div class="detail-info">
            <p class="eyebrow"><?= e(mb_strtoupper(trim(($listing['parent_name'] ?? 'Listing') . ' · ' . condition_label($listing['item_condition']), ' ·'))) ?></p>
            <h1 class="h1 detail-title"><?= e($listing['title']) ?></h1>
            <p class="price"><?= e(money($listing['price'])) ?></p>
            <p class="muted small"><?= e(town_label($listing['town_name'])) ?><?= $listing['published_at'] ? ' · Posted ' . e(time_ago($listing['published_at'])) : '' ?></p>
            <p class="desc"><?= nl2br(e($listing['description'])) ?></p>

            <div class="btn-row">
                <?php if ($verified): ?><span class="tag">Face-verified seller</span><?php endif; ?>
                <?php if (!$isOwner && $listing['status'] !== 'draft'): ?><a class="btn hide-mobile" href="#report">Report listing</a><?php endif; ?>
            </div>

            <div class="btn-row">
                <?php if ($state === 'guest'): ?>
                    <a class="btn btn-primary btn-mblock" href="<?= e(url('login.php?next=' . rawurlencode($self))) ?>">Message seller</a>
                <?php elseif ($state === 'unverified'): ?>
                    <a class="btn btn-primary btn-mblock" href="<?= e(url('register.php?step=verify&next=' . rawurlencode($self))) ?>">Message seller</a>
                <?php elseif ($state === 'owner'): ?>
                    <span class="btn btn-disabled btn-mblock">This is your listing</span>
                <?php elseif ($state === 'unavailable'): ?>
                    <span class="btn btn-disabled btn-mblock"><?= $listing['status'] === 'sold' ? 'Sold' : 'Not available' ?></span>
                <?php else: ?>
                    <a class="btn btn-primary btn-mblock" href="#message">Message seller</a>
                <?php endif; ?>
            </div>
            <?php if ($state === 'guest'): ?><p class="small muted">Sign in to message. Guests can browse all listings.</p><?php endif; ?>
            <?php if ($state === 'unverified'): ?><p class="small muted">Finish face verification to message sellers.</p><?php endif; ?>
            <p class="small muted">Meet locally in a public place. Inspect before paying.</p>
        </div>
    </div>
</div>

<section class="band">
    <div class="wrap two-col detail-cards">
        <div class="panel seller-card">
            <h2 class="h2 hide-mobile">Sold by <?= e($sellerName) ?></h2>
            <div class="seller-m m-only">
                <strong><?= e($sellerName) ?><?= $verified ? ' · Face verified' : '' ?></strong>
            </div>
            <p class="muted small hide-mobile"><?= $verified ? 'Face verified' : 'Not face verified' ?> · Member since <?= e(date('Y', strtotime((string) $listing['seller_since']))) ?></p>
            <?php if ($sr['count'] > 0): ?>
                <p class="rating-big hide-mobile"><span class="stars"><?= e(stars($sr['avg'])) ?></span> <strong><?= e(number_format((float) $sr['avg'], 1)) ?> / 5</strong></p>
                <p class="muted small hide-mobile"><?= (int) $sr['count'] ?> seller review<?= $sr['count'] === 1 ? '' : 's' ?><?= $br['count'] > 0 ? ' · ' . e(number_format((float) $br['avg'], 1)) . ' as a buyer (' . (int) $br['count'] . ' review' . ($br['count'] === 1 ? '' : 's') . ')' : '' ?></p>
                <p class="m-only">★ <?= e(number_format((float) $sr['avg'], 1)) ?> / 5 · <?= (int) $sr['count'] ?> seller reviews</p>
                <?php if ($br['count'] > 0): ?><p class="muted small m-only">Buyer rating: <?= e(number_format((float) $br['avg'], 1)) ?> / 5 · <?= (int) $br['count'] ?> reviews</p><?php endif; ?>
            <?php else: ?>
                <p class="muted">No reviews yet.</p>
            <?php endif; ?>
            <?php if ($latestReview): ?>
                <blockquote class="quote hide-mobile">
                    “<?= e($latestReview['comment'] ?: 'Rated ' . $latestReview['rating'] . ' out of 5.') ?>”
                    <cite>— <?= e(short_name($latestReview, 'reviewer_')) ?> · Verified transaction</cite>
                </blockquote>
            <?php endif; ?>
            <a class="btn" href="<?= e(url('seller.php?id=' . (int) $listing['seller_id'])) ?>">View profile &amp; reviews</a>
            <p class="small muted hide-mobile top-gap">After a completed deal, buyers and sellers can rate one another.</p>
        </div>

        <div class="panel message-panel" id="message">
            <div class="panel-head">
                <h2 class="h2"><span class="hide-mobile">Message <?= e($sellerFirst) ?></span><span class="m-only">Conversation with <?= e($sellerFirst) ?></span></h2>
                <?php if ($state === 'guest' || $state === 'unverified'): ?><span class="eyebrow">SIGNED-IN PREVIEW</span><?php endif; ?>
            </div>

            <?php if ($state === 'guest' || $state === 'unverified'): ?>
                <div class="bubble bubble-them"><span class="who"><?= e($sellerFirst) ?> · Seller</span>Hi! Yes, it’s still available. You can inspect it before buying.</div>
                <div class="bubble bubble-me"><span class="who">You · Buyer</span>Great! Could we meet near the town plaza?</div>
                <div class="compose">
                    <div class="field grow"><label>Your message</label><input type="text" placeholder="Type a message…" disabled></div>
                    <a class="btn btn-primary" href="<?= e($state === 'guest' ? url('login.php?next=' . rawurlencode($self)) : url('register.php?step=verify&next=' . rawurlencode($self))) ?>"><?= $state === 'guest' ? 'Sign in' : 'Verify' ?></a>
                </div>
            <?php elseif ($state === 'owner'): ?>
                <p class="muted">This is your listing. Buyers’ messages appear in your inbox.</p>
                <a class="btn" href="<?= e(url('messages.php')) ?>">Open my messages</a>
            <?php elseif ($state === 'unavailable'): ?>
                <p class="muted">Messaging is closed because this item is not available.</p>
            <?php else: ?>
                <?php if (!$messages): ?>
                    <p class="muted">Say hello to <?= e($sellerFirst) ?> and ask about the item, then agree on a public place to meet.</p>
                <?php endif; ?>
                <?php foreach ($messages as $m): $mine = (int) $m['sender_id'] === (int) $me['id']; ?>
                    <div class="bubble <?= $mine ? 'bubble-me' : 'bubble-them' ?>">
                        <span class="who"><?= $mine ? 'You · Buyer' : e($sellerFirst) . ' · Seller' ?> <span class="when"><?= e(time_ago($m['created_at'])) ?></span></span>
                        <?= nl2br(e($m['body'])) ?>
                    </div>
                <?php endforeach; ?>
                <form class="compose" method="post" action="<?= e(url('messages.php')) ?>">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="send">
                    <input type="hidden" name="listing_id" value="<?= (int) $listing['id'] ?>">
                    <input type="hidden" name="return" value="<?= e($self . '#message') ?>">
                    <div class="field grow">
                        <label for="msg-body">Your message</label>
                        <input id="msg-body" type="text" name="body" maxlength="2000" placeholder="Type a message…" autocomplete="off" required>
                    </div>
                    <button class="btn btn-primary" type="submit">Send</button>
                </form>
            <?php endif; ?>
        </div>
    </div>
</section>

<div class="wrap two-col lower-cards">
    <?php if (!$isOwner): ?>
        <div class="panel report-panel" id="report">
            <h2 class="h2">Report this listing</h2>
            <p class="muted small">Reports are reviewed privately by the admin team. <span class="m-only">Reports include a reason and evidence attachment.</span></p>
            <?php if ($me === null): ?>
                <p><a class="btn btn-primary" href="<?= e(url('login.php?next=' . rawurlencode($self))) ?>">Sign in to report</a></p>
            <?php elseif ($alreadyReported): ?>
                <p class="flash flash-info">You already reported this listing. The admin team will review it.</p>
            <?php else: ?>
                <form method="post" action="<?= e(url('report.php')) ?>" enctype="multipart/form-data" class="form" data-confirm="Submit this report to the admin team for review?">
                    <?= csrf_field() ?>
                    <input type="hidden" name="listing_id" value="<?= (int) $listing['id'] ?>">
                    <div class="field">
                        <label for="reason">Reason</label>
                        <select id="reason" name="reason" required>
                            <option value="">Select: misleading listing / prohibited item / other</option>
                            <?php foreach ($reasons as $r): ?><option value="<?= e($r) ?>"><?= e(reason_label($r)) ?></option><?php endforeach; ?>
                        </select>
                    </div>
                    <div class="field">
                        <label for="details">What happened?</label>
                        <textarea id="details" name="details" rows="3" maxlength="2000" placeholder="Describe your concern…" required></textarea>
                    </div>
                    <div class="field">
                        <label class="btn file-btn">
                            <span data-file-label>Attach evidence · JPG, PNG or PDF</span>
                            <input type="file" name="evidence[]" accept=".jpg,.jpeg,.png,.pdf,image/jpeg,image/png,application/pdf" multiple hidden data-file-input>
                        </label>
                        <p class="hint">Up to 3 files, 5 MB each.</p>
                    </div>
                    <button class="btn" type="submit">Submit report</button>
                </form>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <div class="panel panel-soft help-panel">
        <h2 class="h2 hide-mobile">Questions before you buy?</h2>
        <h2 class="h3 m-only">Shop safely, stay local.</h2>
        <p class="muted hide-mobile">Ask the AI assistant about this product, the seller, marketplace policies, or how buying works.</p>
        <p class="muted m-only">Meet in a public place and inspect before paying. Rate each other after a completed deal.</p>
        <a class="btn btn-mblock" href="<?= e(url('assistant.php?listing=' . (int) $listing['id'])) ?>"><span class="hide-mobile">Ask about this product</span><span class="m-only">Ask AI about this product</span></a>
        <h3 class="h3 top-gap hide-mobile">Need customer service?</h3>
        <p class="muted hide-mobile">Message the admin team for account or transaction concerns.</p>
        <p class="btn-row">
            <?php if (!$isOwner): ?><a class="btn m-only" href="#report">Report listing</a><?php endif; ?>
            <a class="btn" href="<?= e(url('support.php')) ?>"><span class="hide-mobile">Contact admin</span><span class="m-only">Contact admin</span></a>
        </p>
    </div>
</div>
