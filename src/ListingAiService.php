<?php

declare(strict_types=1);

namespace App\Service;

use App\Repository\CategoryRepository;
use App\Repository\ListingRepository;
use App\Repository\TownRepository;

/**
 * The "AI selling assistant". It runs locally (no external API or key needed):
 *  - categorize : keyword detection from the title, description and photo file names
 *  - write      : tidy title + description built only from what the seller typed
 *  - suggestPrice: range computed from comparable listings already on the marketplace
 */
final class ListingAiService
{
    /** [category slug, what we call it, keywords] */
    private const KEYWORDS = [
        ['mobile-phones', 'smartphone', ['phone', 'smartphone', 'cellphone', 'mobile', 'galaxy', 'iphone', 'samsung', 'xiaomi', 'redmi', 'oppo', 'vivo', 'realme', 'huawei', 'android', 'pixel', 'infinix', 'tecno']],
        ['computers-laptops', 'laptop or computer', ['laptop', 'notebook', 'macbook', 'thinkpad', 'computer', 'pc', 'desktop', 'monitor', 'keyboard', 'mouse', 'tablet', 'ipad']],
        ['audio', 'audio device', ['speaker', 'earbud', 'earphone', 'headphone', 'headset', 'airpods', 'soundbar', 'microphone', 'amplifier', 'bluetooth speaker']],
        ['cameras', 'camera', ['camera', 'dslr', 'gopro', 'mirrorless', 'lens', 'tripod']],
        ['furniture', 'furniture', ['table', 'desk', 'chair', 'sofa', 'cabinet', 'bed', 'shelf', 'bookshelf', 'wardrobe', 'dining', 'stool', 'drawer', 'couch', 'mattress']],
        ['kitchen-dining', 'kitchenware', ['pot', 'pan', 'kettle', 'blender', 'plate', 'utensil', 'cookware', 'rice cooker', 'cup', 'knife', 'wok']],
        ['appliances', 'appliance', ['fan', 'refrigerator', 'washing machine', 'aircon', 'air conditioner', 'oven', 'microwave', 'television', 'tv', 'iron', 'stove', 'freezer']],
        ['home-decor', 'home decor', ['decor', 'vase', 'frame', 'curtain', 'lamp', 'rug', 'mirror', 'clock', 'wall art']],
        ['clothing', 'clothing', ['shirt', 'tshirt', 'dress', 'jacket', 'pants', 'shorts', 'jeans', 'uniform', 'hoodie', 'polo', 'blouse', 'skirt', 'swimsuit']],
        ['shoes', 'shoes', ['shoe', 'sneaker', 'sandal', 'slipper', 'boot', 'heels', 'flipflop', 'rubber shoes']],
        ['bags-accessories', 'bag or accessory', ['bag', 'backpack', 'wallet', 'handbag', 'purse', 'sunglasses', 'watch', 'belt', 'cap', 'hat', 'jewelry', 'necklace', 'bracelet']],
        ['bicycles', 'bicycle', ['bicycle', 'bike', 'mtb', 'fixie', 'cycling', 'bmx']],
        ['water-sports', 'water sports gear', ['snorkel', 'snorkeling', 'mask', 'fin', 'kayak', 'paddle', 'surfboard', 'lifejacket', 'swimming', 'goggles', 'diving']],
        ['fishing-gear', 'fishing gear', ['fishing', 'rod', 'reel', 'net', 'tackle', 'bait', 'spear']],
        ['fitness', 'fitness equipment', ['dumbbell', 'barbell', 'yoga', 'treadmill', 'weights', 'kettlebell', 'gym', 'exercise']],
        ['handicrafts', 'handicraft', ['handwoven', 'woven', 'basket', 'handmade', 'craft', 'weaving', 'souvenir', 'banig', 'handicraft', 'native']],
        ['shellcraft', 'shellcraft', ['shell', 'shellcraft', 'seashell', 'capiz', 'pearl']],
        ['dried-seafood', 'dried seafood', ['danggit', 'dried fish', 'tuyo', 'dried squid', 'pusit', 'tinapa', 'dried seafood', 'daing', 'bulad']],
        ['food-delicacies', 'local delicacy', ['delicacy', 'delicacie', 'pasalubong', 'bibingka', 'biscocho', 'chip', 'sweet', 'coconut', 'honey', 'mango', 'vinegar', 'sauce']],
    ];

