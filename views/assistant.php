<?php
/** @var ?array $listing */
/** @var string $ask */
$prompts = $listing
    ? ['Is this still available?', 'Tell me about the seller', 'Is this price fair?', 'How does buying work?']
    : ['Find a bicycle under 4000', 'How do I sell a product?', 'What are the community guidelines?', 'Is it safe to meet a seller?'];
?>
<section class="wrap page-head">
    <p class="eyebrow">AI ASSISTANT</p>
    <h1 class="h1"><?= $listing ? 'Ask about this product' : 'A little help, whenever you need it.' ?></h1>
    <p class="lead">Ask about products, sellers, marketplace policies, or how to use the website.</p>
</section>
<section class="wrap pad-bottom">
    <div class="panel chat" id="chat" data-endpoint="<?= e(url('api/assistant.php')) ?>" data-listing="<?= (int) ($listing['id'] ?? 0) ?>" data-ask="<?= e($ask) ?>">
        <?php if ($listing): ?>
            <p class="small">Asking about <a class="link" href="<?= e(url('listing.php?id=' . (int) $listing['id'])) ?>"><?= e($listing['title']) ?></a> · <?= e(money($listing['price'])) ?></p>
        <?php endif; ?>
        <div class="chat-log" id="chat-log" aria-live="polite">
            <div class="bubble bubble-them"><span class="who">Assistant</span>Hi! What would you like to know?</div>
        </div>
        <div class="chips" id="chat-prompts">
            <?php foreach ($prompts as $p): ?><button type="button" class="chip" data-prompt="<?= e($p) ?>"><?= e($p) ?></button><?php endforeach; ?>
        </div>
        <form class="compose" id="chat-form" autocomplete="off">
            <div class="field grow"><label for="chat-input">Your question</label><input id="chat-input" maxlength="300" placeholder="Type a question…" required></div>
            <button class="btn btn-primary" type="submit">Ask</button>
        </form>
        <p class="small muted">Answers come from the marketplace’s own listings and rules. For account problems or anything I can’t answer, <a class="link" href="<?= e(url('support.php')) ?>">contact the admin team</a>.</p>
    </div>
</section>
