<?php

declare(strict_types=1);

namespace App;

use PDO;

final class ProductRepository
{
    private const LIST_SELECT = "SELECT l.*, c.name AS category_name, t.name AS town,
        CASE WHEN u.face_verified_at IS NULL THEN 0 ELSE 1 END AS is_verified,
        CASE l.item_condition WHEN 'new' THEN 'New' WHEN 'like_new' THEN 'Like new' WHEN 'used' THEN 'Used' ELSE '' END AS condition_label,
        (SELECT p.file_path FROM listing_photos p WHERE p.listing_id=l.id ORDER BY p.sort_order,p.id LIMIT 1) AS image_path,
        (SELECT AVG(r.rating) FROM reviews r WHERE r.reviewee_id=l.seller_id AND r.reviewer_role='buyer') AS seller_rating
        FROM listings l
        LEFT JOIN categories c ON c.id=l.category_id
        LEFT JOIN towns t ON t.id=l.town_id
        LEFT JOIN users u ON u.id=l.seller_id";

    public function __construct(private readonly PDO $pdo)
    {
    }

    public function findAll(): array
    {
        $stmt = $this->pdo->query(self::LIST_SELECT . " WHERE l.status='approved' ORDER BY l.published_at DESC, l.id DESC");

        return $stmt->fetchAll();
    }

    public function findFeatured(int $limit = 8): array
    {
        $query = self::LIST_SELECT . " WHERE l.status='approved' ORDER BY l.published_at DESC, l.id DESC LIMIT " . max(1, $limit);
        $stmt = $this->pdo->query($query);

        return $stmt->fetchAll();
    }

    /** Return a small set tailored to the member's town or latest category visit. */
    public function findRecommendations(?int $categoryId, ?string $town, int $limit = 4): array
    {
        $where = "l.status='approved'";
        $params = [];
        if ($categoryId !== null && $categoryId > 0) {
            $where .= ' AND l.category_id = ?';
            $params[] = $categoryId;
        } elseif (in_array($town, ['Bantayan', 'Santa Fe', 'Madridejos'], true)) {
            $where .= ' AND t.name = ?';
            $params[] = $town;
        }

        $sql = self::LIST_SELECT . " WHERE {$where} ORDER BY l.published_at DESC, l.id DESC LIMIT " . max(1, $limit);
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function findByCategory(int $categoryId): array
    {
        $stmt = $this->pdo->prepare(self::LIST_SELECT . " WHERE l.status='approved' AND l.category_id = ? ORDER BY l.published_at DESC, l.id DESC");
        $stmt->execute([$categoryId]);

        return $stmt->fetchAll();
    }

    public function findBySearch(string $query): array
    {
        $search = '%' . trim($query) . '%';
        $stmt = $this->pdo->prepare(self::LIST_SELECT . " WHERE l.status='approved' AND (l.title LIKE ? OR l.description LIKE ?) ORDER BY l.published_at DESC, l.id DESC");
        $stmt->execute([$search, $search]);

        return $stmt->fetchAll();
    }

    // Return one public listing together with the seller's display information.
    public function findOne(int $id): ?array
    {
        $stmt = $this->pdo->prepare("SELECT l.*, c.name AS category_name, t.name AS town,
                    CASE l.item_condition WHEN 'new' THEN 'New' WHEN 'like_new' THEN 'Like new' WHEN 'used' THEN 'Used' ELSE '' END AS condition_label,
                    u.firstname AS first_name, u.lastname AS last_name,
                    CASE WHEN u.face_verified_at IS NULL THEN 'unverified' ELSE 'verified' END AS verification_status,
                    (SELECT AVG(r.rating) FROM reviews r WHERE r.reviewee_id=l.seller_id AND r.reviewer_role='buyer') AS seller_rating,
                    (SELECT COUNT(*) FROM reviews r WHERE r.reviewee_id=l.seller_id AND r.reviewer_role='buyer') AS review_count,
                    (SELECT p.file_path FROM listing_photos p WHERE p.listing_id=l.id ORDER BY p.sort_order,p.id LIMIT 1) AS image_path
                FROM listings l
                LEFT JOIN categories c ON c.id=l.category_id
                LEFT JOIN towns t ON t.id=l.town_id
                LEFT JOIN users u ON u.id=l.seller_id
                WHERE l.id=? AND l.status IN ('approved','sold') LIMIT 1");
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }
}