    private const STOPWORDS = ['the', 'and', 'for', 'with', 'new', 'used', 'like', 'brand', 'good', 'great', 'very', 'set', 'sale', 'pcs', 'pre', 'owned', 'from', 'only'];

    public function __construct(
        private readonly CategoryRepository $categories,
        private readonly ListingRepository $listings,
        private readonly TownRepository $towns
    ) {
    }

    /** @param string[] $fileNames names of the chosen photo files (they often hint at the item) */
    public function categorize(string $title, string $description, array $fileNames = []): array
    {
        $titleTokens = $this->prep($title);
        $descTokens = $this->prep($description);
        $fileTokens = $this->prep(implode(' ', array_map(
            static fn($n) => pathinfo((string) $n, PATHINFO_FILENAME),
            $fileNames
        )));

        if ($titleTokens['tokens'] === [] && $descTokens['tokens'] === [] && $fileTokens['tokens'] === []) {
            return ['found' => false, 'message' => 'Add photos or a title and we will suggest a category.'];
        }

        $best = null;
        $bestScore = 0;
        foreach (self::KEYWORDS as [$slug, $label, $keywords]) {
            $score = 0;
            foreach ($keywords as $kw) {
                if ($this->has($titleTokens, $kw)) {
                    $score += 3;
                }
                if ($this->has($descTokens, $kw)) {
                    $score += 1;
                }
                if ($this->has($fileTokens, $kw)) {
                    $score += 1;
                }
            }
            if ($score > $bestScore) {
                $bestScore = $score;
                $best = [$slug, $label];
            }
        }

        if ($best === null) {
            return ['found' => false, 'message' => 'We could not tell what this item is. Choose a category yourself.'];
        }
        $cat = $this->categories->findBySlug($best[0]);
        if ($cat === null) {
            return ['found' => false, 'message' => 'We could not match a category. Choose one yourself.'];
        }
        return [
            'found' => true,
            'detected' => $best[1],
            'category_id' => (int) $cat['id'],
            'category_label' => ($cat['parent_name'] ? $cat['parent_name'] . ' / ' : '') . $cat['name'],
        ];
    }

    /** Builds a clean title and description from what the seller has entered. */
    public function write(string $title, string $description, ?string $condition, ?int $townId, ?int $categoryId): array
    {
        $title = trim(preg_replace('/\s+/', ' ', $title) ?? '');
        if ($title === '') {
            return ['ok' => false, 'message' => 'Enter a product title first so we have something to improve.'];
        }
        $title = mb_strtoupper(mb_substr($title, 0, 1)) . mb_substr($title, 1);

        $condLabel = condition_label($condition);
        $coreTitle = trim(explode(' · ', $title)[0]);
        $newTitle = $title;
        if ($condLabel !== '' && stripos($title, $condLabel) === false) {
            $newTitle = $title . ' · ' . $condLabel;
        }
        $newTitle = mb_substr($newTitle, 0, 150);

        $parts = [];
        $parts[] = match ($condition) {
            'new' => 'Brand new ' . $coreTitle . '.',
            'like_new' => $coreTitle . ' in like-new condition.',
            'used' => $coreTitle . ' in used condition.',
            default => $coreTitle . '.',
        };
        $details = trim(preg_replace('/\s+/', ' ', $description) ?? '');
        $tips = [];
        if ($details !== '') {
            // Avoid repeating the opening sentence if the seller already wrote it.
            if (stripos($details, $coreTitle) !== 0) {
                $parts[] = rtrim($details, " .") . '.';
            } else {
                $parts = [rtrim($details, " .") . '.'];
            }
        }
        if (mb_strlen($details) < 40) {
            $tips[] = 'Add details buyers care about: size, color, what is included and any defects.';
        }
        $town = $townId ? $this->towns->find($townId) : null;
        if ($town !== null) {
            $parts[] = 'Available for local pickup in ' . $town['name'] . '.';
        }
        $parts[] = 'Message me with any questions.';
        $tips[] = 'Only keep statements that are true. Mention scratches, repairs or missing parts.';

        return [
            'ok' => true,
            'title' => $newTitle,
            'description' => implode(' ', $parts),
            'tips' => $tips,
        ];
    }

