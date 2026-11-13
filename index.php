<?php

declare(strict_types=1);

require_once __DIR__ . '/config.php';

use App\Repository\CategoryRepository;
use App\Repository\ListingRepository;
use App\Repository\ProductViewRepository;
use App\Repository\TownRepository;
use App\Service\RecommendationService;

$categories = repo(CategoryRepository::class);
$towns = repo(TownRepository::class);
$listings = repo(ListingRepository::class);

const PER_PAGE = 12;

$priceRanges = [
    'u500' => [null, 500, 'Under ₱500'],
    '500-1000' => [500, 1000, '₱500 – ₱1,000'],
    '1000-5000' => [1000, 5000, '₱1,000 – ₱5,000'],
    '5000-10000' => [5000, 10000, '₱5,000 – ₱10,000'],
    '10000+' => [10000, null, '₱10,000 and up'],
];
$ratingOptions = ['4.5' => '4.5 & up', '4' => '4.0 & up', '3' => '3.0 & up'];

$q = mb_substr(trim((string) ($_GET['q'] ?? '')), 0, 100);
$categorySlug = preg_match('/^[a-z0-9\-]+$/', (string) ($_GET['category'] ?? '')) ? (string) $_GET['category'] : '';
$townId = (int) ($_GET['town'] ?? 0);
$priceKey = isset($priceRanges[$_GET['price'] ?? '']) ? (string) $_GET['price'] : '';
$condition = in_array($_GET['condition'] ?? '', ['new', 'like_new', 'used'], true) ? (string) $_GET['condition'] : '';
$ratingKey = isset($ratingOptions[$_GET['rating'] ?? '']) ? (string) $_GET['rating'] : '';
$sort = in_array($_GET['sort'] ?? '', ['newest', 'price_asc', 'price_desc'], true) ? (string) $_GET['sort'] : 'newest';
$page = max(1, (int) ($_GET['page'] ?? 1));

$activeCategory = $categorySlug !== '' ? $categories->findBySlug($categorySlug) : null;

$filters = ['q' => $q, 'town_id' => $townId ?: null, 'condition' => $condition, 'min_rating' => $ratingKey, 'sort' => $sort];
if ($activeCategory !== null) {
    $filters['category_ids'] = $categories->idsWithChildren((int) $activeCategory['id']);
}
if ($priceKey !== '') {
    [$min, $max] = $priceRanges[$priceKey];
    $filters['min_price'] = $min;
    $filters['max_price'] = $max;
}

$result = $listings->search($filters, $page, PER_PAGE);
$pages = max(1, (int) ceil($result['total'] / PER_PAGE));
if ($page > $pages) {
    $page = $pages;
    $result = $listings->search($filters, $page, PER_PAGE);
}

$me = current_user();
$recs = (new RecommendationService($listings, repo(ProductViewRepository::class)))
    ->forViewer($me, view_session_key(), array_map(static fn($i) => (int) $i['id'], $result['items']), 4);

render('home', [
    'topCategories' => $categories->topLevel(),
    'activeCategory' => $activeCategory,
    'towns' => $towns->all(),
    'priceRanges' => $priceRanges,
    'ratingOptions' => $ratingOptions,
    'result' => $result,
    'recs' => $recs,
    'page' => $page,
    'pages' => $pages,
    'params' => array_filter([
        'q' => $q, 'category' => $categorySlug, 'town' => $townId ?: '', 'price' => $priceKey,
        'condition' => $condition, 'rating' => $ratingKey, 'sort' => $sort === 'newest' ? '' : $sort,
    ], static fn($v) => $v !== '' && $v !== null),
    'sel' => ['q' => $q, 'category' => $categorySlug, 'town' => $townId, 'price' => $priceKey, 'condition' => $condition, 'rating' => $ratingKey, 'sort' => $sort],
], ['title' => 'Bantayan Marketplace · Your island. Your marketplace.', 'tab' => 'explore']);
