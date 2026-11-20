<?php

declare(strict_types=1);

namespace App\Repository;

final class ConversationRepository extends Repository
{
    public function find(int $id): ?array
    {
        return $this->one(
            'SELECT cv.id, cv.listing_id, cv.buyer_id, cv.created_at, cv.last_message_at,
                    l.title AS listing_title, l.status AS listing_status, l.price AS listing_price, l.seller_id,
                    s.firstname AS seller_firstname, s.lastname AS seller_lastname,
                    b.firstname AS buyer_firstname, b.lastname AS buyer_lastname
             FROM conversations cv
             JOIN listings l ON l.id = cv.listing_id
             JOIN users s ON s.id = l.seller_id
             JOIN users b ON b.id = cv.buyer_id
             WHERE cv.id = ?',
            [$id]
        );
    }

    public function findByListingBuyer(int $listingId, int $buyerId): ?array
    {
        $id = $this->value('SELECT id FROM conversations WHERE listing_id = ? AND buyer_id = ?', [$listingId, $buyerId]);
        return $id === null ? null : $this->find((int) $id);
    }

    public function create(int $listingId, int $buyerId): int
    {
        return $this->insert('INSERT INTO conversations (listing_id, buyer_id) VALUES (?, ?)', [$listingId, $buyerId]);
    }

    public function forUser(int $userId): array
    {
        return $this->all(
            'SELECT cv.id, cv.listing_id, cv.buyer_id, cv.last_message_at, cv.created_at,
                    l.title AS listing_title, l.seller_id,
                    s.firstname AS seller_firstname, s.lastname AS seller_lastname,
                    b.firstname AS buyer_firstname, b.lastname AS buyer_lastname,
                    (SELECT m.body FROM messages m WHERE m.conversation_id = cv.id ORDER BY m.id DESC LIMIT 1) AS last_body,
                    (SELECT COUNT(*) FROM messages m WHERE m.conversation_id = cv.id AND m.sender_id <> ? AND m.read_at IS NULL) AS unread
             FROM conversations cv
             JOIN listings l ON l.id = cv.listing_id
             JOIN users s ON s.id = l.seller_id
             JOIN users b ON b.id = cv.buyer_id
             WHERE cv.buyer_id = ? OR l.seller_id = ?
             ORDER BY COALESCE(cv.last_message_at, cv.created_at) DESC, cv.id DESC',
            [$userId, $userId, $userId]
        );
    }

    public function messages(int $conversationId): array
    {
        return $this->all(
            'SELECT id, sender_id, body, created_at FROM messages WHERE conversation_id = ? ORDER BY id ASC',
            [$conversationId]
        );
    }

    public function addMessage(int $conversationId, int $senderId, string $body): int
    {
        $at = now();
        $id = $this->insert(
            'INSERT INTO messages (conversation_id, sender_id, body, created_at) VALUES (?, ?, ?, ?)',
            [$conversationId, $senderId, $body, $at]
        );
        $this->run('UPDATE conversations SET last_message_at = ? WHERE id = ?', [$at, $conversationId]);
        return $id;
    }

    public function markRead(int $conversationId, int $userId): void
    {
        $this->run(
            'UPDATE messages SET read_at = ? WHERE conversation_id = ? AND sender_id <> ? AND read_at IS NULL',
            [now(), $conversationId, $userId]
        );
    }

    public function unreadCount(int $userId): int
    {
        return (int) $this->value(
            'SELECT COUNT(*) FROM messages m
             JOIN conversations cv ON cv.id = m.conversation_id
             JOIN listings l ON l.id = cv.listing_id
             WHERE m.sender_id <> ? AND m.read_at IS NULL AND (cv.buyer_id = ? OR l.seller_id = ?)',
            [$userId, $userId, $userId]
        );
    }
}
