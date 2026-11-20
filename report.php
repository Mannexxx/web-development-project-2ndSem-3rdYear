<?php

declare(strict_types=1);

require_once __DIR__ . '/config.php';

use App\Repository\ListingRepository;
use App\Repository\ReportRepository;
use App\Service\UploadService;

if (!is_post()) {
    redirect('index.php');
}
verify_csrf();

$listingId = (int) ($_POST['listing_id'] ?? 0);
$back = 'listing.php?id=' . $listingId;
$user = require_login('Please sign in to report a listing.');

$listing = repo(ListingRepository::class)->find($listingId);
if ($listing === null || !in_array($listing['status'], ['approved', 'sold'], true)) {
    abort(404, 'That listing does not exist.');
}
if ((int) $listing['seller_id'] === (int) $user['id']) {
    flash('error', 'You cannot report your own listing.');
    redirect($back);
}

$reports = repo(ReportRepository::class);
$reason = (string) ($_POST['reason'] ?? '');
$details = trim((string) ($_POST['details'] ?? ''));
$errors = [];
if (!in_array($reason, ['misleading_listing', 'prohibited_item', 'other'], true)) {
    $errors[] = 'Please choose a reason for your report.';
}
if (mb_strlen($details) < 10) {
    $errors[] = 'Please describe what happened (at least 10 characters).';
} elseif (mb_strlen($details) > 2000) {
    $errors[] = 'Please keep your description under 2000 characters.';
}
if ($reports->hasOpenReport($listingId, (int) $user['id'])) {
    $errors[] = 'You already have an open report for this listing. The admin team will review it.';
}

$files = UploadService::files('evidence');
if (count($files) > 3) {
    $errors[] = 'You can attach up to 3 evidence files.';
}

$saved = [];
if ($errors === []) {
    $uploader = new UploadService();
    foreach ($files as $file) {
        try {
            $saved[] = $uploader->saveEvidence($file);
        } catch (RuntimeException $e) {
            $errors[] = $e->getMessage();
        }
    }
}

if ($errors !== []) {
    $uploader = new UploadService();
    foreach ($saved as $s) {
        $uploader->delete($s['path']);
    }
    flash('error', $errors);
    redirect($back . '#report');
}

$reportId = $reports->create($listingId, (int) $user['id'], $reason, $details);
foreach ($saved as $s) {
    $reports->addAttachment($reportId, $s['path'], $s['original'], $s['mime'], $s['size']);
}
flash('success', 'Thank you. Your report was sent to the admin team and will be reviewed privately.');
redirect($back);
