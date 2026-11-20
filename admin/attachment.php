<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/config.php';

use App\Repository\ReportRepository;

require_admin();

$att = repo(ReportRepository::class)->attachment((int) ($_GET['id'] ?? 0));
if ($att === null) {
    abort(404, 'Attachment not found.');
}
$base = realpath(STORAGE_PATH . '/evidence');
$file = realpath(APP_ROOT . '/' . $att['file_path']);
if ($base === false || $file === false || !str_starts_with($file, $base . DIRECTORY_SEPARATOR) || !is_file($file)) {
    abort(404, 'The file is missing from storage.');
}

$safeName = preg_replace('/[^A-Za-z0-9._\- ]/', '_', (string) $att['original_name']) ?: 'evidence';
header('Content-Type: ' . $att['mime_type']);
header('Content-Length: ' . filesize($file));
header('Content-Disposition: inline; filename="' . $safeName . '"');
header('X-Content-Type-Options: nosniff');
header('Cache-Control: private, no-store');
readfile($file);
