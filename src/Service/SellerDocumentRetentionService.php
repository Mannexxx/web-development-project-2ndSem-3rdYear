<?php

declare(strict_types=1);

namespace App\Service;

use App\Repository\SellerApplicationRepository;

final class SellerDocumentRetentionService
{
    public function __construct(
        private SellerApplicationRepository $applications,
        private UploadService $uploads
    ) {
    }

    public function purgeExpired(): void
    {
        foreach ($this->applications->expiredDocuments() as $application) {
            $this->uploads->delete($application['id_front_path']);
            $this->uploads->delete($application['id_back_path']);
            $this->applications->clearExpiredDocuments((int) $application['id']);
        }
    }
}
