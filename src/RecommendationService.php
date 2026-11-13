<?php

declare(strict_types=1);

namespace App\Service;

use App\Repository\ListingRepository;
use App\Repository\ProductViewRepository;

/** "Picked for your next find": ranks live listings by what the visitor has been browsing. */
final class RecommendationService
{
    public function __construct(
        private readonly ListingRepository $listings,
        private readonly ProductViewRepository $views
    ) {
    }

    /**
     * @param int[] $preferAvoid listing ids already visible on the page (used only if enough others exist)
     * @return array{items: array, personalised: bool}
     */
    public function forViewer(?array $user, string $sessionKey, array $preferAvoid = [], int $limit = 4): array
    {
        $userId = $user ? (int) $user['id'] : null;
        $interest = $this->views->interest($userId, $sessionKey);

        $viewedIds = [];
        $leafs = [];
        $parents = [];
        $towns = [];
        foreach ($interest as $row) {
            $viewedIds[(int) $row['listing_id']] = true;
            if ($row['category_id']) {
                $leafs[(int) $row['category_id']] = ($leafs[(int) $row['category_id']] ?? 0) + 1;
            }
            if ($row['parent_id']) {
                $parents[(int) $row['parent_id']] = ($parents[(int) $row['parent_id']] ?? 0) + 1;
            }
            if ($row['town_id']) {
                $towns[(int) $row['town_id']] = true;
            }
        }
        if ($user) {
            $towns[(int) $user['town_id']] = true;
        }

        $filters = ['status' => 'approved', 'sort' => 'newest'];
        if ($userId) {
            $filters['exclude_seller_id'] = $userId;
        }
        $candidates = $this->listings->search($filters, 1, 60)['items'];

        $scored = [];
        foreach ($candidates as $i => $c) {
            $id = (int) $c['id'];
            if (isset($viewedIds[$id])) {
                continue;
            }
            $score = 0.0;
            if ($c['category_id'] && isset($leafs[(int) $c['category_id']])) {
                $score += 3;
            }
            if ($c['category_parent_id'] && isset($parents[(int) $c['category_parent_id']])) {
                $score += 2;
            }
            if (isset($towns[(int) $c['town_id']])) {
                $score += 1;
            }
            if ((int) $c['seller_verified'] === 1) {
                $score += 0.5;
            }
            if ($c['seller_rating'] !== null) {
                $score += ((float) $c['seller_rating']) / 10;
            }
            $score -= $i * 0.001; // newest first on ties
            $scored[] = ['score' => $score, 'item' => $c];
        }
        usort($scored, static fn($a, $b) => $b['score'] <=> $a['score']);

        $fresh = [];
        $overlap = [];
        foreach ($scored as $s) {
            if (in_array((int) $s['item']['id'], $preferAvoid, true)) {
                $overlap[] = $s['item'];
            } else {
                $fresh[] = $s['item'];
            }
        }
        $items = array_slice(array_merge($fresh, $overlap), 0, $limit);

        return ['items' => $items, 'personalised' => $interest !== []];
    }
}
