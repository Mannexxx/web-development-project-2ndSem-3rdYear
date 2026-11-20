<?php

declare(strict_types=1);

require_once __DIR__ . '/config.php';

use App\Repository\ListingRepository;

$listing = null;
if (isset($_GET['listing'])) {
    $found = repo(ListingRepository::class)->find((int) $_GET['listing']);
    if ($found !== null && in_array($found['status'], ['approved', 'sold'], true)) {
        $listing = $found;
    }
}
$ask = mb_substr(trim((string) ($_GET['ask'] ?? '')), 0, 200);

render('assistant', ['listing' => $listing, 'ask' => $ask], [
    'title' => 'AI assistant · ' . APP_NAME,
    'tab' => 'explore',
    'scripts' => ['assistant.js'],
]);
