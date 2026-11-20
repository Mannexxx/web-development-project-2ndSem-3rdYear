<?php

declare(strict_types=1);

namespace App\Repository;

final class TownRepository extends Repository
{
    public function all(string $sql = 'SELECT id, name, slug FROM towns ORDER BY name', array $params = []): array
    {
        return parent::all($sql, $params);
    }

    public function find(int $id): ?array
    {
        return $this->one('SELECT id, name, slug FROM towns WHERE id = ?', [$id]);
    }

    public function findBySlug(string $slug): ?array
    {
        return $this->one('SELECT id, name, slug FROM towns WHERE slug = ?', [$slug]);
    }
}
