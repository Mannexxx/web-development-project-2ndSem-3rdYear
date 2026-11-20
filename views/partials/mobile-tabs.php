<?php
/** @var array|null $me */
/** @var array $layout */
/** @var int $unread */
$tab = $layout['tab'];
$tabs = [
    'explore' => ['Explore', 'index.php'],
    'sell' => ['Sell', 'sell.php'],
    'messages' => ['Messages', 'messages.php'],
<<<<<<< HEAD
    'purchases' => ['Purchases', $me ? 'purchases.php' : 'login.php'],
=======
>>>>>>> 730317b9a6c05588ee6649cb65a4921ce2d6c073
    'profile' => ['Profile', $me ? 'account.php' : 'login.php'],
];
?>
<nav class="m-tabs" aria-label="Main">
    <?php foreach ($tabs as $key => [$label, $href]): ?>
        <a href="<?= e(url($href)) ?>" class="<?= $tab === $key ? 'active' : '' ?>"<?= $tab === $key ? ' aria-current="page"' : '' ?>>
            <?= e($label) ?><?php if ($key === 'messages' && $unread > 0): ?> <span class="badge"><?= (int) $unread ?></span><?php endif; ?>
        </a>
    <?php endforeach; ?>
</nav>
