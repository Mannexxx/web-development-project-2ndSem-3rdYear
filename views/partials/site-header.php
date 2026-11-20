<?php
/** @var array|null $me */
/** @var array $layout */
/** @var int $unread */
$mh = $layout['mobile_header'];
?>
<header class="site-header<?= $mh ? ' has-appbar' : '' ?>">
    <div class="wrap header-row">
        <a class="brand brand-logo" href="<?= e(url('index.php')) ?>" aria-label="<?= e(APP_NAME) ?>">
            <img src="<?= e(asset('logo/bantayan-marketplace-logo.svg')) ?>?v=12" alt="" width="440" height="110">
        </a>
        <form class="header-search" method="get" action="<?= e(url('index.php')) ?>" role="search">
            <input type="search" name="q" value="<?= e($_GET['q'] ?? '') ?>" placeholder="Search products on Bantayan Island" aria-label="Search products">
        </form>
        <nav class="header-nav" aria-label="Account">
            <?php if ($me): ?>
                <a class="header-icon-link hide-mobile" href="<?= e(url('messages.php')) ?>" aria-label="Messages<?= $unread > 0 ? ' (' . (int) $unread . ' unread)' : '' ?>" title="Messages">
                    <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false">
                        <path d="M20 11.5a7.5 7.5 0 0 1-7.5 7.5H6l-3 2v-6a7.5 7.5 0 1 1 17-3.5Z"></path>
                        <path d="M8 10h8M8 14h5"></path>
                    </svg>
                    <span class="header-icon-label">Messages</span>
                    <?php if ($unread > 0): ?><span class="badge"><?= (int) $unread ?></span><?php endif; ?>
                </a>
                <a class="header-icon-link hide-mobile" href="<?= e(url('purchases.php')) ?>" aria-label="My Purchases" title="My Purchases">
                    <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false">
                        <path d="M5 8h14l1 13H4L5 8Z"></path>
                        <path d="M9 9V6a3 3 0 0 1 6 0v3"></path>
                    </svg>
                    <span class="header-icon-label">My Purchases</span>
                </a>
                <?php if (is_admin($me)): ?>
                    <a class="nav-link hide-mobile" href="<?= e(url('admin/index.php')) ?>">Admin</a>
                <?php endif; ?>
                <div class="profile-menu">
                    <button
                        class="profile-menu-trigger"
                        type="button"
                        aria-label="Open profile menu"
                        aria-haspopup="true"
                        aria-expanded="false"
                        aria-controls="header-profile-menu"
                        data-profile-menu-button
                    >
                        <span class="profile-menu-avatar">
                            <?php if (!empty($me['profile_pic'])): ?>
                                <img src="<?= e(url((string) $me['profile_pic'])) ?>" alt="">
                            <?php else: ?>
                                <?= e(mb_strtoupper(mb_substr((string) ($me['firstname'] ?? ''), 0, 1))) ?>
                            <?php endif; ?>
                        </span>
                    </button>
                    <div class="profile-menu-panel" id="header-profile-menu" role="menu" hidden>
                        <a href="<?= e(url('account.php')) ?>" role="menuitem">Profile</a>
                        <form class="inline-form" method="post" action="<?= e(url('logout.php')) ?>" data-confirm="Are you sure you want to log out?">
                            <?= csrf_field() ?>
                            <button type="submit" role="menuitem">Logout</button>
                        </form>
                    </div>
                </div>
                <a class="btn btn-primary btn-sm hide-mobile" href="<?= e(url('sell.php')) ?>">Sell a product</a>
            <?php else: ?>
                <a class="nav-link" href="<?= e(url('login.php')) ?>">Sign in<span class="hide-mobile"> / Register</span></a>
                <a class="btn btn-primary btn-sm hide-mobile" href="<?= e(url('sell.php')) ?>">Sell a product</a>
            <?php endif; ?>
        </nav>
    </div>
</header>
<?php if ($mh): ?>
    <div class="m-appbar">
        <a class="m-back" href="<?= e(url($mh['back'] ?? 'index.php')) ?>" aria-label="Back">‹</a>
        <span class="m-title"><?= e($mh['title'] ?? '') ?></span>
        <?php if (!empty($mh['share'])): ?>
            <button type="button" class="m-share" data-share>Share</button>
        <?php else: ?>
            <span></span>
        <?php endif; ?>
    </div>
<?php endif; ?>
