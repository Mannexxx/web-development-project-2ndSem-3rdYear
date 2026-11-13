<?php /** @var array $listing */ ?>
<section class="wrap page-head">
    <p class="eyebrow">SELLER WORKSPACE / SUBMITTED</p>
    <h1 class="h1">Submitted for moderation.</h1>
    <ol class="stepper">
        <li>01 Photos &amp; details</li>
        <li>02 Review suggestions</li>
        <li class="active">03 Submit for moderation</li>
    </ol>
</section>
<section class="wrap pad-bottom">
    <div class="panel narrow">
        <h2 class="h2">“<?= e($listing['title']) ?>” is waiting for review</h2>
        <p class="muted">An admin will check it against the community guidelines. You’ll receive a status update in your account as soon as there is a decision. Until then only you and the admin team can see it.</p>
        <p class="btn-row">
            <a class="btn btn-primary" href="<?= e(url('account.php')) ?>">Go to my listings</a>
            <a class="btn" href="<?= e(url('sell.php')) ?>">Sell another product</a>
        </p>
    </div>
</section>
