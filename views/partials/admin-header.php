<header class="admin-header">
    <div class="wrap admin-row">
        <a class="admin-brand" href="<?= e(url('admin.php')) ?>">BANTAYAN MARKETPLACE</a>
        <div class="admin-nav">
            <span class="muted">Admin workspace</span>
            <a class="btn" href="<?= e(url('index.php')) ?>">View marketplace</a>
            <form class="inline-form" method="post" action="<?= e(url('logout.php')) ?>" data-confirm="Are you sure you want to log out of the admin workspace?">
                <?= csrf_field() ?>
                <button type="submit" class="btn">Logout</button>
            </form>
        </div>
    </div>
</header>
