<?php
/** @var ?array $me */
/** @var array $ratings */
/** @var array $myListings */
/** @var array $deals */
/** @var array $tickets */
/** @var array $notifications */
/** @var int $unreadNotes */
<<<<<<< HEAD
/** @var array $towns */
/** @var array $profileErrors */
/** @var string $section */
?>
<?php if ($section === 'edit-profile'): ?>
<section class="wrap page-head">
    <a class="link" href="<?= e(url('account.php')) ?>">‹ Back to account</a>
    <h1 class="h1 top-gap-sm">Edit profile</h1>
</section>
<section class="wrap pad-bottom">
    <div class="panel">
        <?php if ($profileErrors): ?>
            <div class="flash flash-error" role="alert"><ul><?php foreach ($profileErrors as $err): ?><li><?= e($err) ?></li><?php endforeach; ?></ul></div>
        <?php endif; ?>
        <form method="post" action="<?= e(url('account.php?section=edit-profile')) ?>" enctype="multipart/form-data" class="form">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="update_profile">
            <div class="profile-picture-field">
                <div class="profile-avatar profile-avatar-edit">
                    <?php if (!empty($me['profile_pic'])): ?>
                        <img src="<?= e(url((string) $me['profile_pic'])) ?>" alt="<?= e(full_name($me)) ?>">
                    <?php else: ?>
                        <span><?= e(mb_strtoupper(mb_substr((string) $me['firstname'], 0, 1))) ?></span>
                    <?php endif; ?>
                </div>
                <div class="field grow">
                    <label for="profile_picture">Profile picture</label>
                    <input id="profile_picture" class="file-btn" name="profile_picture" type="file" accept="image/jpeg,image/png">
                    <p class="hint">JPG or PNG, up to 5 MB.</p>
                </div>
            </div>
            <div class="field-row">
                <div class="field">
                    <label for="firstname">First name</label>
                    <input id="firstname" name="firstname" value="<?= e((string) $me['firstname']) ?>" autocomplete="given-name" required>
                </div>
                <div class="field">
                    <label for="lastname">Last name</label>
                    <input id="lastname" name="lastname" value="<?= e((string) $me['lastname']) ?>" autocomplete="family-name" required>
                </div>
            </div>
            <div class="field">
                <label for="username">Username</label>
                <input id="username" name="username" value="<?= e((string) ($me['username'] ?? '')) ?>" autocomplete="username" pattern="[A-Za-z0-9._]{3,40}" required>
                <p class="hint">Use this username or your email address to sign in.</p>
            </div>
            <div class="field">
                <label for="email">Email address</label>
                <input id="email" name="email" type="email" value="<?= e((string) $me['email']) ?>" autocomplete="email" required>
                <p class="hint">Changing your email requires verification after saving.</p>
            </div>
            <div class="field">
                <label for="town_id">Town on Bantayan Island</label>
                <select id="town_id" name="town_id" required>
                    <?php foreach ($towns as $town): ?>
                        <option value="<?= (int) $town['id'] ?>"<?= (int) $me['town_id'] === (int) $town['id'] ? ' selected' : '' ?>><?= e(town_label($town['name'])) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <button class="btn btn-primary" type="submit">Save profile</button>
        </form>
    </div>
</section>
<?php elseif ($section === 'settings' && !is_admin($me)): ?>
<section class="wrap page-head">
    <a class="link" href="<?= e(url('account.php')) ?>">‹ Back to account</a>
    <h1 class="h1 top-gap-sm">Account settings</h1>
</section>
<section class="wrap pad-bottom">
    <div class="panel">
        <h2 class="h2">Delete account</h2>
        <p class="small muted top-gap-sm">Deleting your account deactivates it and anonymizes your personal details. Your listings will be removed and their photos deleted. Completed deals, messages, reviews, reports, and support history will remain for marketplace records. You will be signed out, and this action cannot be undone.</p>
        <form method="post" action="<?= e(url('account.php?section=settings')) ?>" class="form" data-confirm="Delete your account? It will be deactivated, your personal details anonymized, your listings removed, and you will be signed out. This cannot be undone.">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="deactivate_account">
            <label class="check">
                <input type="checkbox" name="confirm_deactivation" value="1" required>
                <span>I understand that my account will be deactivated and cannot be restored.</span>
            </label>
            <button class="btn btn-danger" type="submit">Delete account</button>
        </form>
    </div>
