<?php

declare(strict_types=1);

namespace App\Repository;

final class CategoryRepository extends Repository
{
    /** Top-level categories (Electronics, Home & living, ...). */
    public function topLevel(): array
    {
        return $this->all('SELECT id, name, slug FROM categories WHERE parent_id IS NULL ORDER BY sort_order, id');
    }

    public function find(int $id): ?array
    {
        return $this->one(
            'SELECT c.id, c.parent_id, c.name, c.slug, p.name AS parent_name, p.slug AS parent_slug
             FROM categories c LEFT JOIN categories p ON p.id = c.parent_id WHERE c.id = ?',
            [$id]
        );
    }

    public function findBySlug(string $slug): ?array
    {
        return $this->one(
            'SELECT c.id, c.parent_id, c.name, c.slug, p.name AS parent_name, p.slug AS parent_slug
             FROM categories c LEFT JOIN categories p ON p.id = c.parent_id WHERE c.slug = ?',
            [$slug]
        );
    }

    /** Sub-categories that listings can be posted into, labelled "Electronics / Mobile phones". */
    public function leaves(): array
    {
        $rows = $this->all(
            'SELECT c.id, c.name, c.slug, p.name AS parent_name, p.sort_order AS parent_order, c.sort_order
             FROM categories c JOIN categories p ON p.id = c.parent_id
             ORDER BY p.sort_order, c.sort_order, c.id'
        );
        foreach ($rows as &$row) {
            $row['label'] = $row['parent_name'] . ' / ' . $row['name'];
        }
        return $rows;
    }

    public function isLeaf(int $id): bool
    {
        return (int) $this->value('SELECT COUNT(*) FROM categories WHERE id = ? AND parent_id IS NOT NULL', [$id]) > 0;
    }

    /** IDs of a category plus its children (for "Electronics" filter). */
    public function idsWithChildren(int $id): array
    {
        $rows = $this->all('SELECT id FROM categories WHERE id = ? OR parent_id = ?', [$id, $id]);
        return array_map(static fn($r) => (int) $r['id'], $rows);
    }
}
