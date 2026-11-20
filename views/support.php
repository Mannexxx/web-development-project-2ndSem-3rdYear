<?php
/** @var ?array $ticket */
/** @var array $messages */
/** @var array $tickets */
/** @var array $categories */
/** @var string $topic */
/** @var ?array $me */
?>
<section class="wrap page-head">
    <p class="eyebrow">CUSTOMER SERVICE</p>
    <h1 class="h1">Prefer a person?</h1>
    <p class="lead">Message the admin team for account help or a marketplace concern.</p>
</section>

<section class="wrap pad-bottom two-col">
    <div class="panel">
        <?php if ($ticket): ?>
            <div class="panel-head"><h2 class="h2">#S-<?= (int) $ticket['id'] ?> · <?= e($ticket['subject']) ?></h2><span class="tag"><?= e(ucfirst($ticket['status'])) ?></span></div>
            <p class="small muted"><?= e(ticket_category_label($ticket['category'])) ?> · opened <?= e(time_ago($ticket['created_at'])) ?></p>
            <?php foreach ($messages as $m): $mine = (int) $m['sender_id'] === (int) $ticket['user_id']; ?>
                <div class="bubble <?= $mine ? 'bubble-me' : 'bubble-them' ?>">
                    <span class="who"><?= $mine ? 'You' : 'Admin team' ?> <span class="when"><?= e(time_ago($m['created_at'])) ?></span></span>
                    <?= nl2br(e($m['body'])) ?>
                </div>
            <?php endforeach; ?>
            <?php if ($ticket['status'] !== 'closed'): ?>
                <form class="compose" method="post" action="<?= e(url('support.php')) ?>">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="reply">
                    <input type="hidden" name="ticket_id" value="<?= (int) $ticket['id'] ?>">
                    <div class="field grow"><label for="body">Your reply</label><input id="body" name="body" maxlength="2000" placeholder="Write a reply…" required></div>
                    <button class="btn btn-primary" type="submit">Send</button>
                </form>
            <?php else: ?>
                <p class="muted top-gap">This request is closed. Start a new one if you need more help.</p>
            <?php endif; ?>
            <p class="top-gap"><a class="btn" href="<?= e(url('support.php')) ?>">New request</a></p>
        <?php else: ?>
            <h2 class="h2">New request</h2>
            <form class="form" method="post" action="<?= e(url('support.php')) ?>">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="new">
                <div class="field">
                    <label for="category">What is this about?</label>
                    <select id="category" name="category">
                        <?php foreach ($categories as $c): ?><option value="<?= e($c) ?>"<?= $topic === $c ? ' selected' : '' ?>><?= e(ticket_category_label($c)) ?></option><?php endforeach; ?>
                    </select>
                </div>
                <div class="field"><label for="subject">Subject</label><input id="subject" name="subject" maxlength="150" placeholder="Short summary"></div>
                <div class="field"><label for="body">Message</label><textarea id="body" name="body" rows="5" maxlength="2000" placeholder="Tell us what happened…" required></textarea></div>
                <button class="btn btn-primary" type="submit">Send to admin team</button>
            </form>
        <?php endif; ?>
    </div>

    <div class="panel panel-soft">
        <h2 class="h2">Your requests</h2>
        <?php if (!$tickets): ?><p class="muted">No requests yet.</p><?php endif; ?>
        <?php foreach ($tickets as $t): ?>
            <div class="row-item">
                <div><strong>#S-<?= (int) $t['id'] ?> · <?= e($t['subject']) ?></strong><br><span class="small muted"><?= e(ucfirst($t['status'])) ?> · <?= e(time_ago($t['updated_at'])) ?></span></div>
                <a class="btn btn-sm" href="<?= e(url('support.php?t=' . (int) $t['id'])) ?>">Open</a>
            </div>
        <?php endforeach; ?>
        <p class="small muted top-gap">Want a quick answer first? <a class="link" href="<?= e(url('assistant.php')) ?>">Ask the AI assistant</a>.</p>
    </div>
</section>