</section>
<?php else: ?>
<section class="wrap page-head">
    <div class="account-heading">
        <div class="profile-avatar">
            <?php if (!empty($me['profile_pic'])): ?>
                <img src="<?= e(url((string) $me['profile_pic'])) ?>" alt="<?= e(full_name($me)) ?>">
            <?php else: ?>
                <span><?= e(mb_strtoupper(mb_substr((string) $me['firstname'], 0, 1))) ?></span>
            <?php endif; ?>
        </div>
        <div>
            <p class="eyebrow">YOUR PROFILE</p>
            <h1 class="h1"><?= e(full_name($me)) ?></h1>
            <p class="lead"><?= e($me['email']) ?> · <?= e(town_label($me['town_name'])) ?> · Member since <?= e(date_pretty($me['created_at'])) ?></p>
        </div>
    </div>
    <div class="btn-row">
        <a class="btn" href="<?= e(url('account.php?section=edit-profile')) ?>">Edit profile</a>
        <?php if (!is_admin($me)): ?><a class="btn" href="<?= e(url('account.php?section=settings')) ?>">Account settings</a><?php endif; ?>
=======
?>
<section class="wrap page-head">
    <p class="eyebrow">YOUR PROFILE</p>
    <h1 class="h1"><?= e(full_name($me)) ?></h1>
    <p class="lead"><?= e($me['email']) ?> · <?= e(town_label($me['town_name'])) ?> · Member since <?= e(date_pretty($me['created_at'])) ?></p>
    <div class="btn-row">
        <?php if (is_verified($me)): ?><span class="tag">Face-verified</span>
        <?php elseif (!is_admin($me)): ?><a class="btn btn-primary" href="<?= e(url('register.php?step=verify')) ?>">Complete face verification</a><?php endif; ?>
>>>>>>> 730317b9a6c05588ee6649cb65a4921ce2d6c073
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
<<<<<<< HEAD
                        <span class="small muted">
                            <?= $l['status'] === 'approved' && (int) $l['stock'] <= 0
                                ? 'Out of stock'
                                : e(listing_status_label($l['status']) . ' · ' . (int) $l['stock'] . ' in stock') ?>
                        </span>
                    </div>
                    <div class="btn-row">
                        <?php if (in_array($l['status'], ['draft', 'changes_requested', 'approved'], true)): ?>
=======
                        <span class="small muted"><?= e(listing_status_label($l['status'])) ?></span>
                    </div>
                    <div class="btn-row">
                        <?php if (in_array($l['status'], ['draft', 'changes_requested'], true)): ?>
>>>>>>> 730317b9a6c05588ee6649cb65a4921ce2d6c073
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
<<<<<<< HEAD
                        <a class="btn btn-sm" href="<?= e(url('messages.php?c=' . (int) $d['conversation_id'] . '&deal=' . (int) $d['id'])) ?>"><?= (int) $d['reviewed'] > 0 ? 'View' : 'Rate' ?></a>
=======
                        <a class="btn btn-sm" href="<?= e(url('messages.php?c=' . (int) $d['conversation_id'])) ?>"><?= (int) $d['reviewed'] > 0 ? 'View' : 'Rate' ?></a>
>>>>>>> 730317b9a6c05588ee6649cb65a4921ce2d6c073
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

<<<<<<< HEAD
        <?php if (!is_admin($me)): ?>
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
        <?php endif; ?>
    </div>
</section>
<?php endif; ?>
=======
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
>>>>>>> 730317b9a6c05588ee6649cb65a4921ce2d6c073
