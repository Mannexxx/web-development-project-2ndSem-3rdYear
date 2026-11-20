<?php
/** @var array|null $me */
/** @var array $layout */
/** @var int $unread */
$tab = $layout['tab'];
$tabs = [
    'explore' => ['Explore', 'index.php', '<path d="m3 10 9-7 9 7v10a1 1 0 0 1-1 1h-6v-7h-4v7H4a1 1 0 0 1-1-1V10Z"></path>'],
    'sell' => ['Sell', 'sell.php', '<path d="M12 5v14M5 12h14"></path><circle cx="12" cy="12" r="9"></circle>'],
    'messages' => ['Messages', 'messages.php', '<path d="M20 11.5a7.5 7.5 0 0 1-7.5 7.5H6l-3 2v-6a7.5 7.5 0 1 1 17-3.5Z"></path><path d="M8 10h8M8 14h5"></path>'],
    'purchases' => ['Purchases', $me ? 'purchases.php' : 'login.php', '<path d="M5 8h14l1 13H4L5 8Z"></path><path d="M9 9V6a3 3 0 0 1 6 0v3"></path>'],
    'profile' => ['Profile', $me ? 'account.php' : 'login.php', '<circle cx="12" cy="8" r="4"></circle><path d="M4 21a8 8 0 0 1 16 0"></path>'],
];
?>
<nav class="m-tabs" aria-label="Main">
    <?php foreach ($tabs as $key => [$label, $href, $icon]): ?>
        <a href="<?= e(url($href)) ?>" class="<?= $tab === $key ? 'active' : '' ?>"<?= $tab === $key ? ' aria-current="page"' : '' ?>>
            <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><?= $icon ?></svg>
            <span class="m-tab-label"><?= e($label) ?><?php if ($key === 'messages' && $unread > 0): ?> <span class="badge"><?= (int) $unread ?></span><?php endif; ?></span>
        </a>
    <?php endforeach; ?>
</nav>
