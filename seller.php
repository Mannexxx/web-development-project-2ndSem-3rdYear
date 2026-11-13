<?php

declare(strict_types=1);

require_once __DIR__ . '/config.php';

use App\Repository\DealRepository;
use App\Repository\ListingRepository;
use App\Repository\UserRepository;

$users = repo(UserRepository::class);
$seller = $users->find((int) ($_GET['id'] ?? 0));
if ($seller === null) {
    abort(404, 'That member does not exist.');
}
$sid = (int) $seller['id'];
$deals = repo(DealRepository::class);

render('seller', [
    'seller' => $seller,
    'ratings' => $users->ratings($sid),
    'sellerReviews' => $deals->reviewsFor($sid, 'seller', 20),
    'buyerReviews' => $deals->reviewsFor($sid, 'buyer', 10),
    'items' => repo(ListingRepository::class)->search(['seller_id' => $sid, 'status' => 'approved'], 1, 12)['items'],
], ['title' => full_name($seller) . ' · ' . APP_NAME, 'tab' => 'explore']);
