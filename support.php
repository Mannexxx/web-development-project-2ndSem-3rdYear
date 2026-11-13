<?php

declare(strict_types=1);

require_once __DIR__ . '/config.php';

use App\Repository\NotificationRepository;
use App\Repository\SupportRepository;
use App\Repository\UserRepository;

$me = require_login('Please sign in to contact customer service.');
$uid = (int) $me['id'];
$support = repo(SupportRepository::class);
$categories = ['account', 'face_verification', 'listing', 'transaction', 'other'];

if (is_post()) {
    verify_csrf();
    $action = (string) ($_POST['action'] ?? '');

    if ($action === 'new') {
        $category = in_array($_POST['category'] ?? '', $categories, true) ? (string) $_POST['category'] : 'other';
        $subject = mb_substr(trim((string) ($_POST['subject'] ?? '')), 0, 150);
        $body = trim((string) ($_POST['body'] ?? ''));
        if ($subject === '') {
            $subject = ticket_category_label($category);
        }
        if (mb_strlen($body) < 5 || mb_strlen($body) > 2000) {
            flash('error', 'Please describe your request (5 to 2000 characters).');
            redirect('support.php?topic=' . $category);
        }
        $id = $support->create($uid, $category, $subject, $body);
        flash('success', 'Your request was sent to the admin team (#S-' . $id . '). You will see their reply here.');
        redirect('support.php?t=' . $id);
    }

    if ($action === 'reply') {
        $ticket = $support->find((int) ($_POST['ticket_id'] ?? 0));
        if ($ticket === null || (int) $ticket['user_id'] !== $uid) {
            abort(404, 'That request does not exist.');
        }
        $body = trim((string) ($_POST['body'] ?? ''));
        if (mb_strlen($body) < 2 || mb_strlen($body) > 2000) {
            flash('error', 'Please type a reply (up to 2000 characters).');
        } else {
            $support->addMessage((int) $ticket['id'], $uid, $body);
            $support->setStatus((int) $ticket['id'], 'open');
            flash('success', 'Reply sent.');
        }
        redirect('support.php?t=' . (int) $ticket['id']);
    }
    redirect('support.php');
}

$ticket = null;
$messages = [];
if (isset($_GET['t'])) {
    $ticket = $support->find((int) $_GET['t']);
    if ($ticket === null || (int) $ticket['user_id'] !== $uid) {
        abort(404, 'That request does not exist.');
    }
    $messages = $support->messages((int) $ticket['id']);
}
$topic = in_array($_GET['topic'] ?? '', $categories, true) ? (string) $_GET['topic'] : 'other';

render('support', [
    'ticket' => $ticket,
    'messages' => $messages,
    'tickets' => $support->forUser($uid),
    'categories' => $categories,
    'topic' => $topic,
], [
    'title' => 'Customer service · ' . APP_NAME,
    'tab' => 'profile',
    'footer' => ['text' => 'Bantayan Marketplace', 'slashes' => true, 'links' => [['Community guidelines', 'guidelines.php'], ['Privacy', 'privacy.php'], ['Help', 'help.php']]],
]);
