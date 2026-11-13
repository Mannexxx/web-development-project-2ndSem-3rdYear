<?php
/** @var array $seller */
/** @var array $ratings */
/** @var array $sellerReviews */
/** @var array $buyerReviews */
/** @var array $items */

$verified = !empty($seller['face_verified_at']);
$sr = $ratings['seller'];
$br = $ratings['buyer'];
?>
<section class="wrap page-head">
    <p class="eyebrow">MEMBER PROFILE</p>
    <h1 class="h1"><?= e(full_name($seller)) ?></h1>
    <p class="lead"><?= $verified ? 'Face verified' : 'Not face verified' ?> · <?= e(town_label($seller['town_name'])) ?> · Member since <?= e(date('Y', strtotime((string) $seller['created_at']))) ?></p>
</section>

<section class="wrap pad-bottom two-col">
    <div class="panel">
        <h2 class="h2">As a seller</h2>
        <?php if ($sr['count']): ?>
            <p class="rating-big"><span class="stars"><?= e(stars($sr['avg'])) ?></span> <strong><?= e(number_format((float) $sr['avg'], 1)) ?> / 5</strong></p>
            <p class="muted small"><?= (int) $sr['count'] ?> seller review<?= $sr['count'] === 1 ? '' : 's' ?></p>
        <?php else: ?><p class="muted">No seller reviews yet.</p><?php endif; ?>
        <?php foreach ($sellerReviews as $r): ?>
            <blockquote class="quote">
                <span class="stars"><?= e(stars($r['rating'])) ?></span> “<?= e($r['comment'] ?: 'Rated ' . $r['rating'] . ' out of 5.') ?>”
                <cite>— <?= e(short_name($r, 'reviewer_')) ?> · Verified transaction · <?= e($r['listing_title']) ?> · <?= e(date_pretty($r['created_at'])) ?></cite>
            </blockquote>
        <?php endforeach; ?>
    </div>
    <div class="panel">
        <h2 class="h2">As a buyer</h2>
        <?php if ($br['count']): ?>
            <p class="rating-big"><span class="stars"><?= e(stars($br['avg'])) ?></span> <strong><?= e(number_format((float) $br['avg'], 1)) ?> / 5</strong></p>
            <p class="muted small"><?= (int) $br['count'] ?> buyer review<?= $br['count'] === 1 ? '' : 's' ?></p>
        <?php else: ?><p class="muted">No buyer reviews yet.</p><?php endif; ?>
        <?php foreach ($buyerReviews as $r): ?>
            <blockquote class="quote">
                <span class="stars"><?= e(stars($r['rating'])) ?></span> “<?= e($r['comment'] ?: 'Rated ' . $r['rating'] . ' out of 5.') ?>”
                <cite>— <?= e(short_name($r, 'reviewer_')) ?> · Verified transaction · <?= e(date_pretty($r['created_at'])) ?></cite>
            </blockquote>
        <?php endforeach; ?>
        <p class="small muted top-gap">After a completed deal, buyers and sellers can rate one another.</p>
    </div>
</section>

<section class="wrap section pad-bottom">
    <h2 class="h2">Listings by <?= e($seller['firstname']) ?></h2>
    <?php if (!$items): ?><p class="muted">No live listings right now.</p><?php endif; ?>
    <div class="grid">
        <?php foreach ($items as $card): ?><?php include VIEW_PATH . '/partials/card.php'; ?><?php endforeach; ?>
    </div>
</section>
