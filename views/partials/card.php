<?php
/** @var array $card a row from ListingRepository::search() */
$rating = [];
if ($card['seller_rating'] !== null) {
    $rating[] = '★ ' . number_format((float) $card['seller_rating'], 1);
} else {
    $rating[] = 'No reviews yet';
}
?>
<a class="card" href="<?= e(url('product.php?id=' . (int) $card['id'])) ?>">
    <div class="photo">
        <?php if (!empty($card['cover'])): ?>
            <img src="<?= e(url($card['cover'])) ?>" alt="" loading="lazy">
        <?php else: ?>
            <span>PRODUCT PHOTO</span>
        <?php endif; ?>
    </div>
    <h3 class="card-title"><?= e($card['title']) ?></h3>
    <p class="card-price"><?= e(money($card['price'])) ?></p>
    <p class="card-meta"><?= e($card['town_name']) ?><?= $card['item_condition'] ? ' · ' . e(condition_label($card['item_condition'])) : '' ?></p>
    <p class="card-rating"><?= e($rating[0]) ?><?php if ((int) $card['seller_verified'] === 1): ?> &nbsp;·&nbsp; Verified<span class="hide-mobile"> seller</span><?php endif; ?></p>
</a>
