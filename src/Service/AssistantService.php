<?php

declare(strict_types=1);

namespace App\Service;

use App\Repository\GuidelineRepository;
use App\Repository\ListingRepository;
use App\Repository\UserRepository;

/**
 * The marketplace's "Ask the AI assistant". It answers from real marketplace data
 * (listings, sellers, guidelines) plus how-to rules. It runs locally, so it needs no
 * API key. It never invents facts: when it does not know, it says so and points to admin.
 */
final class AssistantService
{
    public function __construct(
        private readonly ListingRepository $listings,
        private readonly UserRepository $users,
        private readonly GuidelineRepository $guidelines,
        private readonly ListingAiService $ai,
        private readonly RecommendationService $recommendations
    ) {
    }

    /** @return array{text: string, listings: array, links: array} */
    public function reply(string $message, ?int $listingId = null, ?array $viewer = null): array
    {
        $message = trim(preg_replace('/\s+/', ' ', $message) ?? '');
        $q = mb_strtolower($message);

        if ($q === '') {
            return $this->say('Ask me about a product, a seller, the marketplace rules, or how to buy and sell.');
        }

        if ($listingId !== null) {
            $listing = $this->listings->find($listingId);
            if ($listing !== null && in_array($listing['status'], ['approved', 'sold'], true)) {
                $answer = $this->aboutListing($listing, $q);
                if ($answer !== null) {
                    return $answer;
                }
            }
        }

        // How-to and policy questions
        if (preg_match('/^(hi|hello|hey|good (morning|afternoon|evening)|kumusta)\b/', $q) && mb_strlen($q) < 25) {
            return $this->say(
                "Hi! I can help you find products, explain how buying and selling work, and answer questions about marketplace rules. What would you like to know?",
                [], [['Browse listings', 'index.php']]
            );
        }
        if (preg_match('/\b(sell|selling|post|posting|list (my|an|a)|upload)\b/', $q) && !preg_match('/\b(seller|sold)\b/', $q)) {
            return $this->say(
                "To sell: 1) Sign in with a face-verified profile. 2) Tap \"Sell a product\" and upload up to 8 clear photos (JPG or PNG, max 5 MB each). "
                . "3) Add the title, category, condition, description, price and pickup location. The selling assistant can suggest a category, a better listing text and a fair price. "
                . "4) Submit for review. An admin checks it against the community guidelines and you receive a status update.",
                [], [['Sell a product', 'sell.php'], ['Seller guidelines', 'guidelines.php']]
            );
        }
        if (preg_match('/\b(register|sign ?up|create (an )?account|verify|verification|face|camera)\b/', $q)) {
            return $this->say(
                "Create your profile with your name, email, password and town. Then complete face verification: allow camera access, face the camera in good lighting and follow the on-screen instructions. "
                . "A verified profile lets you message sellers, sell, and leave ratings. You can always browse as a guest. If the camera step fails, contact customer service.",
                [], [['Create a profile', 'register.php'], ['Contact customer service', 'support.php?topic=face_verification']]
            );
        }
        if (preg_match('/\b(message|chat|contact (the )?seller|talk to (the )?seller)\b/', $q)) {
            return $this->say(
                "Open a listing and tap \"Message seller\". You need to be signed in with a verified profile. Conversations are kept under Messages so you can continue them any time.",
                [], [['My messages', 'messages.php']]
            );
        }
        if (preg_match('/\b(report|scam|fake|fraud|misleading)\b/', $q)) {
            return $this->say(
                "On the listing page, scroll to \"Report this listing\", choose a reason, describe what happened and optionally attach evidence (JPG, PNG or PDF). Reports are reviewed privately by the admin team.",
                [], [['Community guidelines', 'guidelines.php']]
            );
        }
        if (preg_match('/\b(rate|rating|ratings|review|reviews|feedback)\b/', $q)) {
            return $this->say(
                "After a completed deal, buyers and sellers can rate one another from 1 to 5 stars. A seller marks the item as sold to the buyer in the conversation, then both people can leave a rating. Only completed deals can be rated, so every review is a verified transaction.",
                [], [['My messages', 'messages.php']]
            );
        }
        if (preg_match('/\b(safe|safety|meet|meetup|pay|payment|paying|cod|gcash|deliver|delivery|shipping)\b/', $q)) {
            return $this->say(
                "Meet locally in a public place and inspect the item before paying. Bantayan Marketplace does not process payments or delivery in this version, so arrange payment in person. Never share one-time codes or send advance payments, and report anything suspicious.",
                [], [['Community guidelines', 'guidelines.php']]
            );
        }
        if (preg_match('/\b(fee|fees|commission|charge|free)\b/', $q)) {
            return $this->say("I don't have any information about fees. Please contact the admin team and they can confirm.", [], [['Contact customer service', 'support.php']]);
        }
        if (preg_match('/\b(policy|policies|rule|rules|prohibited|allowed|banned|guideline|guidelines|not allowed)\b/', $q)) {
            $items = $this->guidelines->active();
            $lines = [];
            foreach ($items as $g) {
                $lines[] = '• ' . $g['title'] . ': ' . $g['body'];
            }
            return $this->say(
                $lines === [] ? 'There are no published guidelines yet.' : "Here are the community guidelines:\n" . implode("\n", $lines),
                [], [['Community guidelines', 'guidelines.php']]
            );
        }
        if (preg_match('/\b(recommend|suggest|for me|picked|pick)\b/', $q)) {
            $recs = $this->recommendations->forViewer($viewer, view_session_key(), [], 4);
            if ($recs['items'] === []) {
                return $this->say('There is nothing to recommend yet. Check back when more listings are posted.');
            }
            return $this->say(
                $recs['personalised'] ? 'Based on what you have been browsing, you might like these:' : 'These are fresh listings from around the island:',
                array_map([$this, 'card'], $recs['items'])
            );
        }
        if (preg_match('/\b(admin|customer service|human|person|support|complain|complaint|account help|help me)\b/', $q)) {
            return $this->say(
                "You can message the admin team for account help or a marketplace concern. Sign in and open Customer service; you will see their reply in your account.",
                [], [['Contact customer service', 'support.php']]
            );
        }

        // Product search: "find a bicycle under 4000"
        $search = $this->searchFrom($q);
        if ($search !== null) {
            $result = $this->listings->search($search['filters'], 1, 4);
            if ($result['items'] !== []) {
                $count = $result['total'];
                return $this->say(
                    'I found ' . $count . ($count === 1 ? ' listing' : ' listings') . ' for "' . $search['term'] . '"'
                    . ($result['total'] > 4 ? ' (showing the newest 4):' : ':'),
                    array_map([$this, 'card'], $result['items']),
                    $result['total'] > 4 ? [['See all results', 'index.php?q=' . rawurlencode($search['term'])]] : []
                );
            }
            return $this->say(
                'I could not find any live listings for "' . $search['term'] . '"' . (isset($search['filters']['max_price']) ? ' in that price range' : '') . '. Try different words, or browse all listings.',
                [], [['Browse listings', 'index.php']]
            );
        }

        return $this->say(
            "I'm not sure about that. I can search listings (for example \"find a bicycle under 4000\"), explain how to buy, sell, register or report, and summarise the community guidelines. For anything else, contact the admin team.",
            [], [['Contact customer service', 'support.php']]
        );
    }

