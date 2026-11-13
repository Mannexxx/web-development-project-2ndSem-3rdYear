<?php

declare(strict_types=1);

require_once __DIR__ . '/config.php';

use App\Database;
use App\Repository\ConversationRepository;
use App\Repository\DealRepository;
use App\Repository\ListingRepository;
use App\Repository\NotificationRepository;

$conversations = repo(ConversationRepository::class);
$listings = repo(ListingRepository::class);
$deals = repo(DealRepository::class);
$notes = repo(NotificationRepository::class);

/** Only allow going back to the listing page or a conversation. */
function message_return(?string $ret, string $fallback): string
{
    $ret = (string) $ret;
    return preg_match('/^(listing\.php\?id=\d+|messages\.php\?c=\d+)(#[a-z\-]+)?$/', $ret) ? $ret : $fallback;
}

if (is_post()) {
    verify_csrf();
    $action = (string) ($_POST['action'] ?? '');

    if ($action === 'send') {
        $me = require_verified('send messages');
        $body = trim((string) ($_POST['body'] ?? ''));
        $convId = (int) ($_POST['conversation_id'] ?? 0);
        $listingId = (int) ($_POST['listing_id'] ?? 0);
        $fallback = $convId > 0 ? 'messages.php?c=' . $convId : 'listing.php?id=' . $listingId;
        $return = message_return($_POST['return'] ?? '', $fallback);

        if ($body === '' || mb_strlen($body) > 2000) {
            flash('error', 'Please type a message (up to 2000 characters).');
            redirect($return);
        }

        $conv = null;
        if ($convId > 0) {
            $conv = $conversations->find($convId);
            if ($conv === null || ((int) $conv['buyer_id'] !== (int) $me['id'] && (int) $conv['seller_id'] !== (int) $me['id'])) {
                abort(403, 'You are not part of this conversation.');
            }
        } else {
            $listing = $listings->find($listingId);
            if ($listing === null || $listing['status'] !== 'approved') {
                flash('error', 'This listing is no longer available for messages.');
                redirect('index.php');
            }
            if ((int) $listing['seller_id'] === (int) $me['id']) {
                flash('error', 'You cannot message yourself about your own listing.');
                redirect($return);
            }
            $conv = $conversations->findByListingBuyer($listingId, (int) $me['id']);
            if ($conv === null) {
                try {
                    $newId = $conversations->create($listingId, (int) $me['id']);
                } catch (PDOException) {
                    $newId = 0; // created by a double click a moment ago
                }
                $conv = $newId > 0 ? $conversations->find($newId) : $conversations->findByListingBuyer($listingId, (int) $me['id']);
            }
            if ($conv === null) {
                abort(500, 'Could not start the conversation.');
            }
        }

        $conversations->addMessage((int) $conv['id'], (int) $me['id'], $body);
        flash('success', 'Message sent.');
        redirect($convId > 0 ? $return : message_return($_POST['return'] ?? '', 'messages.php?c=' . (int) $conv['id']));
    }

    if ($action === 'sold') {
        $me = require_verified('mark items as sold');
        $conv = $conversations->find((int) ($_POST['conversation_id'] ?? 0));
        if ($conv === null || (int) $conv['seller_id'] !== (int) $me['id']) {
            abort(403, 'Only the seller can mark an item as sold.');
        }
        $back = 'messages.php?c=' . (int) $conv['id'];
        if ($conv['listing_status'] !== 'approved') {
            flash('error', 'This listing is not live, so it cannot be marked as sold.');
            redirect($back);
        }
        $pdo = Database::getConnection();
        $pdo->beginTransaction();
        try {
            $deals->create((int) $conv['listing_id'], (int) $conv['buyer_id'], (int) $me['id']);
            $listings->setStatus((int) $conv['listing_id'], 'sold');
            $pdo->commit();
        } catch (Throwable $e) {
            $pdo->rollBack();
            flash('error', 'Could not complete the deal. Please try again.');
            redirect($back);
        }
        $notes->create((int) $conv['buyer_id'], 'deal', 'Deal completed', 'Rate your experience with ' . $me['firstname'] . ' for "' . $conv['listing_title'] . '".', $back);
        flash('success', 'Marked as sold. You can now rate each other.');
        redirect($back);
    }

    if ($action === 'review') {
        $me = require_verified('leave a rating');
        $deal = $deals->find((int) ($_POST['deal_id'] ?? 0));
        $rating = (int) ($_POST['rating'] ?? 0);
        $comment = trim((string) ($_POST['comment'] ?? ''));
        $back = message_return($_POST['return'] ?? '', 'account.php');
        if ($deal === null || ((int) $deal['buyer_id'] !== (int) $me['id'] && (int) $deal['seller_id'] !== (int) $me['id'])) {
            abort(403, 'You can only rate a deal you took part in.');
        }
        if ($rating < 1 || $rating > 5) {
            flash('error', 'Please choose a rating from 1 to 5 stars.');
            redirect($back);
        }
        if (mb_strlen($comment) > 500) {
            flash('error', 'Please keep your comment under 500 characters.');
            redirect($back);
        }
        if ($deals->reviewBy((int) $deal['id'], (int) $me['id']) !== null) {
            flash('info', 'You already rated this deal.');
            redirect($back);
        }
        $isBuyer = (int) $deal['buyer_id'] === (int) $me['id'];
        $revieweeId = $isBuyer ? (int) $deal['seller_id'] : (int) $deal['buyer_id'];
        try {
            $deals->addReview((int) $deal['id'], (int) $me['id'], $revieweeId, $isBuyer ? 'buyer' : 'seller', $rating, $comment);
        } catch (PDOException) {
            flash('info', 'You already rated this deal.');
            redirect($back);
        }
        $notes->create($revieweeId, 'review', 'You received a new rating', $me['firstname'] . ' rated you ' . $rating . ' out of 5.', 'seller.php?id=' . $revieweeId);
        flash('success', 'Thank you for rating this deal.');
        redirect($back);
    }

    redirect('messages.php');
}

$me = require_login('Please sign in to see your messages.');
$uid = (int) $me['id'];

$current = null;
$messages = [];
$deal = null;
$myReview = null;
$currentId = (int) ($_GET['c'] ?? 0);
if ($currentId > 0) {
    $current = $conversations->find($currentId);
    if ($current === null || ((int) $current['buyer_id'] !== $uid && (int) $current['seller_id'] !== $uid)) {
        abort(404, 'That conversation does not exist.');
    }
    $conversations->markRead($currentId, $uid);
    $messages = $conversations->messages($currentId);
    $found = $deals->findByListing((int) $current['listing_id']);
    if ($found !== null && (int) $found['buyer_id'] === (int) $current['buyer_id']) {
        $deal = $found;
        $myReview = $deals->reviewBy((int) $deal['id'], $uid);
    }
}

render('messages', [
    'list' => $conversations->forUser($uid),
    'current' => $current,
    'messages' => $messages,
    'deal' => $deal,
    'myReview' => $myReview,
    'uid' => $uid,
], [
    'title' => 'Messages · ' . APP_NAME,
    'tab' => 'messages',
]);
