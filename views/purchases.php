<?php
/** @var array<string, array<int, array<string, mixed>>> $itemsByStatus */

$sections = [
    'to_receive' => 'To Receive',
    'to_review' => 'To Review',
    'completed' => 'Completed',
    'canceled' => 'Canceled',
];
?>
<section class="wrap page-head">
    <p class="eyebrow">BUYER WORKSPACE</p>
    <h1 class="h1">My Purchases</h1>
    <p class="lead">Coordinate local pickup with each seller. No online payment or delivery is collected by this site.</p>
    <nav class="chips" aria-label="Purchase sections" role="tablist">
        <?php foreach ($sections as $status => $label): ?>
            <a
                class="chip"
                id="<?= e($status) ?>-tab"
                href="#<?= e($status) ?>"
                role="tab"
                aria-controls="<?= e($status) ?>"
                aria-selected="false"
                tabindex="-1"
                data-purchase-tab
            ><?= e($label) ?> (<?= count($itemsByStatus[$status]) ?>)</a>
        <?php endforeach; ?>
    </nav>
</section>

<section class="wrap pad-bottom purchase-sections">
    <?php foreach ($sections as $status => $label): ?>
        <section
            class="panel purchase-section"
            id="<?= e($status) ?>"
            role="tabpanel"
            aria-labelledby="<?= e($status) ?>-tab"
            tabindex="0"
        >
            <h2 class="h2"><?= e($label) ?></h2>
            <?php if ($itemsByStatus[$status] === []): ?>
                <p class="muted top-gap-sm">No purchases here yet.</p>
            <?php else: ?>
                <?php foreach ($itemsByStatus[$status] as $purchase): ?>
                    <?php
                    $seller = trim($purchase['seller_firstname'] . ' ' . $purchase['seller_lastname']);
                    $conversationUrl = $purchase['conversation_id']
                        ? 'messages.php?c=' . (int) $purchase['conversation_id']
                        : 'listing.php?id=' . (int) $purchase['listing_id'];
                    ?>
                    <article class="row-item purchase-row">
                        <div>
                            <strong><?= e($purchase['listing_title']) ?></strong><br>
                            <span class="small"><?= e(money($purchase['unit_price'])) ?> · 1 item</span><br>
                            <span class="small muted">
                                <?= $status === 'to_review' || $status === 'completed' ? 'Bought from ' : 'Seller: ' ?><?= e($seller) ?>
                                · <?= e(date_pretty($purchase['created_at'])) ?>
                            </span>
                        </div>
                        <div class="btn-row">
                            <a class="btn btn-sm" href="<?= e(url('listing.php?id=' . (int) $purchase['listing_id'])) ?>">View listing</a>
                            <a class="btn btn-sm" href="<?= e(url($conversationUrl)) ?>">Message seller</a>
                            <?php if ($status === 'to_receive'): ?>
                                <form method="post" action="<?= e(url('purchases.php')) ?>" class="inline-form">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="purchase_id" value="<?= (int) $purchase['id'] ?>">
                                    <button class="btn btn-sm btn-primary" type="submit" name="action" value="received" data-confirm-click="Confirm you have received this item? You will then be able to review the seller.">Confirm received</button>
                                </form>
                                <form method="post" action="<?= e(url('purchases.php')) ?>" class="inline-form">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="purchase_id" value="<?= (int) $purchase['id'] ?>">
                                    <button class="btn btn-sm" type="submit" name="action" value="cancel" data-confirm-click="Cancel this order? Its reserved stock will be returned to the listing.">Cancel order</button>
                                </form>
                            <?php elseif ($status === 'to_review' && $purchase['conversation_id']): ?>
                                <a class="btn btn-sm btn-primary" href="<?= e(url(
                                    'messages.php?c=' . (int) $purchase['conversation_id']
                                    . '&deal=' . (int) $purchase['deal_id']
                                    . '&return=' . rawurlencode('purchases.php#to-review')
                                )) ?>">Rate seller</a>
                            <?php elseif ($status === 'completed'): ?>
                                <span class="tag">Reviewed</span>
                            <?php endif; ?>
                        </div>
                    </article>
                <?php endforeach; ?>
            <?php endif; ?>
        </section>
    <?php endforeach; ?>
</section>
