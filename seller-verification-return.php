<?php

declare(strict_types=1);

require_once __DIR__ . '/config.php';

use App\Repository\SellerApplicationRepository;

$me = require_login();
$sessionId = trim((string) ($_GET['verificationSessionId'] ?? ''));
$application = repo(SellerApplicationRepository::class)->forUser((int) $me['id']);
if ($application === null || $sessionId === '' || !hash_equals((string) ($application['didit_session_id'] ?? ''), $sessionId)) {
    abort(404, 'That identity verification session could not be found.');
}

flash('info', 'You returned from Didit. Your verified result is being confirmed securely; refresh your seller application in a moment.');
redirect('seller-application.php');
