<?php

declare(strict_types=1);

namespace App\Repository;

/** Completed deals and the ratings that hang off them. */
final class DealRepository extends Repository
{
    public function create(int $listingId, int $buyerId, int $sellerId): int
    {
        return $this->insert(
            'INSERT INTO deals (listing_id, buyer_id, seller_id, completed_at) VALUES (?, ?, ?, ?)',
            [$listingId, $buyerId, $sellerId, now()]
        );
    }

    public function find(int $id): ?array
    {
        return $this->one('SELECT id, listing_id, buyer_id, seller_id, completed_at FROM deals WHERE id = ?', [$id]);
    }

    public function findByListing(int $listingId): ?array
    {
        return $this->one('SELECT id, listing_id, buyer_id, seller_id, completed_at FROM deals WHERE listing_id = ?', [$listingId]);
    }

    /** Deals a user took part in, with the other party and whether the user already rated. */
    public function forUser(int $userId): array
    {
        return $this->all(
            'SELECT d.id, d.listing_id, d.buyer_id, d.seller_id, d.completed_at,
                    l.title AS listing_title,
                    o.id AS other_id, o.firstname AS other_firstname, o.lastname AS other_lastname,
                    (SELECT COUNT(*) FROM reviews rv WHERE rv.deal_id = d.id AND rv.reviewer_id = ?) AS reviewed,
                    (SELECT cv.id FROM conversations cv WHERE cv.listing_id = d.listing_id AND cv.buyer_id = d.buyer_id) AS conversation_id
             FROM deals d
             JOIN listings l ON l.id = d.listing_id
             JOIN users o ON o.id = (CASE WHEN d.buyer_id = ? THEN d.seller_id ELSE d.buyer_id END)
             WHERE d.buyer_id = ? OR d.seller_id = ?
             ORDER BY d.completed_at DESC, d.id DESC',
            [$userId, $userId, $userId, $userId]
        );
    }

    public function reviewBy(int $dealId, int $reviewerId): ?array
    {
        return $this->one('SELECT id, rating, comment, created_at FROM reviews WHERE deal_id = ? AND reviewer_id = ?', [$dealId, $reviewerId]);
    }

    public function addReview(int $dealId, int $reviewerId, int $revieweeId, string $reviewerRole, int $rating, ?string $comment): int
    {
        return $this->insert(
            'INSERT INTO reviews (deal_id, reviewer_id, reviewee_id, reviewer_role, rating, comment, created_at) VALUES (?, ?, ?, ?, ?, ?, ?)',
            [$dealId, $reviewerId, $revieweeId, $reviewerRole, $rating, $comment !== '' ? $comment : null, now()]
        );
    }

    /**
     * Reviews a user RECEIVED while acting as $asRole ('seller' or 'buyer').
     * A seller's reviews were written by buyers, and vice versa.
     */
    public function reviewsFor(int $userId, string $asRole, int $limit = 20): array
    {
        $writtenBy = $asRole === 'seller' ? 'buyer' : 'seller';
        $limit = max(1, min(100, $limit));
        return $this->all(
            "SELECT rv.id, rv.rating, rv.comment, rv.created_at,
                    u.firstname AS reviewer_firstname, u.lastname AS reviewer_lastname,
                    l.title AS listing_title
             FROM reviews rv
             JOIN users u ON u.id = rv.reviewer_id
             JOIN deals d ON d.id = rv.deal_id
             JOIN listings l ON l.id = d.listing_id
             WHERE rv.reviewee_id = ? AND rv.reviewer_role = ?
             ORDER BY rv.created_at DESC, rv.id DESC
             LIMIT $limit",
            [$userId, $writtenBy]
        );
    }
}
