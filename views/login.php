<?php
/** @var ?string $error */
/** @var string $email */
/** @var string $next */
?>
<section class="wrap page-head">
    <p class="eyebrow">WELCOME BACK</p>
    <h1 class="h1">Sign in to Bantayan Marketplace.</h1>
    <p class="lead">Message sellers, post products and leave ratings. You can always keep browsing as a guest.</p>
</section>
<section class="wrap pad-bottom">
    <div class="panel narrow">
        <h2 class="h2">Sign in</h2>
        <?php if ($error): ?><div class="flash flash-error" role="alert"><?= e($error) ?></div><?php endif; ?>
        <form method="post" action="<?= e(url('login.php')) ?>" class="form" novalidate>
            <?= csrf_field() ?>
            <input type="hidden" name="next" value="<?= e($next) ?>">
            <div class="field">
                <label for="email">Email address</label>
                <input id="email" name="email" type="email" autocomplete="email" placeholder="you@example.com" value="<?= e($email) ?>" required>
            </div>
            <div class="field">
                <label for="password">Password</label>
                <input id="password" name="password" type="password" autocomplete="current-password" placeholder="Your password" required>
            </div>
            <button class="btn btn-primary" type="submit">Sign in</button>
        </form>
        <p class="small muted top-gap">New here? <a class="link" href="<?= e(url('register.php' . ($next !== '' ? '?next=' . rawurlencode($next) : ''))) ?>">Create a profile</a></p>
        <p><a class="btn" href="<?= e(url('index.php')) ?>">Continue browsing as a guest</a></p>
    </div>
</section>
