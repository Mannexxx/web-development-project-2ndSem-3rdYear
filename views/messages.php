<?php
/** @var array $list */
/** @var ?array $current */
/** @var array $messages */
/** @var ?array $deal */
/** @var ?array $myReview */
/** @var int $uid */

$isSeller = $current !== null && (int) $current['seller_id'] === $uid;
$otherName = $current === null ? '' : ($isSeller ? full_name($current, 'buyer_') : full_name($current, 'seller_'));
$otherFirst = $current === null ? '' : ($isSeller ? $current['buyer_firstname'] : $current['seller_firstname']);
?>
<section class="wrap page-head">
    <p class="eyebrow">YOUR INBOX</p>
    <h1 class="h1">Messages</h1>
</section>

<section class="wrap pad-bottom">
    <div class="inbox<?= $current ? ' has-thread' : '' ?>">
        <aside class="inbox-list panel">
            <?php if (!$list): ?>
                <p class="muted">No conversations yet. Open a listing and tap “Message seller”.</p>
                <a class="btn btn-primary" href="<?= e(url('index.php')) ?>">Browse listings</a>
            <?php endif; ?>
            <?php foreach ($list as $c):
                $mine = (int) $c['seller_id'] === $uid;
                $name = $mine ? full_name($c, 'buyer_') : full_name($c, 'seller_');
                ?>
                <a class="inbox-item<?= $current && (int) $current['id'] === (int) $c['id'] ? ' active' : '' ?>" href="<?= e(url('messages.php?c=' . (int) $c['id'])) ?>">
                    <span class="inbox-name"><?= e($name) ?><?php if ((int) $c['unread'] > 0): ?> <span class="badge"><?= (int) $c['unread'] ?></span><?php endif; ?></span>
                    <span class="inbox-listing"><?= e($c['listing_title']) ?> · <?= $mine ? 'You’re selling' : 'You’re buying' ?></span>
                    <span class="inbox-last muted small"><?= e(excerpt((string) ($c['last_body'] ?? 'No messages yet'), 60)) ?></span>
                </a>
            <?php endforeach; ?>
        </aside>

        <div class="thread panel">
            <?php if ($current === null): ?>
                <p class="muted">Select a conversation to read it.</p>
            <?php else: ?>
                <a class="m-only link" href="<?= e(url('messages.php')) ?>">‹ All messages</a>
                <div class="panel-head">
                    <h2 class="h2"><?= e($otherName) ?></h2>
                    <span class="eyebrow"><?= $isSeller ? 'BUYER' : 'SELLER' ?></span>
                </div>
                <p class="small muted">About <a class="link" href="<?= e(url('listing.php?id=' . (int) $current['listing_id'])) ?>"><?= e($current['listing_title']) ?></a> · <?= e(money($current['listing_price'])) ?></p>

                <?php foreach ($messages as $m): $mine = (int) $m['sender_id'] === $uid; ?>
                    <div class="bubble <?= $mine ? 'bubble-me' : 'bubble-them' ?>">
                        <span class="who"><?= $mine ? 'You · ' . ($isSeller ? 'Seller' : 'Buyer') : e($otherFirst) . ' · ' . ($isSeller ? 'Buyer' : 'Seller') ?> <span class="when"><?= e(time_ago($m['created_at'])) ?></span></span>
                        <?= nl2br(e($m['body'])) ?>
                    </div>
                <?php endforeach; ?>
                <?php if (!$messages): ?><p class="muted">No messages yet.</p><?php endif; ?>

                <form class="compose" method="post" action="<?= e(url('messages.php')) ?>">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="send">
                    <input type="hidden" name="conversation_id" value="<?= (int) $current['id'] ?>">
                    <input type="hidden" name="return" value="<?= e('messages.php?c=' . (int) $current['id']) ?>">
                    <div class="field grow">
                        <label for="body">Your message</label>
                        <input id="body" name="body" type="text" maxlength="2000" placeholder="Type a message…" autocomplete="off" required>
                    </div>
                    <button class="btn btn-primary" type="submit">Send</button>
                </form>

                <hr class="rule">
                <?php if ($deal !== null): ?>
                    <div class="done-box">
                        <p><strong>Deal completed</strong> · <?= e(date_pretty($deal['completed_at'])) ?></p>
                        <?php if ($myReview !== null): ?>
                            <p class="muted">You rated <?= e($otherFirst) ?> <span class="stars"><?= e(stars($myReview['rating'])) ?></span><?= $myReview['comment'] ? ' · “' . e($myReview['comment']) . '”' : '' ?></p>
                        <?php else: ?>
                            <form method="post" action="<?= e(url('messages.php')) ?>" class="form">
                                <?= csrf_field() ?>
                                <input type="hidden" name="action" value="review">
                                <input type="hidden" name="deal_id" value="<?= (int) $deal['id'] ?>">
                                <input type="hidden" name="return" value="<?= e('messages.php?c=' . (int) $current['id']) ?>">
                                <div class="field">
                                    <label for="rating">Rate <?= e($otherFirst) ?></label>
                                    <select id="rating" name="rating" required>
                                        <option value="">Choose a rating</option>
                                        <?php for ($i = 5; $i >= 1; $i--): ?><option value="<?= $i ?>"><?= $i ?> — <?= e(stars($i)) ?></option><?php endfor; ?>
                                    </select>
                                </div>
                                <div class="field">
                                    <label for="comment">Comment (optional)</label>
                                    <input id="comment" name="comment" maxlength="500" placeholder="How was the deal?">
                                </div>
                                <button class="btn btn-primary" type="submit" data-confirm-click="Submit this rating? You can only rate this completed deal once.">Submit rating</button>
                            </form>
                        <?php endif; ?>
                    </div>
                <?php elseif ($isSeller && $current['listing_status'] === 'approved'): ?>
                    <form method="post" action="<?= e(url('messages.php')) ?>" data-confirm="Mark “<?= e($current['listing_title']) ?>” as sold to <?= e($otherName) ?>? This closes the listing.">
                        <?= csrf_field() ?>
                        <input type="hidden" name="action" value="sold">
                        <input type="hidden" name="conversation_id" value="<?= (int) $current['id'] ?>">
                        <button class="btn" type="submit">Mark as sold to <?= e($otherFirst) ?></button>
                        <p class="small muted">After a completed deal, buyers and sellers can rate one another.</p>
                    </form>
                <?php elseif ($current['listing_status'] === 'sold'): ?>
                    <p class="small muted">This item was sold to someone else.</p>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
</section>
