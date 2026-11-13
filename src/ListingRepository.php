<?php

declare(strict_types=1);

namespace App\Repository;

final class ListingRepository extends Repository
{
    private const LIST_SELECT = "SELECT l.id, l.seller_id, l.category_id, l.town_id, l.title, l.price, l.item_condition, l.status,
            l.published_at, l.created_at, l.updated_at,
            t.name AS town_name, c.name AS category_name, c.slug AS category_slug, c.parent_id AS category_parent_id,
            u.firstname AS seller_firstname, u.lastname AS seller_lastname,
            CASE WHEN u.face_verified_at IS NULL THEN 0 ELSE 1 END AS seller_verified,
            r.avg_rating AS seller_rating, COALESCE(r.review_count, 0) AS seller_review_count,
            (SELECT p.file_path FROM listing_photos p WHERE p.listing_id = l.id ORDER BY p.sort_order, p.id LIMIT 1) AS cover";

    private const LIST_FROM = "listings l
            JOIN users u ON u.id = l.seller_id
            JOIN towns t ON t.id = l.town_id
            LEFT JOIN categories c ON c.id = l.category_id
            LEFT JOIN v_user_ratings r ON r.user_id = u.id AND r.as_role = 'seller'";

    /* ---- browsing --------------------------------------------------------- */

    /**
     * Filters: q, category_ids[], town_id, min_price, max_price, condition,
     * min_rating, seller_id, exclude_seller_id, status (default approved), sort.
     *
     * @return array{items: array, total: int}
     */
    public function search(array $f, int $page = 1, int $perPage = 12): array
    {
        [$where, $params] = $this->buildWhere($f);

        $total = (int) $this->value('SELECT COUNT(*) FROM ' . self::LIST_FROM . ' WHERE ' . $where, $params);

        $order = match ($f['sort'] ?? 'newest') {
            'price_asc' => 'l.price ASC, l.id DESC',
            'price_desc' => 'l.price DESC, l.id DESC',
            default => 'l.published_at DESC, l.id DESC',
        };
        $perPage = max(1, min(100, $perPage));
        $offset = max(0, ($page - 1) * $perPage);

        $items = $this->all(
            self::LIST_SELECT . ' FROM ' . self::LIST_FROM . ' WHERE ' . $where
            . ' ORDER BY ' . $order . ' LIMIT ' . $perPage . ' OFFSET ' . $offset,
            $params
        );

        return ['items' => $items, 'total' => $total];
    }

    private function buildWhere(array $f): array
    {
        $where = ['l.status = ?'];
        $params = [$f['status'] ?? 'approved'];

        $q = trim((string) ($f['q'] ?? ''));
        if ($q !== '') {
            $words = array_slice(preg_split('/\s+/', $q) ?: [], 0, 5);
            foreach ($words as $word) {
                $where[] = "(l.title LIKE ? ESCAPE '!' OR l.description LIKE ? ESCAPE '!')";
                $like = $this->like($word);
                $params[] = $like;
                $params[] = $like;
            }
        }
        if (!empty($f['category_ids'])) {
            $marks = implode(',', array_fill(0, count($f['category_ids']), '?'));
            $where[] = "l.category_id IN ($marks)";
            foreach ($f['category_ids'] as $id) {
                $params[] = (int) $id;
            }
        }
        if (!empty($f['town_id'])) {
            $where[] = 'l.town_id = ?';
            $params[] = (int) $f['town_id'];
        }
        if (isset($f['min_price']) && $f['min_price'] !== null) {
            $where[] = 'l.price >= ?';
            $params[] = (float) $f['min_price'];
        }
        if (isset($f['max_price']) && $f['max_price'] !== null) {
            $where[] = 'l.price <= ?';
            $params[] = (float) $f['max_price'];
        }
        if (!empty($f['condition'])) {
            $where[] = 'l.item_condition = ?';
            $params[] = $f['condition'];
        }
        if (!empty($f['min_rating'])) {
            $where[] = 'r.avg_rating >= ?';
            $params[] = (float) $f['min_rating'];
        }
        if (!empty($f['seller_id'])) {
            $where[] = 'l.seller_id = ?';
            $params[] = (int) $f['seller_id'];
        }
        if (!empty($f['exclude_seller_id'])) {
            $where[] = 'l.seller_id <> ?';
            $params[] = (int) $f['exclude_seller_id'];
        }

        return [implode(' AND ', $where), $params];
    }

    /* ---- single listing ---------------------------------------------------- */

    public function find(int $id): ?array
    {
        return $this->one(
            "SELECT l.*, t.name AS town_name,
                    c.name AS category_name, c.slug AS category_slug, c.parent_id AS category_parent_id,
                    pc.name AS parent_name, pc.slug AS parent_slug,
                    u.firstname AS seller_firstname, u.lastname AS seller_lastname,
                    u.face_verified_at AS seller_verified_at, u.created_at AS seller_since,
                    u.violation_count AS seller_violations
             FROM listings l
             JOIN users u ON u.id = l.seller_id
             JOIN towns t ON t.id = l.town_id
             LEFT JOIN categories c ON c.id = l.category_id
             LEFT JOIN categories pc ON pc.id = c.parent_id
             WHERE l.id = ?",
            [$id]
        );
    }

    public function photos(int $listingId): array
    {
        return $this->all('SELECT id, file_path, sort_order FROM listing_photos WHERE listing_id = ? ORDER BY sort_order, id', [$listingId]);
    }

