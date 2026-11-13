<?php
declare(strict_types=1);
session_start();
require_once __DIR__ . '/config.php';

$pdo = App\Database::getConnection();
$repo = new App\ProductRepository($pdo);
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT) ?: 0;
$product = $repo->findOne($id);
if (!$product) {
    http_response_code(404);
} elseif (!empty($product['category_id'])) {
    // Keep this browsing preference in the current session for the home page.
    $_SESSION['preferred_category_id'] = (int) $product['category_id'];
}
$viewer = $_SESSION['user'] ?? null;
$isOwner = $product && is_array($viewer) && (int) $viewer['id'] === (int) ($product['seller_id'] ?? 0);
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title><?= $product ? htmlspecialchars($product['title']) : 'Listing not found' ?> · Bantayan Marketplace</title>
    <link rel="stylesheet" href="assets/marketplace.css">
</head>
<body>
<?php include __DIR__ . '/includes/market-header.php'; ?>
<?php if (!$product): ?>
    <main class="wrap section"><h1>Listing not found</h1><a class="button" href="products.php">Back to listings</a></main>
<?php else: ?>
    <main class="wrap">
        <p class="muted"><a href="products.php">Marketplace</a> / <?= htmlspecialchars($product['category_name'] ?? 'Local goods') ?></p>
        <div class="detail-layout">
            <div>
                <div class="photo detail-image"<?php if ($product['image_path']): ?> style="background-image:url('<?= htmlspecialchars($product['image_path']) ?>')"<?php endif ?>><?php if (!$product['image_path']): ?>Product photo · 1 of 4<?php endif ?></div>
                <div class="chips"><button>Photo 1</button><button>Photo 2</button><button>Photo 3</button><button>Photo 4</button></div>
            </div>
            <div>
                <span class="eyebrow"><?= htmlspecialchars($product['category_name'] ?? 'Local goods') ?> · <?= htmlspecialchars($product['condition_label']) ?></span>
                <h1><?= htmlspecialchars($product['title']) ?></h1>
                <h2>₱<?= number_format((float) $product['price']) ?></h2>
                <p class="muted"><?= htmlspecialchars($product['town']) ?>, Cebu · Listed <?= htmlspecialchars(date('M j', strtotime($product['created_at']))) ?></p>
                <p><?= nl2br(htmlspecialchars($product['description'])) ?></p>
                <p class="chips"><span class="button"><?= $product['verification_status'] === 'verified' ? 'Face-verified seller' : 'Local seller' ?></span><a class="button" href="#report">Report listing</a></p>
                <?php if ($isOwner): ?>
                    <p class="notice">You are viewing your own listing. Other members can contact you about it.</p>
                <?php elseif ($viewer): ?>
                    <a class="button dark" href="messages.php?product=<?= (int) $product['id'] ?>">Message seller</a>
                    <p class="muted">Signed in as <?= htmlspecialchars((string) $viewer['name']) ?>.</p>
                <?php else: ?>
                    <a class="button dark" href="login.php?next=<?= rawurlencode('product.php?id=' . (int) $product['id']) ?>">Sign in to message seller</a>
                    <p class="muted">Guest browsing is open. Sign in or register when you’re ready to contact the seller.</p>
                <?php endif; ?>
                <div class="panel soft">
                    <h3>Sold by <?= htmlspecialchars(trim(($product['first_name'] ?? 'Local') . ' ' . ($product['last_name'] ?? 'seller'))) ?></h3>
                    <p>★ <?= number_format((float) ($product['seller_rating'] ?? 0), 1) ?> / 5 · <?= (int) $product['review_count'] ?> seller reviews</p>
                    <?php if (!empty($product['seller_id'])): ?><a class="button" href="profile.php?id=<?= (int) $product['seller_id'] ?>">View profile & reviews</a><?php endif; ?>
                </div>
            </div>
        </div>
    </main>
    <section class="help">
        <div class="wrap columns">
            <div id="report"><h2>Report this listing</h2><p class="muted">Reports are reviewed privately by the admin team.</p>
                <form action="report.php" method="post" data-confirm="Submit this report to the admin team for review?">
                    <input type="hidden" name="product_id" value="<?= (int) $product['id'] ?>">
                    <div class="field"><label for="reason">Reason</label><select id="reason" name="reason"><option value="misleading_listing">Misleading listing</option><option value="prohibited_item">Prohibited item</option><option value="suspected_scam">Suspected scam</option><option value="other">Other</option></select></div>
                    <div class="field"><label for="details">What happened?</label><textarea id="details" name="details" required></textarea></div>
                    <button>Submit report</button>
                </form>
            </div>
            <div><h2>Shop safely, stay local.</h2><p class="muted">Meet in a public place and inspect before paying.</p><a class="button" href="help.php">Contact admin</a></div>
        </div>
    </section>
<?php endif; ?>
<nav class="mobile-nav"><a href="index.php">Explore</a><a href="sell.php">Sell</a><a href="messages.php">Messages</a><a href="<?= $viewer ? 'profile.php?id=' . (int) $viewer['id'] : 'register.php' ?>"><?= $viewer ? 'Profile' : 'Join' ?></a></nav>
<script src="assets/js/app.js"></script>
</body>
</html>
