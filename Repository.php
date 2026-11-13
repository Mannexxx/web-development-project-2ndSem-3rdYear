<?php

declare(strict_types=1);

namespace App\Repository;

use PDO;

/** Thin helper around PDO shared by all repositories. */
abstract class Repository
{
    public function __construct(protected PDO $db)
    {
    }

    /** @return array<int, array<string, mixed>> */
    protected function all(string $sql, array $params = []): array
    {
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    protected function one(string $sql, array $params = []): ?array
    {
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $row = $stmt->fetch();
        return $row === false ? null : $row;
    }

    protected function value(string $sql, array $params = []): mixed
    {
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $v = $stmt->fetchColumn();
        return $v === false ? null : $v;
    }

    /** Runs INSERT/UPDATE/DELETE and returns the number of affected rows. */
    protected function run(string $sql, array $params = []): int
    {
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->rowCount();
    }

    protected function insert(string $sql, array $params = []): int
    {
        $this->run($sql, $params);
        return (int) $this->db->lastInsertId();
    }

    /** Escapes % and _ for LIKE ... ESCAPE '!' */
    protected function like(string $term): string
    {
        return '%' . str_replace(['!', '%', '_'], ['!!', '!%', '!_'], $term) . '%';
    }
}
