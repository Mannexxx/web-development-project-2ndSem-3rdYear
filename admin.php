<?php

declare(strict_types=1);

// Compatibility entry point: the active admin workspace is in admin/index.php.
require_once __DIR__ . '/config.php';
redirect('admin/index.php');
