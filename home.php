<?php
/** @var array $topCategories */
/** @var ?array $activeCategory */
/** @var array $towns */
/** @var array $priceRanges */
/** @var array $ratingOptions */
/** @var array $result */
/** @var array $recs */
/** @var int $page */
/** @var int $pages */
/** @var array $params */
/** @var array $sel */
/** @var ?array $me */

$total = (int) $result['total'];
$hasFilters = $sel['q'] !== '' || $sel['category'] !== '' || $sel['town'] || $sel['price'] !== '' || $sel['condition'] !== '' || $sel['rating'] !== '';
?>
<section class="hero">
    <div class="wrap">
        <div class="hero-top">
            <span class="eyebrow">BANTAYAN ISLAND, CEBU</span>
            <span class="muted small hide-mobile">
                <?= $me ? 'Signed in as ' . e($me['firstname']) : 'Browsing as a guest · No account needed' ?>
            </span>
        </div>
        <h1 class="hero-title">Your island.<br class="m-only"> Your marketplace.</h1>
        <p class="lead">Buy local finds. Sell what’s next.<span class="hide-mobile"> Connect with your island community.</span></p>
        <p class="m-meta m-only">Bantayan Island, Cebu · <?= $me ? 'Signed in' : 'Browse as a guest' ?></p>

        <div class="chips" role="navigation" aria-label="Categories">
            <a class="chip<?= $activeCategory === null ? ' active' : '' ?>" href="<?= e(url('index.php' . query_with($params, ['category' => null, 'page' => null]))) ?>">All<span class="hide-mobile"> products</span></a>
            <?php foreach ($topCategories as $c):
                $isActive = $activeCategory !== null && ($activeCategory['slug'] === $c['slug'] || ($activeCategory['parent_slug'] ?? null) === $c['slug']);
                $short = str_contains($c['name'], ' & ') ? explode(' & ', $c['name'])[0] : $c['name'];
                ?>
                <a class="chip<?= $isActive ? ' active' : '' ?>" href="<?= e(url('index.php' . query_with($params, ['category' => $c['slug'], 'page' => null]))) ?>">
                    <span class="hide-mobile"><?= e($c['name']) ?></span><span class="m-only"><?= e($short) ?></span>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<section class="section wrap" id="listings">
    <form id="filters" method="get" action="<?= e(url('index.php')) ?>">
        <?php if ($sel['q'] !== ''): ?><input type="hidden" name="q" value="<?= e($sel['q']) ?>"><?php endif; ?>
        <?php if ($sel['category'] !== ''): ?><input type="hidden" name="category" value="<?= e($sel['category']) ?>"><?php endif; ?>

        <div class="section-head">
            <h2 class="h2">
                <?php if ($sel['q'] !== ''): ?>Results for “<?= e($sel['q']) ?>”
                <?php else: ?><span class="hide-mobile">Fresh finds around the island</span><span class="m-only">Fresh finds</span><?php endif; ?>
            </h2>
            <label class="select-btn hide-mobile">
                <span class="sr-only">Sort</span>
                <select name="sort" data-autosubmit>
                    <option value="newest"<?= $sel['sort'] === 'newest' ? ' selected' : '' ?>>Newest first</option>
                    <option value="price_asc"<?= $sel['sort'] === 'price_asc' ? ' selected' : '' ?>>Price: low to high</option>
                    <option value="price_desc"<?= $sel['sort'] === 'price_desc' ? ' selected' : '' ?>>Price: high to low</option>
                </select>
            </label>
        </div>

        <div class="filter-bar">
            <button type="button" class="btn m-only" data-toggle-filters aria-expanded="false">Filters</button>
            <label class="select-btn m-only">
                <span class="sr-only">Sort</span>
                <select data-mirror-sort>
                    <option value="newest"<?= $sel['sort'] === 'newest' ? ' selected' : '' ?>>Newest first</option>
                    <option value="price_asc"<?= $sel['sort'] === 'price_asc' ? ' selected' : '' ?>>Price: low to high</option>
                    <option value="price_desc"<?= $sel['sort'] === 'price_desc' ? ' selected' : '' ?>>Price: high to low</option>
                </select>
            </label>
            <div class="filter-selects" id="filter-selects">
                <label class="select-btn">
                    <span class="sr-only">Location</span>
                    <select name="town" data-autosubmit>
                        <option value="">Location: All towns</option>
                        <?php foreach ($towns as $t): ?>
                            <option value="<?= (int) $t['id'] ?>"<?= (int) $sel['town'] === (int) $t['id'] ? ' selected' : '' ?>><?= e($t['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label class="select-btn">
                    <span class="sr-only">Price range</span>
                    <select name="price" data-autosubmit>
                        <option value="">Price range</option>
                        <?php foreach ($priceRanges as $key => $range): ?>
                            <option value="<?= e($key) ?>"<?= $sel['price'] === $key ? ' selected' : '' ?>><?= e($range[2]) ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label class="select-btn">
                    <span class="sr-only">Condition</span>
                    <select name="condition" data-autosubmit>
                        <option value="">Condition</option>
                        <?php foreach (['new', 'like_new', 'used'] as $c): ?>
                            <option value="<?= e($c) ?>"<?= $sel['condition'] === $c ? ' selected' : '' ?>><?= e(condition_label($c)) ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label class="select-btn">
                    <span class="sr-only">Seller rating</span>
                    <select name="rating" data-autosubmit>
                        <option value="">Seller rating</option>
                        <?php foreach ($ratingOptions as $key => $label): ?>
                            <option value="<?= e($key) ?>"<?= $sel['rating'] === (string) $key ? ' selected' : '' ?>><?= e($label) ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <noscript><button class="btn" type="submit">Apply filters</button></noscript>
            </div>
            <span class="count"><?= number_format($total) ?> <span class="hide-mobile"><?= $total === 1 ? 'listing' : 'listings' ?></span><span class="m-only"><?= $total === 1 ? 'item' : 'items' ?></span></span>
        </div>
    </form>

    <?php if ($result['items'] === []): ?>
        <div class="empty">
            <p class="h3"><?= $hasFilters ? 'No listings match your search.' : 'No listings yet.' ?></p>
            <p class="muted"><?= $hasFilters ? 'Try different words or remove a filter.' : 'Be the first to sell something on the island.' ?></p>
            <p>
                <?php if ($hasFilters): ?><a class="btn" href="<?= e(url('index.php')) ?>">Clear filters</a><?php endif; ?>
                <a class="btn btn-primary" href="<?= e(url('sell.php')) ?>">Sell a product</a>
            </p>
        </div>
    <?php else: ?>
        <div class="grid">
            <?php foreach ($result['items'] as $card): ?>
                <?php include VIEW_PATH . '/partials/card.php'; ?>
            <?php endforeach; ?>
        </div>
        <?php if ($pages > 1): ?>
            <nav class="pagination" aria-label="Pages">
                <?php if ($page > 1): ?><a class="btn" href="<?= e(url('index.php' . query_with($params, ['page' => $page - 1]) . '#listings')) ?>">‹ Previous</a><?php endif; ?>
                <span class="muted small">Page <?= $page ?> of <?= $pages ?></span>
                <?php if ($page < $pages): ?><a class="btn" href="<?= e(url('index.php' . query_with($params, ['page' => $page + 1]) . '#listings')) ?>">Next ›</a><?php endif; ?>
            </nav>
        <?php endif; ?>
    <?php endif; ?>
</section>

<?php if ($recs['items'] !== []): ?>
    <section class="section wrap" id="picks">
        <div class="section-head">
            <h2 class="h2"><span class="hide-mobile">Picked for your next find</span><span class="m-only">Recommended for you</span></h2>
            <a class="btn hide-mobile" href="<?= e(url('assistant.php?ask=' . rawurlencode('Recommend something for me'))) ?>">AI recommendations</a>
        </div>
        <p class="muted small picks-note">
            <?php if (!$recs['personalised']): ?>
                <span class="hide-mobile">Fresh picks from around the island. Open a few listings and these will adapt to what you browse.</span>
                <span class="m-only">AI picks from your browsing activity.</span>
            <?php elseif ($me): ?>
                <span class="hide-mobile">Based on your browsing activity on Bantayan Marketplace.</span>
                <span class="m-only">AI picks from your browsing activity.</span>
            <?php else: ?>
                <span class="hide-mobile">Based on this browsing session. <a href="<?= e(url('login.php')) ?>" class="link">Sign in</a> to personalize picks across visits.</span>
                <span class="m-only">AI picks from your browsing activity.</span>
            <?php endif; ?>
        </p>
        <div class="grid">
            <?php foreach ($recs['items'] as $card): ?>
                <?php include VIEW_PATH . '/partials/card.php'; ?>
            <?php endforeach; ?>
        </div>
    </section>
<?php endif; ?>

<section class="help-band">
    <div class="wrap help-grid">
        <div>
            <h2 class="h2 hide-mobile">A little help, whenever you need it.</h2>
            <h2 class="h3 m-only">Need a little guidance?</h2>
            <p class="muted"><span class="hide-mobile">Ask our AI assistant about products, sellers, marketplace policies, or how to use the website.</span><span class="m-only">Ask about products, sellers, or policies.</span></p>
            <a class="btn hide-mobile" href="<?= e(url('assistant.php')) ?>">Ask the AI assistant</a>
            <a class="btn btn-primary btn-block m-only" href="<?= e(url('assistant.php')) ?>">Ask the AI assistant</a>
            <p class="small muted m-only"><a href="<?= e(url('support.php')) ?>">Contact admin</a> · <a href="<?= e(url('guidelines.php')) ?>">Community guidelines</a></p>
        </div>
        <div class="hide-mobile">
            <h3 class="h3">Prefer a person?</h3>
            <p class="muted">Contact the admin team for account help or a marketplace concern.</p>
            <a class="btn" href="<?= e(url('support.php')) ?>">Contact customer service</a>
        </div>
    </div>
</section>