    /** Suggests a price range from comparable local listings. */
    public function suggestPrice(?int $categoryId, string $title, ?int $excludeListingId = null): array
    {
        if (!$categoryId) {
            return ['ok' => false, 'message' => 'Choose a category to see a suggested price.'];
        }
        $comps = $this->listings->comparables($categoryId, $excludeListingId);

        $words = array_slice(array_values(array_filter(
            $this->tokens($title),
            static fn($t) => strlen($t) >= 3 && !in_array($t, self::STOPWORDS, true)
        )), 0, 3);

        $matched = [];
        if ($words !== []) {
            $need = min(2, count($words));
            foreach ($comps as $c) {
                $ct = $this->tokens((string) $c['title']);
                $hits = count(array_intersect($words, $ct));
                if ($hits >= $need) {
                    $matched[] = (float) $c['price'];
                }
            }
        }
        $basis = 'similar items';
        $prices = $matched;
        if (count($prices) < 3) {
            $prices = array_map(static fn($c) => (float) $c['price'], $comps);
            $basis = 'this category';
        }
        if (count($prices) < 3) {
            return ['ok' => false, 'message' => 'Not enough comparable local listings yet to suggest a price. Check similar items and set your own price.'];
        }

        sort($prices);
        $n = count($prices);
        $median = $this->percentile($prices, 0.5);
        if ($n >= 5) {
            $low = $this->percentile($prices, 0.25);
            $high = $this->percentile($prices, 0.75);
        } else {
            $low = $median * 0.92;
            $high = $median * 1.08;
        }
        $low = $this->roundPrice($low);
        $high = $this->roundPrice($high);
        $suggested = $this->roundPrice($median);
        if ($high <= $low) {
            $low = $this->roundPrice($median * 0.92);
            $high = $this->roundPrice($median * 1.08);
        }

        return [
            'ok' => true,
            'low' => $low,
            'high' => $high,
            'suggested' => $suggested,
            'count' => $n,
            'basis' => $basis,
        ];
    }

    /* ---- helpers ------------------------------------------------------------ */

    /** Lower-case word list including simple singular forms ("shoes" -> "shoe"). */
    public function tokens(string $text): array
    {
        $text = mb_strtolower($text);
        preg_match_all('/[a-z0-9]+/', $text, $m);
        $out = [];
        foreach ($m[0] as $w) {
            $out[] = $w;
            if (strlen($w) > 3 && str_ends_with($w, 's') && !str_ends_with($w, 'ss')) {
                $out[] = substr($w, 0, -1);
            }
        }
        return array_values(array_unique($out));
    }

    /** @return array{tokens: string[], text: string} */
    private function prep(string $text): array
    {
        preg_match_all('/[a-z0-9]+/', mb_strtolower($text), $m);
        return ['tokens' => $this->tokens($text), 'text' => ' ' . implode(' ', $m[0]) . ' '];
    }

    /** @param array{tokens: string[], text: string} $prepared */
    private function has(array $prepared, string $keyword): bool
    {
        if (!str_contains($keyword, ' ')) {
            return in_array($keyword, $prepared['tokens'], true);
        }
        return str_contains($prepared['text'], ' ' . $keyword . ' ') || str_contains($prepared['text'], ' ' . $keyword . 's ');
    }

    private function percentile(array $sorted, float $p): float
    {
        $n = count($sorted);
        if ($n === 1) {
            return $sorted[0];
        }
        $pos = ($n - 1) * $p;
        $lo = (int) floor($pos);
        $hi = (int) ceil($pos);
        return $sorted[$lo] + ($sorted[$hi] - $sorted[$lo]) * ($pos - $lo);
    }

    private function roundPrice(float $value): int
    {
        $step = $value < 1000 ? 10 : 50;
        return (int) (round($value / $step) * $step);
    }
}
