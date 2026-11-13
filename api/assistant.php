<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/config.php';

use App\Repository\CategoryRepository;
use App\Repository\GuidelineRepository;
use App\Repository\ListingRepository;
use App\Repository\ProductViewRepository;
use App\Repository\TownRepository;
use App\Repository\UserRepository;
use App\Service\AssistantService;
use App\Service\ListingAiService;
use App\Service\RecommendationService;

if (!is_post()) {
    json_response(['ok' => false, 'message' => 'Method not allowed.'], 405);
}
if (!csrf_valid()) {
    json_response(['ok' => false, 'message' => 'Your session expired. Please reload the page.'], 419);
}

$in = json_input();
$message = mb_substr(trim((string) ($in['message'] ?? '')), 0, 300);
$listingId = (int) ($in['listing_id'] ?? 0) ?: null;

$listings = repo(ListingRepository::class);
$assistant = new AssistantService(
    $listings,
    repo(UserRepository::class),
    repo(GuidelineRepository::class),
    new ListingAiService(repo(CategoryRepository::class), $listings, repo(TownRepository::class)),
    new RecommendationService($listings, repo(ProductViewRepository::class))
);

json_response(['ok' => true] + $assistant->reply($message, $listingId, current_user()));
