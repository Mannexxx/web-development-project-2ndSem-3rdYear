<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/config.php';

use App\Repository\NotificationRepository;
use App\Repository\UserRepository;
use App\Service\FaceVerifier;
use App\Service\UploadService;

if (!is_post()) {
    json_response(['ok' => false, 'message' => 'Method not allowed.'], 405);
}
if (!csrf_valid()) {
    json_response(['ok' => false, 'message' => 'Your session expired. Please reload the page.'], 419);
}
$user = current_user();
if ($user === null) {
    json_response(['ok' => false, 'message' => 'Please sign in first.'], 401);
}
if (is_verified($user)) {
    json_response(['ok' => true, 'message' => 'Your profile is already verified.']);
}
if (empty($_POST['consent'])) {
    json_response(['ok' => false, 'message' => 'Please tick the consent box to continue.'], 422);
}

$users = repo(UserRepository::class);
$uid = (int) $user['id'];
$consentAt = now();

$since = date('Y-m-d H:i:s', time() - 3600);
if ($users->failedFaceAttemptsSince($uid, $since) >= 5) {
    json_response(['ok' => false, 'message' => 'Too many unsuccessful attempts. Please contact admin support for help.'], 429);
}

$frames = [];
foreach (['frame1', 'frame2', 'frame3'] as $key) {
    $f = $_FILES[$key] ?? null;
    if (!$f || ($f['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK || !is_uploaded_file((string) $f['tmp_name'])) {
        json_response(['ok' => false, 'message' => 'The camera images did not upload. Please try again.'], 422);
    }
    $frames[] = (string) file_get_contents((string) $f['tmp_name']);
}

$check = (new FaceVerifier())->check($frames);
if (!$check['ok']) {
    $users->recordFaceAttempt($uid, 'failed', $consentAt, null, $check['reason']);
    json_response(['ok' => false, 'message' => $check['reason']], 422);
}

try {
    $path = (new UploadService())->saveFaceImage($frames[0]);
} catch (RuntimeException $e) {
    json_response(['ok' => false, 'message' => 'We could not store your verification. Please contact admin support.'], 500);
}
$users->recordFaceAttempt($uid, 'verified', $consentAt, $path, null);
$users->markFaceVerified($uid);
repo(NotificationRepository::class)->create($uid, 'verification', 'Profile verified', 'You can now message sellers, sell products and leave ratings.', 'sell.php');

json_response(['ok' => true, 'message' => 'Verified! Your profile now shows the face-verified badge.']);
