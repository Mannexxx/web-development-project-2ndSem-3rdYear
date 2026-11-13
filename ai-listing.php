<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/config.php';

use App\Repository\CategoryRepository;
use App\Repository\ListingRepository;
use App\Repository\TownRepository;
use App\Service\ListingAiService;

if (!is_post()) {
    json_response(['ok' => false, 'message' => 'Method not allowed.'], 405);
}
if (!csrf_valid()) {
    json_response(['ok' => false, 'message' => 'Your session expired. Please reload the page.'], 419);
}
$user = current_user();
if ($user === null || (!is_verified($user) && !is_admin($user))) {
    json_response(['ok' => false, 'message' => 'Please sign in with a verified profile.'], 401);
}

$in = json_input();
$action = (string) ($in['action'] ?? '');
$title = mb_substr(trim((string) ($in['title'] ?? '')), 0, 150);
$description = mb_substr(trim((string) ($in['description'] ?? '')), 0, 2000);
$condition = in_array($in['item_condition'] ?? '', ['new', 'like_new', 'used'], true) ? (string) $in['item_condition'] : null;
$categoryId = (int) ($in['category_id'] ?? 0) ?: null;
$townId = (int) ($in['town_id'] ?? 0) ?: null;
$listingId = (int) ($in['listing_id'] ?? 0) ?: null;
$files = array_slice(array_map('strval', (array) ($in['filenames'] ?? [])), 0, MAX_PHOTOS);

$ai = new ListingAiService(repo(CategoryRepository::class), repo(ListingRepository::class), repo(TownRepository::class));

switch ($action) {
    case 'categorize':
        json_response(['ok' => true] + $ai->categorize($title, $description, $files));
    case 'write':
        json_response($ai->write($title, $description, $condition, $townId, $categoryId));
    case 'price':
        json_response($ai->suggestPrice($categoryId, $title, $listingId));
    default:
        json_response(['ok' => false, 'message' => 'Unknown action.'], 400);
}
