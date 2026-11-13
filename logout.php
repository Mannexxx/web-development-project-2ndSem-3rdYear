<?php

declare(strict_types=1);

require_once __DIR__ . '/config.php';

if (is_post() && csrf_valid()) {
    App\Service\AuthService::signOut();
    flash('success', 'You have been signed out.');
}
redirect('index.php');
