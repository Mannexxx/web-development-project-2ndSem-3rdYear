<?php
/** @var ?array $me */
/** @var array $ratings */
/** @var array $myListings */
/** @var array $deals */
/** @var array $tickets */
/** @var array $notifications */
/** @var int $unreadNotes */
?>
<section class="wrap page-head">
    <p class="eyebrow">YOUR PROFILE</p>
    <h1 class="h1"><?= e(full_name($me)) ?></h1>
    <p class="lead"><?= e($me['email']) ?> · <?= e(town_label($me['town_name'])) ?> · Member since <?= e(date_pretty($me['created_at'])) ?></p>
    <div class="btn-row">
        <?php if (is_verified($me)): ?><span class="tag">Face-verified</span>
        <?php elseif (!is_admin($me)): ?><a class="btn btn-primary" href="<?= e(url('register.php?step=verify')) ?>">Complete face verification</a><?php endif; ?>
        <?php if (is_admin($me)): ?><a class="btn" href="<?= e(url('admin/index.php')) ?>">Open admin workspace</a><?php endif; ?>
        <?php if ($me['status'] === 'restricted'): ?><span class="tag tag-bad">Account restricted</span><?php endif; ?>
        <form class="inline-form m-only" method="post" action="<?= e(url('logout.php')) ?>" data-confirm="Are you sure you want to log out?"><?= csrf_field() ?><button class="btn" type="submit">Logout</button></form>
    </div>
</section>

<section class="wrap pad-bottom account-grid">
    <div>
        <div class="panel">
            <div class="panel-head"><h2 class="h2">My listings</h2><a class="btn btn-primary btn-sm" href="<?= e(url('sell.php')) ?>">Sell a product</a></div>
            <?php if (!$myListings): ?>
                <p class="muted">You have not posted anything yet.</p>
            <?php endif; ?>
            <?php foreach ($myListings as $l): ?>
                <div class="row-item">
                    <div>
                        <strong><?= e($l['title'] ?: 'Untitled') ?></strong> · <?= e(money($l['price'])) ?><br>
                        <span class="small muted"><?= e(listing_status_label($l['status'])) ?></span>
                    </div>
                    <div class="btn-row">
                        <?php if (in_array($l['status'], ['draft', 'changes_requested'], true)): ?>
                            <a class="btn btn-sm" href="<?= e(url('sell.php?id=' . (int) $l['id'])) ?>">Edit</a>
                        <?php endif; ?>
                        <a class="btn btn-sm" href="<?= e(url('listing.php?id=' . (int) $l['id'])) ?>">View</a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="panel top-gap">
            <h2 class="h2">Deals &amp; ratings</h2>
            <p class="small muted">
                As a seller: <?= $ratings['seller']['count'] ? e(number_format((float) $ratings['seller']['avg'], 1)) . ' / 5 (' . (int) $ratings['seller']['count'] . ' reviews)' : 'no reviews yet' ?> ·
                As a buyer: <?= $ratings['buyer']['count'] ? e(number_format((float) $ratings['buyer']['avg'], 1)) . ' / 5 (' . (int) $ratings['buyer']['count'] . ' reviews)' : 'no reviews yet' ?>
            </p>
            <?php if (!$deals): ?><p class="muted">Completed deals will appear here so you can rate each other.</p><?php endif; ?>
            <?php foreach ($deals as $d): ?>
                <div class="row-item">
                    <div>
                        <strong><?= e($d['listing_title']) ?></strong><br>
                        <span class="small muted"><?= (int) $d['buyer_id'] === (int) $me['id'] ? 'Bought from' : 'Sold to' ?> <?= e(trim($d['other_firstname'] . ' ' . $d['other_lastname'])) ?> · <?= e(date_pretty($d['completed_at'])) ?></span>
                    </div>
                    <?php if ($d['conversation_id']): ?>
                        <a class="btn btn-sm" href="<?= e(url('messages.php?c=' . (int) $d['conversation_id'])) ?>"><?= (int) $d['reviewed'] > 0 ? 'View' : 'Rate' ?></a>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <div>
        <div class="panel panel-soft" id="notifications">
            <div class="panel-head">
                <h2 class="h2">Status updates<?php if ($unreadNotes > 0): ?> <span class="badge"><?= (int) $unreadNotes ?></span><?php endif; ?></h2>
                <?php if ($unreadNotes > 0): ?>
                    <form method="post" action="<?= e(url('account.php')) ?>"><?= csrf_field() ?><input type="hidden" name="action" value="read_notifications"><button class="btn btn-sm" type="submit" data-confirm-click="Mark all status updates as read?">Mark all read</button></form>
                <?php endif; ?>
            </div>
            <?php if (!$notifications): ?><p class="muted">Nothing new yet.</p><?php endif; ?>
            <?php foreach ($notifications as $n): ?>
                <div class="note<?= $n['read_at'] ? '' : ' unread' ?>">
                    <strong><?= e($n['title']) ?></strong>
                    <?php if ($n['body']): ?><p class="small"><?= e($n['body']) ?></p><?php endif; ?>
                    <p class="small muted"><?= e(time_ago($n['created_at'])) ?><?php if ($n['link']): ?> · <a class="link" href="<?= e(url($n['link'])) ?>">Open</a><?php endif; ?></p>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="panel top-gap">
            <div class="panel-head"><h2 class="h2">Customer service</h2><a class="btn btn-sm" href="<?= e(url('support.php')) ?>">Contact admin</a></div>
            <?php if (!$tickets): ?><p class="muted">No requests yet.</p><?php endif; ?>
            <?php foreach ($tickets as $t): ?>
                <div class="row-item">
                    <div><strong><?= e($t['subject']) ?></strong><br><span class="small muted"><?= e(ucfirst($t['status'])) ?> · <?= e(time_ago($t['updated_at'])) ?></span></div>
                    <a class="btn btn-sm" href="<?= e(url('support.php?t=' . (int) $t['id'])) ?>">Open</a>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
