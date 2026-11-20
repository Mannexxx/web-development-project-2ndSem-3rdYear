<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/config.php';

use App\Database;
use App\Repository\NotificationRepository;
use App\Repository\SellerApplicationRepository;
use App\Repository\UserRepository;
use App\Service\DiditService;

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    json_response(['ok' => false], 405);
}
$raw = file_get_contents('php://input');
if ($raw === false || $raw === '' || strlen($raw) > 65536) {
    json_response(['ok' => false], 413);
}
$payload = json_decode($raw);
if (!$payload instanceof stdClass) {
    json_response(['ok' => false], 400);
}

$headers = [
    'timestamp' => (string) ($_SERVER['HTTP_X_TIMESTAMP'] ?? ''),
    'signature_v2' => (string) ($_SERVER['HTTP_X_SIGNATURE_V2'] ?? ''),
    'signature' => (string) ($_SERVER['HTTP_X_SIGNATURE'] ?? ''),
];
if (!DiditService::verifyWebhook($raw, $payload, $headers)) {
    json_response(['ok' => false], 401);
}

if (($payload->webhook_type ?? '') !== 'status.updated') {
    json_response(['ok' => true]);
}
$eventId = (string) ($payload->event_id ?? '');
$sessionId = (string) ($payload->session_id ?? '');
$status = (string) ($payload->status ?? '');
$supportedStatuses = ['Approved', 'Declined', 'In Review', 'In Progress', 'Not Started', 'Abandoned', 'Expired', 'Kyc Expired', 'Resubmitted', 'Awaiting User'];
if ($eventId === '' || $sessionId === '' || !in_array($status, $supportedStatuses, true)) {
    json_response(['ok' => false], 400);
}

$applications = repo(SellerApplicationRepository::class);
$application = $applications->findByDiditSession($sessionId);
if ($application === null || (string) ($payload->vendor_data ?? '') !== 'seller-application-' . (int) $application['id']) {
    json_response(['ok' => true]);
}
$created = $payload->created_at ?? $payload->timestamp ?? time();
$eventTime = is_numeric($created) ? date('Y-m-d H:i:s', (int) $created) : date('Y-m-d H:i:s');
$db = Database::getConnection();
$db->beginTransaction();
try {
    $stmt = $db->prepare('INSERT IGNORE INTO didit_webhook_events (event_id, session_id, received_at) VALUES (?, ?, ?)');
    $stmt->execute([$eventId, $sessionId, now()]);
    if ($stmt->rowCount() === 0) {
        $db->commit();
        json_response(['ok' => true]);
    }

    $updated = $applications->applyDiditStatus((int) $application['id'], $status, $eventTime);
    if ($updated && $status === 'Approved') {
        repo(UserRepository::class)->markFaceVerified((int) $application['user_id']);
    }
    $db->commit();
} catch (Throwable $e) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    throw $e;
}

if ($updated && in_array($status, ['Approved', 'Declined'], true)) {
    $title = $status === 'Approved' ? 'Identity check complete' : 'Identity verification needs another attempt';
    $body = $status === 'Approved'
        ? 'Didit verified your identity. Your seller application still needs admin approval.'
        : 'Didit could not verify your identity. Return to your seller application to try again.';
    repo(NotificationRepository::class)->create((int) $application['user_id'], 'verification', $title, $body, 'seller-application.php');
}
json_response(['ok' => true]);