    /* ---- answers about one listing ---------------------------------------------- */

    private function aboutListing(array $l, string $q): ?array
    {
        $seller = trim($l['seller_firstname'] . ' ' . $l['seller_lastname']);
        $title = $l['title'];

        if (preg_match('/\b(available|still|sold)\b/', $q)) {
            return $l['status'] === 'sold'
                ? $this->say('"' . $title . '" has already been sold.', $this->similar($l))
                : $this->say('"' . $title . '" is currently listed as available. Message ' . $l['seller_firstname'] . ' to confirm and to agree a time and place to meet. I cannot see whether the seller has already promised it to someone.');
        }
        if (preg_match('/\b(price|cost|how much|fair|expensive|cheap|negotiat|discount|haggle|offer)\b/', $q)) {
            $text = 'The asking price is ' . money($l['price']) . '.';
            $s = $this->ai->suggestPrice($l['category_id'] ? (int) $l['category_id'] : null, $title, (int) $l['id']);
            if ($s['ok']) {
                $p = (float) $l['price'];
                $where = $p < $s['low'] ? 'below' : ($p > $s['high'] ? 'above' : 'within');
                $text .= ' Comparable local listings (' . $s['count'] . ') are typically ' . money($s['low']) . ' to ' . money($s['high'])
                    . ', so this price is ' . $where . ' that range.';
            } else {
                $text .= ' There are not enough similar local listings to compare it with.';
            }
            return $this->say($text . ' Whether the price is negotiable is up to the seller, so ask in a message.');
        }
        if (preg_match('/\b(seller|who|trust|trusted|rating|reviews?|verified|legit)\b/', $q)) {
            $r = $this->users->ratings((int) $l['seller_id']);
            $text = $seller . ' is a member since ' . date('Y', strtotime((string) $l['seller_since'])) . '. ';
            $text .= !empty($l['seller_verified_at']) ? 'Their profile is face-verified. ' : 'Their profile is not face-verified. ';
            $text .= $r['seller']['count'] > 0
                ? 'Rated ' . number_format((float) $r['seller']['avg'], 1) . ' / 5 from ' . $r['seller']['count'] . ' seller review' . ($r['seller']['count'] === 1 ? '' : 's') . '.'
                : 'They have no seller reviews yet.';
            return $this->say($text, [], [['View profile & reviews', 'profile.php?id=' . (int) $l['seller_id']]]);
        }
        if (preg_match('/\b(condition|used|defect|working|works|damage|scratch|quality)\b/', $q)) {
            return $this->say(
                'The seller lists the condition as "' . condition_label($l['item_condition']) . '". Description: ' . excerpt((string) $l['description'], 300)
                . ' If something is not mentioned, ask the seller, and inspect the item before paying.'
            );
        }
        if (preg_match('/\b(where|location|pickup|pick up|meet|town)\b/', $q)) {
            return $this->say(
                'Pickup is in ' . town_label($l['town_name']) . '. Agree on a public meeting place with the seller, and inspect the item before paying.'
            );
        }
        if (preg_match('/\b(similar|alternative|alternatives|other|more like|compare)\b/', $q)) {
            $similar = $this->similar($l);
            return $similar === []
                ? $this->say('I could not find similar listings right now.')
                : $this->say('Here are similar listings:', $similar);
        }
        if (preg_match('/\b(buy|buying|how does|how do|how to|order)\b/', $q)) {
            return $this->say(
                'To buy: sign in with a verified profile, tap "Message seller", agree on price, time and a public meeting place, inspect the item, then pay in person. '
                . 'Afterwards the seller marks it as sold and you can rate each other.'
            );
        }
        if (preg_match('/\b(this|it|product|item|tell|about|describe|summary)\b/', $q)) {
            return $this->say(
                $title . ' is listed at ' . money($l['price']) . ' (' . condition_label($l['item_condition']) . ') in ' . town_label($l['town_name']) . ' by ' . $seller . '. '
                . excerpt((string) $l['description'], 240)
            );
        }
        return null; // fall through to general answers
    }

