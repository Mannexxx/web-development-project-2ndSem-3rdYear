<?php

declare(strict_types=1);

namespace App;

use PDO;

final class CategoryRepository
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    public function findAll(): array
    {
        $stmt = $this->pdo->query('SELECT * FROM categories ORDER BY name');

        return $stmt->fetchAll();
    }
}
