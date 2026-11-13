<?php
// Moderation is restricted to accounts whose role is explicitly set to admin.
declare(strict_types=1);
session_start();
require_once __DIR__ . '/config.php';
if (empty($_SESSION['user']) || ($_SESSION['user']['role'] ?? '') !== 'admin') {
    if (empty($_SESSION['user'])) {
        header('Location: login.php?next=admin.php');
        exit;
    }
    http_response_code(403);
    exit('Admin access only. Sign in with an account that has the admin role.');
}
$pdo = App\Database::getConnection();
$notice = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = (int)($_POST['product_id'] ?? 0);
    $decision = (string)($_POST['decision'] ?? '');
    $valid = ['active', 'changes_requested', 'rejected'];
    if ($id > 0 && in_array($decision, $valid, true)) {
        $note = trim((string)($_POST['note'] ?? ''));
        $pdo->beginTransaction();
        $pdo->prepare('UPDATE products SET status=? WHERE id=? AND status=\'pending\'')->execute([$decision, $id]);
        $pdo->prepare('INSERT INTO moderation_actions(product_id,admin_id,action,note) VALUES(?,?,?,?)')->execute([$id, $_SESSION['user']['id'], $decision === 'active' ? 'approved' : $decision, $note ?: null]);
        $pdo->commit();
        $notice = 'Moderation decision saved.';
    }
}
$pending = $pdo->query("SELECT p.*,u.first_name,u.last_name FROM products p LEFT JOIN users u ON u.id=p.seller_id WHERE p.status='pending' ORDER BY p.created_at")->fetchAll();
$reports = $pdo->query("SELECT r.*,p.title FROM reports r LEFT JOIN products p ON p.id=r.product_id WHERE r.status='open' ORDER BY r.created_at")->fetchAll();
?>
<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Admin moderation</title>
    <link rel="stylesheet" href="assets/marketplace.css">
</head>

<body>
    <header class="wrap topbar"><a class="brand" href="index.php">BANTAYAN MARKETPLACE</a>
        <nav class="toplinks"><a href="index.php">View marketplace</a><form method="post" action="logout.php" data-confirm="Are you sure you want to log out of the admin workspace?"><?= csrf_field() ?><button type="submit">Logout</button></form></nav>
    </header>
    <main class="wrap section"><span class="eyebrow">Community operations</span>
        <h1>Keep the marketplace safe and local.</h1>
        <div class="stats">
            <div class="stat"><strong><?= count($pending) ?></strong>
                <div class="muted">Listings awaiting review</div>
            </div>
            <div class="stat"><strong><?= count($reports) ?></strong>
                <div class="muted">Open community reports</div>
            </div>
            <div class="stat"><strong>0</strong>
                <div class="muted">Customer service requests</div>
            </div>
        </div><?php if ($notice): ?><p class="success"><?= htmlspecialchars($notice) ?></p><?php endif ?><div class="admin-list section">
            <section>
                <h2>Products awaiting review</h2><?php foreach ($pending as $p): ?><article class="panel" style="margin-bottom:12px">
                        <h3><?= htmlspecialchars($p['title']) ?> · ₱<?= number_format((float)$p['price']) ?></h3>
                        <p class="muted"><?= htmlspecialchars(trim(($p['first_name'] ?? 'Seller') . ' ' . ($p['last_name'] ?? ''))) ?> · <?= htmlspecialchars($p['town']) ?></p>
                        <p><?= nl2br(htmlspecialchars($p['description'])) ?></p>
                        <form method="post"><input type="hidden" name="product_id" value="<?= (int)$p['id'] ?>">
                            <div class="field"><label>Decision note</label><input name="note" placeholder="Reason for this decision"></div><button class="dark" name="decision" value="active" data-confirm-click="Approve and publish this listing?">Approve listing</button> <button name="decision" value="changes_requested" data-confirm-click="Request changes to this listing and return it to the seller?">Request changes</button> <button name="decision" value="rejected" data-confirm-click="Reject this listing? The seller will be notified.">Reject</button>
                        </form>
                    </article><?php endforeach ?><?php if (!$pending): ?><p class="muted">No listings are waiting for review.</p><?php endif ?>
            </section>
            <aside class="panel soft">
                <h2>Community reports</h2><?php foreach ($reports as $r): ?><article class="panel">
                        <h3>Report #<?= (int)$r['id'] ?> · <?= htmlspecialchars($r['title'] ?? 'Listing removed') ?></h3>
                        <p><?= htmlspecialchars(str_replace('_', ' ', $r['reason'])) ?></p>
                        <p><?= nl2br(htmlspecialchars($r['details'])) ?></p>
                        <form method="post" action="admin-report.php"><input type="hidden" name="report_id" value="<?= (int)$r['id'] ?>"><button class="dark" name="decision" value="action_taken" data-confirm-click="Resolve this report and take action on the listing?">Resolve</button> <button name="decision" value="dismissed" data-confirm-click="Dismiss this report without taking action?">Dismiss</button></form>
                    </article><?php endforeach ?><?php if (!$reports): ?><p class="muted">No open reports.</p><?php endif ?>
            </aside>
        </div>
    </main>
</body>

    <script src="assets/js/app.js"></script>
</html>
