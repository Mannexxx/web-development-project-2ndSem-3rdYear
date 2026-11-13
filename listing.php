<?php

declare(strict_types=1);

require_once __DIR__ . '/config.php';

use App\Repository\ConversationRepository;
use App\Repository\DealRepository;
use App\Repository\ListingRepository;
use App\Repository\ProductViewRepository;
use App\Repository\UserRepository;

$listings = repo(ListingRepository::class);
$id = (int) ($_GET['id'] ?? 0);
$listing = $id > 0 ? $listings->find($id) : null;
if ($listing === null) {
    abort(404, 'That listing does not exist.');
}

$me = current_user();
$isOwner = $me !== null && (int) $me['id'] === (int) $listing['seller_id'];
$isPublic = in_array($listing['status'], ['approved', 'sold'], true);
if (!$isPublic && !$isOwner && !is_admin($me)) {
    abort(404, 'That listing is not available.');
}

if ($listing['status'] === 'approved' && !$isOwner) {
    repo(ProductViewRepository::class)->record($id, $me ? (int) $me['id'] : null, view_session_key());
}

$users = repo(UserRepository::class);
$ratings = $users->ratings((int) $listing['seller_id']);
$deals = repo(DealRepository::class);
$latestReviews = $deals->reviewsFor((int) $listing['seller_id'], 'seller', 1);

// What the message panel should show
$state = 'ready';
if ($isOwner) {
    $state = 'owner';
} elseif ($listing['status'] !== 'approved') {
    $state = 'unavailable';
} elseif ($me === null) {
    $state = 'guest';
} elseif (!is_verified($me) && !is_admin($me)) {
    $state = 'unverified';
}

$conversation = null;
$messages = [];
if ($me !== null && !$isOwner && in_array($state, ['ready', 'unavailable'], true)) {
    $conversations = repo(ConversationRepository::class);
    $conversation = $conversations->findByListingBuyer($id, (int) $me['id']);
    if ($conversation !== null) {
        $messages = $conversations->messages((int) $conversation['id']);
        $conversations->markRead((int) $conversation['id'], (int) $me['id']);
        $state = 'ready';
    }
}

$reasons = ['misleading_listing', 'prohibited_item', 'other'];
$alreadyReported = $me !== null && !$isOwner && repo(App\Repository\ReportRepository::class)->hasOpenReport($id, (int) $me['id']);

render('listing', [
    'listing' => $listing,
    'photos' => $listings->photos($id),
    'ratings' => $ratings,
    'latestReview' => $latestReviews[0] ?? null,
    'state' => $state,
    'isOwner' => $isOwner,
    'conversation' => $conversation,
    'messages' => $messages,
    'reasons' => $reasons,
    'alreadyReported' => $alreadyReported,
], [
    'title' => $listing['title'] . ' · ' . APP_NAME,
    'tab' => 'explore',
    'mobile_header' => ['title' => 'Product details', 'back' => 'index.php', 'share' => true],
    'footer' => ['text' => 'Bantayan Marketplace', 'slashes' => true, 'links' => [['Community guidelines', 'guidelines.php'], ['Privacy', 'privacy.php'], ['Customer service', 'support.php']]],
]);
