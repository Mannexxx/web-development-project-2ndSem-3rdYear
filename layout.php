<?php
/** @var array $layout */
/** @var string $content */
/** @var array|null $me */

use App\Repository\ConversationRepository;

$flashes = consume_flashes();
$unread = $me ? repo(ConversationRepository::class)->unreadCount((int) $me['id']) : 0;
$chrome = $layout['chrome'];
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf" content="<?= e(csrf_token()) ?>">
    <meta name="base" content="<?= e(BASE_PATH) ?>">
    <title><?= e($layout['title']) ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap">
    <link rel="stylesheet" href="<?= e(asset('css/app.css')) ?>">
</head>
<body class="chrome-<?= e($chrome) ?> <?= e($layout['body_class']) ?><?= $layout['hide_tabs'] ? '' : ' has-tabs' ?>">
<?php if ($chrome === 'admin'): ?>
    <?php require VIEW_PATH . '/partials/admin-header.php'; ?>
<?php else: ?>
    <?php require VIEW_PATH . '/partials/site-header.php'; ?>
<?php endif; ?>

<?php if ($flashes): ?>
    <div class="wrap flashes">
        <?php foreach ($flashes as $f): ?>
            <div class="flash flash-<?= e($f['type']) ?>" role="<?= $f['type'] === 'error' ? 'alert' : 'status' ?>">
                <?php if (count($f['messages']) === 1): ?>
                    <?= e($f['messages'][0]) ?>
                <?php else: ?>
                    <ul><?php foreach ($f['messages'] as $m): ?><li><?= e($m) ?></li><?php endforeach; ?></ul>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<main id="main"><?= $content ?></main>

<?php if ($chrome !== 'admin'): ?>
    <?php require VIEW_PATH . '/partials/footer.php'; ?>
    <?php if (!$layout['hide_tabs']): ?>
        <?php require VIEW_PATH . '/partials/mobile-tabs.php'; ?>
    <?php endif; ?>
<?php else: ?>
    <footer class="admin-footer"><div class="wrap">Admin actions are recorded. Apply community guidelines consistently and keep evidence private.</div></footer>
<?php endif; ?>

<script src="<?= e(asset('js/app.js')) ?>"></script>
<?php foreach ($layout['scripts'] as $script): ?>
    <script src="<?= e(asset('js/' . $script)) ?>"></script>
<?php endforeach; ?>
</body>
</html>
