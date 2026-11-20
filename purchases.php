<?php

declare(strict_types=1);

require_once __DIR__ . '/config.php';

use App\Repository\ConversationRepository;
use App\Repository\PurchaseRepository;

$me = require_login('Please sign in to see your purchases.');
$uid = (int) $me['id'];
$purchases = repo(PurchaseRepository::class);
$conversations = repo(ConversationRepository::class);

$messageSeller = static function (int $listingId, int $buyerId, string $body) use ($conversations): void {
    $conversation = $conversations->findByListingBuyer($listingId, $buyerId);
    if ($conversation === null) {
        try {
            $conversationId = $conversations->create($listingId, $buyerId);
        } catch (PDOException) {
            $conversationId = 0;
        }
        $conversation = $conversationId > 0
            ? $conversations->find($conversationId)
            : $conversations->findByListingBuyer($listingId, $buyerId);
    }
    if ($conversation === null) {
        throw new RuntimeException('Could not open a conversation with the seller.');
    }
    $conversations->addMessage((int) $conversation['id'], $buyerId, $body);
};

if (is_post()) {
    verify_csrf();
    $me = require_verified('manage purchases');
    $uid = (int) $me['id'];
    $action = (string) ($_POST['action'] ?? '');
    $listingId = (int) ($_POST['listing_id'] ?? 0);
    $purchaseId = (int) ($_POST['purchase_id'] ?? 0);

    if ($action === 'buy') {
        try {
            $purchaseId = $purchases->placeOrder($listingId, $uid);
        } catch (RuntimeException $e) {
            flash('error', $e->getMessage());
            redirect('listing.php?id=' . $listingId);
        } catch (PDOException $e) {
            error_log('Purchase creation failed for buyer ' . $uid . ': ' . $e->getMessage());
            flash('error', 'Your purchase could not be placed. Please try again.');
            redirect('listing.php?id=' . $listingId);
        }

        try {
            $messageSeller(
                $listingId,
                $uid,
                'I placed an order for this item. Please message me to arrange local pickup.'
            );
            flash('success', 'Order placed. Your purchase is listed in My Purchases, and the seller has been notified.');
        } catch (PDOException | RuntimeException $e) {
            error_log('Seller notification failed for purchase ' . $purchaseId . ': ' . $e->getMessage());
            flash('error', 'Your order was placed, but we could not message the seller. Please contact them from the listing.');
        }
        redirect('purchases.php#to-receive');
    }

    if ($action === 'cancel' || $action === 'received') {
        try {
            if ($action === 'cancel') {
                $purchases->cancel($purchaseId, $uid);
                $message = 'I canceled my order for this item.';
                $notice = 'Purchase canceled and stock returned to the listing.';
            } else {
                $purchases->markReceived($purchaseId, $uid);
                $message = 'I received this item. Thank you.';
                $notice = 'Receipt confirmed. You can now review the seller.';
            }
        } catch (RuntimeException $e) {
            flash('error', $e->getMessage());
            redirect('purchases.php');
        } catch (PDOException $e) {
            error_log('Purchase update failed for buyer ' . $uid . ': ' . $e->getMessage());
            flash('error', 'This purchase could not be updated. Please try again.');
            redirect('purchases.php');
        }

        $purchase = $purchases->findForBuyer($purchaseId, $uid);
        if ($purchase !== null) {
            try {
                $messageSeller((int) $purchase['listing_id'], $uid, $message);
            } catch (PDOException | RuntimeException $e) {
                error_log('Seller update notification failed for purchase ' . $purchaseId . ': ' . $e->getMessage());
                flash('error', $notice . ' We could not message the seller, so please contact them from the listing.');
                redirect('purchases.php');
            }
        }
        flash('success', $notice);
        redirect('purchases.php');
    }

    abort(400, 'Unknown purchase action.');
}

$itemsByStatus = [
    'to_receive' => [],
    'to_review' => [],
    'completed' => [],
    'canceled' => [],
];
foreach ($purchases->forBuyer($uid) as $purchase) {
    $itemsByStatus[$purchase['status']][] = $purchase;
}

render('purchases', ['itemsByStatus' => $itemsByStatus], [
    'title' => 'My Purchases · ' . APP_NAME,
    'tab' => 'purchases',
]);
