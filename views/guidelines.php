<?php /** @var array $items */ ?>
<section class="wrap page-head">
    <p class="eyebrow">COMMUNITY</p>
    <h1 class="h1">Community guidelines</h1>
    <p class="lead">Simple rules that keep Bantayan Marketplace safe, honest and local.</p>
</section>
<section class="wrap pad-bottom page-narrow-left">
    <?php foreach (['everyone' => 'For everyone', 'sellers' => 'For sellers'] as $aud => $heading):
        $group = array_filter($items, static fn($g) => $g['audience'] === $aud); ?>
        <?php if ($group): ?>
            <h2 class="h2 top-gap" id="<?= e($aud) ?>"><?= e($heading) ?></h2>
            <?php foreach ($group as $g): ?>
                <div class="panel top-gap-sm"><h3 class="h3"><?= e($g['title']) ?></h3><p class="muted"><?= e($g['body']) ?></p></div>
            <?php endforeach; ?>
        <?php endif; ?>
    <?php endforeach; ?>
    <p class="top-gap">Repeated violations may lead to account restrictions. Seen something wrong? Use “Report listing” on the listing page.</p>
</section>
