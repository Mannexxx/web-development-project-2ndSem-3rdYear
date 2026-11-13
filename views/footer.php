<?php
/** @var array $layout */
$footer = $layout['footer'] ?? [];
$text = $footer['text'] ?? 'Bantayan Marketplace · Local buying. Local selling.';
$links = $footer['links'] ?? [
    ['Community guidelines', 'guidelines.php'],
    ['Privacy', 'privacy.php'],
    ['Help', 'help.php'],
];
?>
<footer class="site-footer">
    <div class="wrap footer-row">
        <span><?= e($text) ?></span>
        <nav class="footer-links" aria-label="Footer">
            <?php foreach ($links as $i => [$label, $href]): ?>
                <?php if ($i > 0 && !empty($footer['slashes'])): ?><span class="sep">/</span><?php endif; ?>
                <a href="<?= e(url($href)) ?>"><?= e($label) ?></a>
            <?php endforeach; ?>
        </nav>
    </div>
</footer>
