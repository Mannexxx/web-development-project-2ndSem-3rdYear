<?php

declare(strict_types=1);

// Compatibility entry point: seller profiles live in seller.php.
require_once __DIR__ . '/config.php';
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
redirect('seller.php' . ($id !== false && $id !== null && $id > 0 ? '?id=' . $id : ''));
