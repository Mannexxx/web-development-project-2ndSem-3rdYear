<?php

declare(strict_types=1);

namespace App;

use PDO;

final class ProductRepository
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    public function findAll(): array
    {
        $stmt = $this->pdo->query("SELECT p.*, c.name AS category_name, u.verification_status = 'verified' AS is_verified, (SELECT image_path FROM product_images WHERE product_id=p.id ORDER BY sort_order,id LIMIT 1) AS image_path, (SELECT AVG(rating) FROM reviews WHERE reviewee_id=p.seller_id) AS seller_rating FROM products p LEFT JOIN categories c ON c.id=p.category_id LEFT JOIN users u ON u.id=p.seller_id WHERE p.status='active' ORDER BY p.created_at DESC");

        return $stmt->fetchAll();
    }

    public function findFeatured(int $limit = 8): array
    {
        $query = "SELECT p.*, c.name AS category_name, u.verification_status = 'verified' AS is_verified, (SELECT image_path FROM product_images WHERE product_id=p.id ORDER BY sort_order,id LIMIT 1) AS image_path, (SELECT AVG(rating) FROM reviews WHERE reviewee_id=p.seller_id) AS seller_rating FROM products p LEFT JOIN categories c ON c.id=p.category_id LEFT JOIN users u ON u.id=p.seller_id WHERE p.status='active' ORDER BY p.created_at DESC LIMIT " . max(1, $limit);
        $stmt = $this->pdo->query($query);

        return $stmt->fetchAll();
    }

    /** Return a small set tailored to the member's town or latest category visit. */
    public function findRecommendations(?int $categoryId, ?string $town, int $limit = 4): array
    {
        $where = "p.status='active'";
        $params = [];
        if ($categoryId !== null && $categoryId > 0) {
            $where .= ' AND p.category_id = ?';
            $params[] = $categoryId;
        } elseif (in_array($town, ['Bantayan', 'Santa Fe', 'Madridejos'], true)) {
            $where .= ' AND p.town = ?';
            $params[] = $town;
        }

        $sql = "SELECT p.*, c.name AS category_name, u.verification_status = 'verified' AS is_verified,
                    (SELECT image_path FROM product_images WHERE product_id=p.id ORDER BY sort_order,id LIMIT 1) AS image_path,
                    (SELECT AVG(rating) FROM reviews WHERE reviewee_id=p.seller_id) AS seller_rating
                FROM products p
                LEFT JOIN categories c ON c.id=p.category_id
                LEFT JOIN users u ON u.id=p.seller_id
                WHERE {$where} ORDER BY p.created_at DESC LIMIT " . max(1, $limit);
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function findByCategory(int $categoryId): array
    {
        $stmt = $this->pdo->prepare("SELECT p.*, c.name AS category_name, u.verification_status = 'verified' AS is_verified, (SELECT image_path FROM product_images WHERE product_id=p.id ORDER BY sort_order,id LIMIT 1) AS image_path, (SELECT AVG(rating) FROM reviews WHERE reviewee_id=p.seller_id) AS seller_rating FROM products p LEFT JOIN categories c ON c.id=p.category_id LEFT JOIN users u ON u.id=p.seller_id WHERE p.status='active' AND p.category_id = ? ORDER BY p.created_at DESC");
        $stmt->execute([$categoryId]);

        return $stmt->fetchAll();
    }

    public function findBySearch(string $query): array
    {
        $search = '%' . trim($query) . '%';
        $stmt = $this->pdo->prepare("SELECT p.*, c.name AS category_name, u.verification_status = 'verified' AS is_verified, (SELECT image_path FROM product_images WHERE product_id=p.id ORDER BY sort_order,id LIMIT 1) AS image_path, (SELECT AVG(rating) FROM reviews WHERE reviewee_id=p.seller_id) AS seller_rating FROM products p LEFT JOIN categories c ON c.id=p.category_id LEFT JOIN users u ON u.id=p.seller_id WHERE p.status='active' AND (p.title LIKE ? OR p.description LIKE ?) ORDER BY p.created_at DESC");
        $stmt->execute([$search, $search]);

        return $stmt->fetchAll();
    }

    // Return one public listing together with the seller's display information.
    public function findOne(int $id): ?array
    {
        $stmt = $this->pdo->prepare("SELECT p.*, c.name AS category_name, u.first_name, u.last_name, u.verification_status, (SELECT AVG(rating) FROM reviews WHERE reviewee_id=p.seller_id) AS seller_rating, (SELECT COUNT(*) FROM reviews WHERE reviewee_id=p.seller_id) AS review_count, (SELECT image_path FROM product_images WHERE product_id=p.id ORDER BY sort_order,id LIMIT 1) AS image_path FROM products p LEFT JOIN categories c ON c.id=p.category_id LEFT JOIN users u ON u.id=p.seller_id WHERE p.id=? AND p.status='active' LIMIT 1");
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }
}
