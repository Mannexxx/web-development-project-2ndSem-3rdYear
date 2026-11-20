<?php

declare(strict_types=1);

namespace App\Repository;

final class NotificationRepository extends Repository
{
    public function create(int $userId, string $type, string $title, ?string $body = null, ?string $link = null): void
    {
        $this->run(
            'INSERT INTO notifications (user_id, type, title, body, link, created_at) VALUES (?, ?, ?, ?, ?, ?)',
            [$userId, $type, $title, $body, $link, now()]
        );
    }

    public function forUser(int $userId, int $limit = 20): array
    {
        $limit = max(1, min(100, $limit));
        return $this->all(
            "SELECT id, type, title, body, link, read_at, created_at FROM notifications WHERE user_id = ? ORDER BY id DESC LIMIT $limit",
            [$userId]
        );
    }

    public function unreadCount(int $userId): int
    {
        return (int) $this->value('SELECT COUNT(*) FROM notifications WHERE user_id = ? AND read_at IS NULL', [$userId]);
    }

    public function markAllRead(int $userId): void
    {
        $this->run('UPDATE notifications SET read_at = ? WHERE user_id = ? AND read_at IS NULL', [now(), $userId]);
    }
}
