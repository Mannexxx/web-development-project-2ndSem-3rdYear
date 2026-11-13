<?php

declare(strict_types=1);

namespace App\Repository;

/** Browsing history behind "Picked for your next find". */
final class ProductViewRepository extends Repository
{
    public function record(int $listingId, ?int $userId, string $sessionKey): void
    {
        $since = date('Y-m-d H:i:s', time() - 1800);
        $recent = (int) $this->value(
            'SELECT COUNT(*) FROM product_views WHERE listing_id = ? AND session_key = ? AND viewed_at >= ?',
            [$listingId, $sessionKey, $since]
        );
        if ($recent > 0) {
            return;
        }
        $this->run(
            'INSERT INTO product_views (listing_id, user_id, session_key, viewed_at) VALUES (?, ?, ?, ?)',
            [$listingId, $userId, $sessionKey, now()]
        );
    }

    /** Listings this person (or this guest session) looked at, newest first. */
    public function interest(?int $userId, string $sessionKey): array
    {
        return $this->all(
            'SELECT v.listing_id, l.category_id, c.parent_id AS parent_id, l.town_id, MAX(v.viewed_at) AS last_viewed
             FROM product_views v
             JOIN listings l ON l.id = v.listing_id
             LEFT JOIN categories c ON c.id = l.category_id
             WHERE v.session_key = ? OR v.user_id = ?
             GROUP BY v.listing_id, l.category_id, c.parent_id, l.town_id
             ORDER BY last_viewed DESC
             LIMIT 40',
            [$sessionKey, $userId ?? 0]
        );
    }
}
