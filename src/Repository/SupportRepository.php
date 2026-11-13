<?php

declare(strict_types=1);

namespace App\Repository;

final class SupportRepository extends Repository
{
    private const SELECT = 'SELECT t.id, t.user_id, t.category, t.subject, t.status, t.created_at, t.updated_at,
                    u.firstname AS user_firstname, u.lastname AS user_lastname, u.face_verified_at AS user_verified_at,
                    (SELECT m.body FROM support_messages m WHERE m.ticket_id = t.id ORDER BY m.id ASC LIMIT 1) AS first_body
             FROM support_tickets t JOIN users u ON u.id = t.user_id';

    public function create(int $userId, string $category, string $subject, string $body): int
    {
        $at = now();
        $id = $this->insert(
            "INSERT INTO support_tickets (user_id, category, subject, status, created_at, updated_at) VALUES (?, ?, ?, 'open', ?, ?)",
            [$userId, $category, $subject, $at, $at]
        );
        $this->addMessage($id, $userId, $body, false);
        return $id;
    }

    public function find(int $id): ?array
    {
        return $this->one(self::SELECT . ' WHERE t.id = ?', [$id]);
    }

    public function forUser(int $userId): array
    {
        return $this->all(self::SELECT . ' WHERE t.user_id = ? ORDER BY t.updated_at DESC, t.id DESC', [$userId]);
    }

    public function open(): array
    {
        return $this->all(self::SELECT . " WHERE t.status = 'open' ORDER BY t.created_at ASC, t.id ASC");
    }

    public function recentHandled(int $limit = 10): array
    {
        $limit = max(1, min(50, $limit));
        return $this->all(self::SELECT . " WHERE t.status <> 'open' ORDER BY t.updated_at DESC, t.id DESC LIMIT $limit");
    }

    public function countOpen(): int
    {
        return (int) $this->value("SELECT COUNT(*) FROM support_tickets WHERE status = 'open'");
    }

    public function messages(int $ticketId): array
    {
        return $this->all(
            'SELECT m.id, m.sender_id, m.body, m.created_at, u.firstname AS sender_firstname, u.lastname AS sender_lastname
             FROM support_messages m JOIN users u ON u.id = m.sender_id
             WHERE m.ticket_id = ? ORDER BY m.id ASC',
            [$ticketId]
        );
    }

    public function addMessage(int $ticketId, int $senderId, string $body, bool $touch = true): void
    {
        $this->run(
            'INSERT INTO support_messages (ticket_id, sender_id, body, created_at) VALUES (?, ?, ?, ?)',
            [$ticketId, $senderId, $body, now()]
        );
        if ($touch) {
            $this->run('UPDATE support_tickets SET updated_at = ? WHERE id = ?', [now(), $ticketId]);
        }
    }

    public function setStatus(int $ticketId, string $status): void
    {
        $this->run('UPDATE support_tickets SET status = ?, updated_at = ? WHERE id = ?', [$status, now(), $ticketId]);
    }
}
