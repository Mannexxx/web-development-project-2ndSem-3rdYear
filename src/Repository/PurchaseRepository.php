<?php

declare(strict_types=1);

namespace App\Repository;

use RuntimeException;
use Throwable;

final class PurchaseRepository extends Repository
{
    public function placeOrder(int $listingId, int $buyerId): int
    {
        $this->db->beginTransaction();
        try {
            $listing = $this->one(
                'SELECT id, seller_id, title, price, stock, status FROM listings WHERE id = ? FOR UPDATE',
                [$listingId]
            );
            if ($listing === null || $listing['status'] !== 'approved') {
                throw new RuntimeException('This listing is no longer available.');
            }
            if ((int) $listing['seller_id'] === $buyerId) {
                throw new RuntimeException('You cannot buy your own listing.');
            }
            if ((int) $listing['stock'] < 1) {
                throw new RuntimeException('This item is out of stock.');
            }

            if ($this->run('UPDATE listings SET stock = stock - 1 WHERE id = ? AND stock > 0', [$listingId]) !== 1) {
                throw new RuntimeException('This item is out of stock.');
            }
            $purchaseId = $this->insert(
                "INSERT INTO purchases (listing_id, buyer_id, seller_id, listing_title, unit_price, status)
                 VALUES (?, ?, ?, ?, ?, 'to_receive')",
                [$listingId, $buyerId, (int) $listing['seller_id'], $listing['title'], $listing['price']]
            );
            $this->db->commit();
            return $purchaseId;
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $e;
        }
    }

    /** @return array<int, array<string, mixed>> */
    public function forBuyer(int $buyerId): array
    {
        return $this->all(
            'SELECT p.id, p.listing_id, p.seller_id, p.listing_title, p.unit_price, p.status,
                    p.created_at, p.received_at, p.canceled_at,
                    s.firstname AS seller_firstname, s.lastname AS seller_lastname,
                    cv.id AS conversation_id,
                    d.id AS deal_id, rv.id AS review_id
             FROM purchases p
             JOIN users s ON s.id = p.seller_id
             LEFT JOIN conversations cv ON cv.listing_id = p.listing_id AND cv.buyer_id = p.buyer_id
             LEFT JOIN deals d ON d.purchase_id = p.id
             LEFT JOIN reviews rv ON rv.deal_id = d.id AND rv.reviewer_id = p.buyer_id
             WHERE p.buyer_id = ?
             ORDER BY p.created_at DESC, p.id DESC',
            [$buyerId]
        );
    }

    public function hasOrderForListingBuyer(int $listingId, int $buyerId): bool
    {
        return (int) $this->value(
            "SELECT COUNT(*) FROM purchases
             WHERE listing_id = ? AND buyer_id = ? AND status <> 'canceled'",
            [$listingId, $buyerId]
        ) > 0;
    }

    public function findForBuyer(int $purchaseId, int $buyerId): ?array
    {
        return $this->one(
            'SELECT id, listing_id FROM purchases WHERE id = ? AND buyer_id = ?',
            [$purchaseId, $buyerId]
        );
    }

    public function cancel(int $purchaseId, int $buyerId): void
    {
        $this->db->beginTransaction();
        try {
            $purchase = $this->one(
                'SELECT id, listing_id, status FROM purchases WHERE id = ? AND buyer_id = ? FOR UPDATE',
                [$purchaseId, $buyerId]
            );
            if ($purchase === null) {
                throw new RuntimeException('That purchase does not exist.');
            }
            if ($purchase['status'] !== 'to_receive') {
                throw new RuntimeException('Only purchases awaiting receipt can be canceled.');
            }

            $this->run('UPDATE listings SET stock = stock + 1 WHERE id = ?', [(int) $purchase['listing_id']]);
            $this->run(
                "UPDATE purchases SET status = 'canceled', canceled_at = ? WHERE id = ?",
                [now(), $purchaseId]
            );
            $this->db->commit();
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $e;
        }
    }

    public function markReceived(int $purchaseId, int $buyerId): int
    {
        $this->db->beginTransaction();
        try {
            $purchase = $this->one(
                'SELECT id, listing_id, buyer_id, seller_id, status FROM purchases WHERE id = ? AND buyer_id = ? FOR UPDATE',
                [$purchaseId, $buyerId]
            );
            if ($purchase === null) {
                throw new RuntimeException('That purchase does not exist.');
            }
            if ($purchase['status'] !== 'to_receive') {
                throw new RuntimeException('This purchase is not awaiting receipt.');
            }

            $dealId = $this->insert(
                'INSERT INTO deals (purchase_id, listing_id, buyer_id, seller_id) VALUES (?, ?, ?, ?)',
                [$purchaseId, (int) $purchase['listing_id'], (int) $purchase['buyer_id'], (int) $purchase['seller_id']]
            );
            $this->run(
                "UPDATE purchases SET status = 'to_review', received_at = ? WHERE id = ?",
                [now(), $purchaseId]
            );
            $this->db->commit();
            return $dealId;
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $e;
        }
    }
}
