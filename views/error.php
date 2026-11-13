<?php
/** @var int $code */
/** @var string $heading */
/** @var string $message */
?>
<section class="wrap page-narrow">
    <p class="eyebrow">Error <?= (int) $code ?></p>
    <h1 class="h1"><?= e($heading) ?></h1>
    <p class="lead"><?= e($message) ?></p>
    <p><a class="btn btn-primary" href="<?= e(url('index.php')) ?>">Back to the marketplace</a></p>
</section>
