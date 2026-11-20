<?php

declare(strict_types=1);

// Legacy report action. Use the canonical admin workspace for report decisions.
require_once __DIR__ . '/config.php';
redirect('admin/index.php?tab=reports');
