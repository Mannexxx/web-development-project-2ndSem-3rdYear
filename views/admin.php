<?php
/** @var string $tab */
/** @var array $pending */
/** @var array $openReports */
/** @var array $handledReports */
/** @var array $openTickets */
/** @var array $handledTickets */
/** @var ?array $review */
/** @var array $reviewPhotos */
/** @var ?array $reviewSeller */
/** @var ?array $report */
/** @var array $reportAttachments */
/** @var ?array $ticket */
/** @var array $ticketMessages */
/** @var array $guidelinesAll */
/** @var array $activeGuidelines */
/** @var ?array $editGuideline */

$au = static fn(string $t, array $x = []): string => url('admin/index.php?' . http_build_query(['tab' => $t] + $x));
$tabLinks = [
    'moderation' => 'Product moderation',
    'reports' => 'Reports (' . count($openReports) . ')',
    'support' => 'Customer service (' . count($openTickets) . ')',
    'guidelines' => 'Community guidelines',
];
?>
<section class="wrap admin-head">
    <p class="eyebrow">COMMUNITY OPERATIONS</p>
    <h1 class="h1">Keep the marketplace safe and local.</h1>
    <div class="stats">
        <div class="stat"><strong><?= count($pending) ?></strong><span class="muted">Listings awaiting review</span></div>
        <div class="stat"><strong><?= count($openReports) ?></strong><span class="muted">Open community reports</span></div>
        <div class="stat"><strong><?= count($openTickets) ?></strong><span class="muted">Customer service requests</span></div>
    </div>
    <nav class="tabs" aria-label="Admin sections">
        <?php foreach ($tabLinks as $key => $label): ?>
            <a class="tab<?= $tab === $key ? ' active' : '' ?>" href="<?= e($au($key)) ?>"><?= e($label) ?></a>
        <?php endforeach; ?>
    </nav>
</section>

