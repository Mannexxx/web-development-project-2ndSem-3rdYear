<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/config.php';

use App\Repository\SellerApplicationRepository;
use App\Service\SellerDocumentRetentionService;
use App\Service\UploadService;

require_admin();
$applications = repo(SellerApplicationRepository::class);
(new SellerDocumentRetentionService($applications, new UploadService()))->purgeExpired();
$application = $applications->find((int) ($_GET['id'] ?? 0));
$side = (string) ($_GET['side'] ?? '');
if ($application === null || !in_array($side, ['front', 'back'], true)
    || ($application['retain_until'] !== null && strtotime((string) $application['retain_until']) <= time())) {
    abort(404, 'Seller ID image not found.');
}
$relative = (string) ($side === 'front' ? $application['id_front_path'] : $application['id_back_path']);
$base = realpath(STORAGE_PATH . '/seller-applications');
$file = realpath(APP_ROOT . '/' . $relative);
if ($base === false || $file === false || !str_starts_with($file, $base . DIRECTORY_SEPARATOR) || !is_file($file)) {
    abort(404, 'Seller ID image is no longer available.');
}
$mime = (new finfo(FILEINFO_MIME_TYPE))->file($file);
if (!in_array($mime, ['image/jpeg', 'image/png'], true)) {
    abort(404, 'Seller ID image is invalid.');
}
header('Content-Type: ' . $mime);
header('Content-Length: ' . filesize($file));
header('Content-Disposition: inline; filename="seller-id-' . $side . '.' . ($mime === 'image/png' ? 'png' : 'jpg') . '"');
header('X-Content-Type-Options: nosniff');
header('Cache-Control: private, no-store');
readfile($file);
