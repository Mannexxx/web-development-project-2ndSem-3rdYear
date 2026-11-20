<?php

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require_once dirname(__DIR__) . '/config.php';

use App\Repository\SellerApplicationRepository;
use App\Service\SellerDocumentRetentionService;
use App\Service\UploadService;

(new SellerDocumentRetentionService(
    repo(SellerApplicationRepository::class),
    new UploadService()
))->purgeExpired();

echo "Expired seller ID images purged.\n";