<section class="wrap admin-grid">
    <div class="admin-main">
    <?php if ($tab === 'moderation'): ?>
        <h2 class="h2">Products awaiting review</h2>
        <div class="table-head"><span>Listing / Seller</span><span>Status &nbsp; Action</span></div>
        <?php if (!$pending): ?><p class="muted">Nothing is waiting for review. Nice work.</p><?php endif; ?>
        <?php foreach ($pending as $p): ?>
            <div class="row-card<?= $review && (int) $review['id'] === (int) $p['id'] ? ' selected' : '' ?>">
                <div>
                    <strong><?= e($p['title']) ?> · <?= e(money($p['price'])) ?></strong><br>
                    <span class="small muted"><?= e(full_name($p, 'seller_')) ?> · <?= (int) $p['seller_verified'] ? 'Face verified' : 'Not face verified' ?></span>
                </div>
                <div class="row-actions"><span class="small muted">Pending</span><a class="btn btn-sm" href="<?= e($au('moderation', ['review' => (int) $p['id']])) ?>">Review</a></div>
            </div>
        <?php endforeach; ?>

        <?php if ($review): ?>
            <div class="panel panel-soft top-gap" id="review">
                <h2 class="h2">Review: <?= e($review['title']) ?></h2>
                <p class="muted">Check photos, description, category, price, and seller profile against community guidelines.</p>
                <div class="review-facts small">
                    <p><strong><?= e(money($review['price'])) ?></strong> · <?= e(condition_label($review['item_condition'])) ?> · <?= e(trim(($review['parent_name'] ?? '') . ' / ' . ($review['category_name'] ?? ''), ' /')) ?> · <?= e(town_label($review['town_name'])) ?></p>
                    <p><?= nl2br(e($review['description'])) ?></p>
                    <p class="muted">Seller: <a class="link" href="<?= e(url('seller.php?id=' . (int) $review['seller_id'])) ?>"><?= e(full_name($review, 'seller_')) ?></a>
                        · <?= !empty($review['seller_verified_at']) ? 'Face verified' : 'Not face verified' ?>
                        · <?= $reviewSeller['seller']['count'] ? e(number_format((float) $reviewSeller['seller']['avg'], 1)) . ' / 5 (' . (int) $reviewSeller['seller']['count'] . ' reviews)' : 'no reviews yet' ?>
                        · <?= (int) $review['seller_violations'] ?> past violation<?= (int) $review['seller_violations'] === 1 ? '' : 's' ?></p>
                    <div class="review-photos">
                        <?php foreach ($reviewPhotos as $ph): ?>
                            <a href="<?= e(url($ph['file_path'])) ?>" target="_blank" rel="noopener"><img src="<?= e(url($ph['file_path'])) ?>" alt="Listing photo"></a>
                        <?php endforeach; ?>
                        <?php if (!$reviewPhotos): ?><span class="bad">No photos were uploaded.</span><?php endif; ?>
                    </div>
                </div>
                <form method="post" action="<?= e(url('admin/index.php')) ?>" class="form">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="moderate">
                    <input type="hidden" name="tab" value="moderation">
                    <input type="hidden" name="listing_id" value="<?= (int) $review['id'] ?>">
                    <div class="checks">
                        <label class="check"><input type="checkbox" name="original_photos" value="1"><span>Original photos</span></label>
                        <label class="check"><input type="checkbox" name="accurate_info" value="1"><span>Accurate information</span></label>
                        <label class="check"><input type="checkbox" name="permitted_item" value="1"><span>Permitted item</span></label>
                    </div>
                    <div class="field">
                        <label for="note">Decision note</label>
                        <input id="note" name="note" maxlength="1000" placeholder="Add a reason if you reject or request changes…">
                    </div>
                    <div class="btn-row">
                        <button class="btn btn-primary" type="submit" name="decision" value="approve" data-confirm-click="Approve and publish this listing?">Approve listing</button>
                        <button class="btn" type="submit" name="decision" value="changes" data-confirm-click="Request changes to this listing and return it to the seller?">Request changes</button>
                        <button class="btn" type="submit" name="decision" value="reject" data-confirm-click="Reject this listing? The seller will be notified.">Reject</button>
                    </div>
                </form>
            </div>
        <?php endif; ?>

    <?php elseif ($tab === 'reports'): ?>
        <h2 class="h2">Open community reports</h2>
        <?php if (!$openReports): ?><p class="muted">No open reports.</p><?php endif; ?>
        <?php foreach ($openReports as $r): ?>
            <div class="row-card<?= $report && (int) $report['id'] === (int) $r['id'] ? ' selected' : '' ?>">
                <div><strong>#R-<?= (int) $r['id'] ?> · <?= e($r['listing_title']) ?></strong><br>
                    <span class="small muted"><?= e(reason_label($r['reason'])) ?> · by <?= e(short_name($r, 'reporter_')) ?> · <?= e(time_ago($r['created_at'])) ?></span></div>
                <div class="row-actions"><span class="small muted">Open</span><a class="btn btn-sm" href="<?= e($au('reports', ['report' => (int) $r['id']])) ?>">Review</a></div>
            </div>
        <?php endforeach; ?>
        <?php if ($handledReports): ?>
            <h3 class="h3 top-gap">Recently handled</h3>
            <?php foreach ($handledReports as $r): ?>
                <div class="row-card"><div><strong>#R-<?= (int) $r['id'] ?> · <?= e($r['listing_title']) ?></strong><br>
                    <span class="small muted"><?= $r['status'] === 'dismissed' ? 'Dismissed' : 'Listing removed' ?> · <?= e(time_ago($r['handled_at'])) ?></span></div></div>
            <?php endforeach; ?>
        <?php endif; ?>

    <?php elseif ($tab === 'support'): ?>
        <h2 class="h2">Open customer service requests</h2>
        <?php if (!$openTickets): ?><p class="muted">No open requests.</p><?php endif; ?>
        <?php foreach ($openTickets as $t): ?>
            <div class="row-card<?= $ticket && (int) $ticket['id'] === (int) $t['id'] ? ' selected' : '' ?>">
                <div><strong>#S-<?= (int) $t['id'] ?> · <?= e(full_name($t, 'user_')) ?></strong><br>
                    <span class="small muted"><?= e($t['subject']) ?> · <?= e(time_ago($t['created_at'])) ?></span></div>
                <div class="row-actions"><span class="small muted">Open</span><a class="btn btn-sm" href="<?= e($au('support', ['ticket' => (int) $t['id']])) ?>">Open</a></div>
            </div>
        <?php endforeach; ?>
        <?php if ($handledTickets): ?>
            <h3 class="h3 top-gap">Recently handled</h3>
            <?php foreach ($handledTickets as $t): ?>
                <div class="row-card">
                    <div><strong>#S-<?= (int) $t['id'] ?> · <?= e(full_name($t, 'user_')) ?></strong><br><span class="small muted"><?= e($t['subject']) ?> · <?= e(ucfirst($t['status'])) ?></span></div>
                    <div class="row-actions"><a class="btn btn-sm" href="<?= e($au('support', ['ticket' => (int) $t['id']])) ?>">View</a></div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>

    <?php else: ?>
        <h2 class="h2">Community guidelines</h2>
        <?php foreach ($guidelinesAll as $g): ?>
            <div class="row-card<?= $editGuideline && (int) $editGuideline['id'] === (int) $g['id'] ? ' selected' : '' ?>">
                <div><strong><?= e($g['title']) ?></strong> <?= (int) $g['is_active'] ? '' : '<span class="tag">Hidden</span>' ?><br>
                    <span class="small muted"><?= e($g['audience'] === 'sellers' ? 'Sellers' : 'Everyone') ?> · <?= e(excerpt($g['body'], 90)) ?></span></div>
                <div class="row-actions"><a class="btn btn-sm" href="<?= e($au('guidelines', ['edit' => (int) $g['id']])) ?>#guideline-form">Edit</a></div>
            </div>
        <?php endforeach; ?>

        <div class="panel panel-soft top-gap" id="guideline-form">
            <h3 class="h3"><?= $editGuideline ? 'Edit guideline' : 'Add a guideline' ?></h3>
            <form method="post" action="<?= e(url('admin/index.php')) ?>" class="form">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="guideline_save">
                <input type="hidden" name="tab" value="guidelines">
                <input type="hidden" name="id" value="<?= (int) ($editGuideline['id'] ?? 0) ?>">
                <div class="field-row">
                    <div class="field"><label for="g-title">Title</label><input id="g-title" name="title" maxlength="120" value="<?= e($editGuideline['title'] ?? '') ?>" required></div>
                    <div class="field"><label for="g-code">Code</label><input id="g-code" name="code" maxlength="40" placeholder="for-example-no-spam" value="<?= e($editGuideline['code'] ?? '') ?>" required></div>
                </div>
                <div class="field"><label for="g-body">Guideline text</label><textarea id="g-body" name="body" rows="3" required><?= e($editGuideline['body'] ?? '') ?></textarea></div>
                <div class="field-row">
                    <div class="field"><label for="g-aud">Applies to</label>
                        <select id="g-aud" name="audience">
                            <option value="everyone"<?= ($editGuideline['audience'] ?? '') !== 'sellers' ? ' selected' : '' ?>>Everyone</option>
                            <option value="sellers"<?= ($editGuideline['audience'] ?? '') === 'sellers' ? ' selected' : '' ?>>Sellers</option>
                        </select></div>
                    <div class="field"><label for="g-sort">Order</label><input id="g-sort" name="sort_order" type="number" min="0" max="999" value="<?= (int) ($editGuideline['sort_order'] ?? 50) ?>"></div>
                </div>
                <label class="check"><input type="checkbox" name="is_active" value="1"<?= !$editGuideline || (int) $editGuideline['is_active'] ? ' checked' : '' ?>><span>Show publicly and allow it as an enforcement reason</span></label>
                <div class="btn-row">
                    <button class="btn btn-primary" type="submit" data-confirm-click="Save this community guideline? Any edits will affect marketplace guidance.">Save guideline</button>
                    <?php if ($editGuideline): ?><a class="btn" href="<?= e($au('guidelines')) ?>">Cancel</a><?php endif; ?>
                </div>
            </form>
        </div>
    <?php endif; ?>
    </div>

    <aside class="admin-side panel panel-soft">
        <h2 class="h2"><?= $report ? 'Community report · #R-' . (int) $report['id'] : 'Community reports' ?></h2>
        <?php if (!$report): ?>
            <p class="muted">No open reports.</p>
        <?php else: ?>
            <p class="small">Listing: <a class="link" href="<?= e(url('listing.php?id=' . (int) $report['listing_id'])) ?>"><?= e($report['listing_title']) ?></a><br>
                Reason: <?= e(reason_label($report['reason'])) ?><br>
                Reported by: <?= e(short_name($report, 'reporter_')) ?><br>
                <?php if ($report['status'] !== 'open'): ?>Status: <strong><?= $report['status'] === 'dismissed' ? 'Dismissed' : 'Listing removed' ?></strong><?php endif; ?></p>
            <div class="panel evidence">
                <?php if ($reportAttachments): ?>
                    <?php foreach ($reportAttachments as $a): ?>
                        <p>Evidence · <?= e($a['original_name']) ?><br>
                            <a class="btn btn-sm" target="_blank" rel="noopener" href="<?= e(url('admin/attachment.php?id=' . (int) $a['id'])) ?>">View attachment</a></p>
                    <?php endforeach; ?>
                <?php else: ?><p class="muted">No evidence attached.</p><?php endif; ?>
            </div>
            <p class="quote-line">“<?= e($report['details']) ?>”</p>
            <?php if ($report['status'] === 'open'): ?>
                <form method="post" action="<?= e(url('admin/index.php')) ?>" class="form">
                    <?= csrf_field() ?>
                    <input type="hidden" name="tab" value="reports">
                    <input type="hidden" name="report_id" value="<?= (int) $report['id'] ?>">
                    <div class="field">
                        <label for="guideline_id">Enforcement reason</label>
                        <select id="guideline_id" name="guideline_id">
                            <option value="">Choose a community guideline…</option>
                            <?php foreach ($activeGuidelines as $g): ?><option value="<?= (int) $g['id'] ?>"><?= e($g['title']) ?></option><?php endforeach; ?>
                        </select>
                    </div>
                    <div class="field"><label for="r-note">Note (optional)</label><input id="r-note" name="note" maxlength="1000" placeholder="Internal note"></div>
                    <div class="btn-row">
                        <button class="btn btn-primary" type="submit" name="action" value="report_remove" data-confirm-click="Remove this listing from the marketplace? The seller and reporter will be notified.">Remove listing</button>
                        <button class="btn" type="submit" name="action" value="report_dismiss" data-confirm-click="Dismiss this report without taking action?">Dismiss report</button>
                    </div>
                </form>
                <p class="small muted">Notify the seller and reporter. Repeated violations may lead to account restrictions.</p>
            <?php endif; ?>
        <?php endif; ?>

        <div class="panel top-gap">
            <h2 class="h2"><?= $ticket ? 'Customer service · #S-' . (int) $ticket['id'] : 'Customer service' ?></h2>
            <?php if (!$ticket): ?>
                <p class="muted">No open requests.</p>
            <?php else: ?>
                <p class="small"><?= e(short_name($ticket, 'user_')) ?> · <?= e(ticket_category_label($ticket['category'])) ?> <span class="tag"><?= e(ucfirst($ticket['status'])) ?></span><br><strong><?= e($ticket['subject']) ?></strong></p>
                <?php foreach ($ticketMessages as $m): $fromUser = (int) $m['sender_id'] === (int) $ticket['user_id']; ?>
                    <p class="small <?= $fromUser ? '' : 'admin-reply' ?>"><span class="muted"><?= $fromUser ? e(short_name($ticket, 'user_')) : 'Admin' ?>:</span> “<?= e($m['body']) ?>”</p>
                <?php endforeach; ?>
                <?php if ($ticket['status'] !== 'closed'): ?>
                    <form method="post" action="<?= e(url('admin/index.php')) ?>" class="form">
                        <?= csrf_field() ?>
                        <input type="hidden" name="tab" value="support">
                        <input type="hidden" name="action" value="ticket_reply">
                        <input type="hidden" name="ticket_id" value="<?= (int) $ticket['id'] ?>">
                        <div class="field"><label for="t-body">Admin response</label><input id="t-body" name="body" maxlength="2000" placeholder="Write a reply to this customer…" required></div>
                        <button class="btn btn-primary" type="submit">Send response</button>
                    </form>
                    <div class="btn-row top-gap-sm">
                        <form method="post" action="<?= e(url('admin/index.php')) ?>" data-confirm="Close this customer service request?"><?= csrf_field() ?>
                            <input type="hidden" name="tab" value="support"><input type="hidden" name="action" value="ticket_close"><input type="hidden" name="ticket_id" value="<?= (int) $ticket['id'] ?>">
                            <button class="btn btn-sm" type="submit">Close request</button></form>
                        <?php if ($ticket['category'] === 'face_verification' && empty($ticket['user_verified_at'])): ?>
                            <form method="post" action="<?= e(url('admin/index.php')) ?>" data-confirm="Mark this member as face verified without the camera check?"><?= csrf_field() ?>
                                <input type="hidden" name="tab" value="support"><input type="hidden" name="action" value="ticket_verify"><input type="hidden" name="ticket_id" value="<?= (int) $ticket['id'] ?>">
                                <button class="btn btn-sm" type="submit">Mark member as face verified</button></form>
                        <?php endif; ?>
                    </div>
                <?php else: ?>
                    <p class="muted small">This request is closed.</p>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </aside>
</section>