    public function photo(int $photoId): ?array
    {
        return $this->one('SELECT id, listing_id, file_path, sort_order FROM listing_photos WHERE id = ?', [$photoId]);
    }

    public function photoCount(int $listingId): int
    {
        return (int) $this->value('SELECT COUNT(*) FROM listing_photos WHERE listing_id = ?', [$listingId]);
    }

    public function addPhoto(int $listingId, string $path): int
    {
        $next = (int) $this->value('SELECT COALESCE(MAX(sort_order), -1) + 1 FROM listing_photos WHERE listing_id = ?', [$listingId]);
        return $this->insert('INSERT INTO listing_photos (listing_id, file_path, sort_order) VALUES (?, ?, ?)', [$listingId, $path, $next]);
    }

    public function replacePhotoPath(int $photoId, string $path): void
    {
        $this->run('UPDATE listing_photos SET file_path = ? WHERE id = ?', [$path, $photoId]);
    }

    public function removePhoto(int $photoId): void
    {
        $this->run('DELETE FROM listing_photos WHERE id = ?', [$photoId]);
    }

    public function makeCover(int $listingId, int $photoId): void
    {
        $this->run('UPDATE listing_photos SET sort_order = sort_order + 1 WHERE listing_id = ?', [$listingId]);
        $this->run('UPDATE listing_photos SET sort_order = 0 WHERE id = ? AND listing_id = ?', [$photoId, $listingId]);
    }

    /* ---- seller workspace ---------------------------------------------------- */

    public function forSeller(int $sellerId): array
    {
        return $this->all(
            self::LIST_SELECT . ' FROM ' . self::LIST_FROM . ' WHERE l.seller_id = ? ORDER BY l.created_at DESC, l.id DESC',
            [$sellerId]
        );
    }

    /** Creates (id = null) or updates a draft/edit. Returns the listing id. */
    public function save(?int $id, int $sellerId, array $d): int
    {
        $values = [
            $d['category_id'] ?: null,
            (int) $d['town_id'],
            $d['title'],
            $d['description'],
            $d['price'],
            $d['item_condition'] ?: null,
        ];
        if ($id === null) {
            return $this->insert(
                "INSERT INTO listings (category_id, town_id, title, description, price, item_condition, seller_id, status)
                 VALUES (?, ?, ?, ?, ?, ?, ?, 'draft')",
                [...$values, $sellerId]
            );
        }
        $this->run(
            'UPDATE listings SET category_id = ?, town_id = ?, title = ?, description = ?, price = ?, item_condition = ?
             WHERE id = ? AND seller_id = ?',
            [...$values, $id, $sellerId]
        );
        return $id;
    }

    public function submit(int $id): void
    {
        $this->run("UPDATE listings SET status = 'pending', submitted_at = ? WHERE id = ?", [now(), $id]);
    }

    public function approve(int $id): void
    {
        $this->run("UPDATE listings SET status = 'approved', published_at = ? WHERE id = ?", [now(), $id]);
    }

    public function setStatus(int $id, string $status): void
    {
        if ($status === 'sold') {
            $this->run("UPDATE listings SET status = 'sold', sold_at = ? WHERE id = ?", [now(), $id]);
            return;
        }
        $this->run('UPDATE listings SET status = ? WHERE id = ?', [$status, $id]);
    }

    /* ---- admin moderation ------------------------------------------------------- */

    public function pending(int $limit = 50): array
    {
        $limit = max(1, min(200, $limit));
        return $this->all(
            "SELECT l.id, l.title, l.price, l.status, l.submitted_at,
                    u.id AS seller_id, u.firstname AS seller_firstname, u.lastname AS seller_lastname,
                    CASE WHEN u.face_verified_at IS NULL THEN 0 ELSE 1 END AS seller_verified
             FROM listings l JOIN users u ON u.id = l.seller_id
             WHERE l.status = 'pending'
             ORDER BY l.submitted_at ASC, l.id ASC LIMIT $limit"
        );
    }

    public function countByStatus(string $status): int
    {
        return (int) $this->value('SELECT COUNT(*) FROM listings WHERE status = ?', [$status]);
    }

    public function addModeration(int $listingId, int $adminId, string $decision, array $checks, ?string $note): void
    {
        $this->run(
            'INSERT INTO listing_moderations (listing_id, admin_id, decision, original_photos, accurate_info, permitted_item, note)
             VALUES (?, ?, ?, ?, ?, ?, ?)',
            [
                $listingId, $adminId, $decision,
                !empty($checks['original_photos']) ? 1 : 0,
                !empty($checks['accurate_info']) ? 1 : 0,
                !empty($checks['permitted_item']) ? 1 : 0,
                $note !== null && $note !== '' ? $note : null,
            ]
        );
    }

    public function latestModeration(int $listingId): ?array
    {
        return $this->one(
            'SELECT decision, note, created_at FROM listing_moderations WHERE listing_id = ? ORDER BY id DESC LIMIT 1',
            [$listingId]
        );
    }

    /* ---- price comparison ---------------------------------------------------------- */

    /** Live and sold listings in a category: the "comparable local listings". */
    public function comparables(int $categoryId, ?int $excludeListingId = null): array
    {
        $sql = "SELECT id, title, price, item_condition FROM listings
                WHERE category_id = ? AND status IN ('approved', 'sold') AND price > 0";
        $params = [$categoryId];
        if ($excludeListingId !== null) {
            $sql .= ' AND id <> ?';
            $params[] = $excludeListingId;
        }
        return $this->all($sql, $params);
    }
}
