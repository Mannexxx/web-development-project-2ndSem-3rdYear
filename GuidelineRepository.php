<?php

declare(strict_types=1);

namespace App\Repository;

final class GuidelineRepository extends Repository
{
    public function active(): array
    {
        return $this->all('SELECT id, code, title, body, audience FROM guidelines WHERE is_active = 1 ORDER BY sort_order, id');
    }

    public function allIncludingInactive(): array
    {
        return $this->all('SELECT id, code, title, body, audience, sort_order, is_active FROM guidelines ORDER BY sort_order, id');
    }

    public function find(int $id): ?array
    {
        return $this->one('SELECT id, code, title, body, audience, sort_order, is_active FROM guidelines WHERE id = ?', [$id]);
    }

    public function codeExists(string $code, ?int $exceptId = null): bool
    {
        return (int) $this->value('SELECT COUNT(*) FROM guidelines WHERE code = ? AND id <> ?', [$code, $exceptId ?? 0]) > 0;
    }

    public function save(?int $id, string $code, string $title, string $body, string $audience, int $sort, bool $active): int
    {
        if ($id === null) {
            return $this->insert(
                'INSERT INTO guidelines (code, title, body, audience, sort_order, is_active) VALUES (?, ?, ?, ?, ?, ?)',
                [$code, $title, $body, $audience, $sort, $active ? 1 : 0]
            );
        }
        $this->run(
            'UPDATE guidelines SET code = ?, title = ?, body = ?, audience = ?, sort_order = ?, is_active = ? WHERE id = ?',
            [$code, $title, $body, $audience, $sort, $active ? 1 : 0, $id]
        );
        return $id;
    }
}
