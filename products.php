<?php

declare(strict_types=1);

// Compatibility entry point: the home page owns listing search and filters.
require_once __DIR__ . '/config.php';
$category = filter_input(INPUT_GET, 'category', FILTER_VALIDATE_INT);
$search = trim((string) ($_GET['search'] ?? ''));
$query = [];
if ($category !== false && $category !== null && $category > 0) {
    $query['category'] = (string) $category;
}
if ($search !== '') {
    $query['q'] = $search;
}
redirect('index.php' . ($query ? '?' . http_build_query($query) : ''));