    private function similar(array $l): array
    {
        if (empty($l['category_id'])) {
            return [];
        }
        $res = $this->listings->search(['category_ids' => [(int) $l['category_id']], 'sort' => 'newest'], 1, 5);
        $out = [];
        foreach ($res['items'] as $item) {
            if ((int) $item['id'] !== (int) $l['id']) {
                $out[] = $this->card($item);
            }
        }
        return array_slice($out, 0, 4);
    }

    /* ---- search ------------------------------------------------------------------- */

    private function searchFrom(string $q): ?array
    {
        $filters = ['sort' => 'newest'];

        if (preg_match('/(?:under|below|less than|max(?:imum)?|up to|not more than|within)\s*(?:₱|php|p)?\s*(\d[\d,]*)/', $q, $m)) {
            $filters['max_price'] = (float) str_replace(',', '', $m[1]);
            $q = str_replace($m[0], ' ', $q);
        } elseif (preg_match('/(?:over|above|more than|at least|min(?:imum)?)\s*(?:₱|php|p)?\s*(\d[\d,]*)/', $q, $m)) {
            $filters['min_price'] = (float) str_replace(',', '', $m[1]);
            $q = str_replace($m[0], ' ', $q);
        }

        $filler = ['find', 'me', 'a', 'an', 'the', 'some', 'any', 'show', 'search', 'for', 'looking', 'look', 'i', 'am', 'want', 'need', 'to', 'buy', 'get',
            'please', 'do', 'you', 'have', 'there', 'is', 'are', 'on', 'in', 'island', 'bantayan', 'marketplace', 'listings', 'listing', 'products', 'product', 'can', 'could', 'what', 'about', 'with', 'cheap', 'good'];
        preg_match_all('/[\p{L}\p{N}]+/u', $q, $m);
        $words = array_values(array_filter($m[0], static fn($w) => !in_array($w, $filler, true) && mb_strlen($w) > 1));
        if ($words === [] || count($words) > 5) {
            return null;
        }
        $term = implode(' ', $words);
        $filters['q'] = $term;
        return ['term' => $term, 'filters' => $filters];
    }

    /* ---- output ---------------------------------------------------------------------- */

    private function card(array $l): array
    {
        return [
            'id' => (int) $l['id'],
            'title' => (string) $l['title'],
            'price' => money($l['price']),
            'meta' => $l['town_name'] . ' · ' . condition_label($l['item_condition']),
            'url' => url('product.php?id=' . (int) $l['id']),
        ];
    }

    /** @param array<int, array{0:string,1:string}> $links [label, path] */
    private function say(string $text, array $listings = [], array $links = []): array
    {
        return [
            'text' => $text,
            'listings' => array_values($listings),
            'links' => array_map(static fn($l) => ['label' => $l[0], 'url' => url($l[1])], $links),
        ];
    }
}
