<?php
/** @var array|null $me */
/** @var array $layout */
/** @var int $unread */
$mh = $layout['mobile_header'];
?>
<header class="site-header<?= $mh ? ' has-appbar' : '' ?>">
    <div class="wrap header-row">
<<<<<<< HEAD
        <a class="brand brand-logo" href="<?= e(url('index.php')) ?>" aria-label="<?= e(APP_NAME) ?>">
            <img src="<?= e(asset('logo/bantayan-marketplace-logo.svg')) ?>?v=12" alt="" width="440" height="110">
        </a>
=======
        <a class="brand" href="<?= e(url('index.php')) ?>">BANTAYAN<br>MARKETPLACE</a>
>>>>>>> 730317b9a6c05588ee6649cb65a4921ce2d6c073
        <form class="header-search" method="get" action="<?= e(url('index.php')) ?>" role="search">
            <input type="search" name="q" value="<?= e($_GET['q'] ?? '') ?>" placeholder="Search products on Bantayan Island" aria-label="Search products">
        </form>
        <nav class="header-nav" aria-label="Account">
            <?php if ($me): ?>
                <a class="nav-link hide-mobile" href="<?= e(url('messages.php')) ?>">Messages<?php if ($unread > 0): ?> <span class="badge"><?= (int) $unread ?></span><?php endif; ?></a>
<<<<<<< HEAD
                <a class="nav-link hide-mobile" href="<?= e(url('purchases.php')) ?>">My Purchases</a>
                <a class="nav-link hide-mobile" href="<?= e(url('account.php')) ?>">Account</a>
                <?php if (is_admin($me)): ?>
                    <a class="nav-link hide-mobile" href="<?= e(url('admin/index.php')) ?>">Admin</a>
=======
                <a class="nav-link hide-mobile" href="<?= e(url('account.php')) ?>">Account</a>
                <?php if (is_admin($me)): ?>
                    <a class="nav-link hide-mobile" href="<?= e(url('admin.php')) ?>">Admin</a>
>>>>>>> 730317b9a6c05588ee6649cb65a4921ce2d6c073
                <?php endif; ?>
                <form class="inline-form hide-mobile" method="post" action="<?= e(url('logout.php')) ?>" data-confirm="Are you sure you want to log out?">
                    <?= csrf_field() ?>
                    <button type="submit" class="nav-link nav-button">Logout</button>
                </form>
            <?php else: ?>
                <a class="nav-link" href="<?= e(url('login.php')) ?>">Sign in<span class="hide-mobile"> / Register</span></a>
            <?php endif; ?>
            <a class="btn btn-primary btn-sm hide-mobile" href="<?= e(url('sell.php')) ?>">Sell a product</a>
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
