<?php

declare(strict_types=1);

require_once __DIR__ . '/config.php';

use App\Repository\DealRepository;
use App\Repository\ListingRepository;
use App\Repository\NotificationRepository;
use App\Repository\SupportRepository;
use App\Repository\UserRepository;

$me = require_login('Please sign in to see your account.');
$uid = (int) $me['id'];
$notes = repo(NotificationRepository::class);

if (is_post()) {
    verify_csrf();
    if (($_POST['action'] ?? '') === 'read_notifications') {
        $notes->markAllRead($uid);
    }
    redirect('account.php#notifications');
}

$notifications = $notes->forUser($uid, 15);

render('account', [
    'ratings' => repo(UserRepository::class)->ratings($uid),
    'myListings' => repo(ListingRepository::class)->forSeller($uid),
    'deals' => repo(DealRepository::class)->forUser($uid),
    'tickets' => repo(SupportRepository::class)->forUser($uid),
    'notifications' => $notifications,
    'unreadNotes' => $notes->unreadCount($uid),
], ['title' => 'My account · ' . APP_NAME, 'tab' => 'profile']);
