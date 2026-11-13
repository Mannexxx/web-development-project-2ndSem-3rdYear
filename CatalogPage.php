<?php

declare(strict_types=1);

namespace App;

final class CatalogPage
{
    public function __construct(
        private readonly CategoryRepository $categoryRepository,
        private readonly ProductRepository $productRepository
    ) {
    }

    public function render(array $authData = []): void
    {
        $categories = $this->categoryRepository->findAll();
        $products = $this->productRepository->findFeatured(8);
        $user = $_SESSION['user'] ?? null;
        $preferredCategoryId = isset($_SESSION['preferred_category_id'])
            ? (int) $_SESSION['preferred_category_id']
            : null;
        // Signed-in members start with local listings; recent category browsing takes priority.
        $recommendations = $this->productRepository->findRecommendations(
            $preferredCategoryId,
            is_array($user) ? (string) ($user['town'] ?? '') : null,
            4
        );
        if ($recommendations === []) {
            $recommendations = array_slice($products, 0, 4);
        }
        $browsingUser = is_array($user) ? $user : null;

        extract($authData, EXTR_SKIP);

        require __DIR__ . '/../views/home.php';
    }
}
