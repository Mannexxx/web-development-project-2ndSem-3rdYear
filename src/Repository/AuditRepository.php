<?php

declare(strict_types=1);

namespace App\Repository;

/** "Admin actions are recorded." */
final class AuditRepository extends Repository
{
    public function record(int $adminId, string $action, string $targetType, ?int $targetId, ?string $details = null): void
    {
        $this->run(
            'INSERT INTO admin_actions (admin_id, action, target_type, target_id, details, created_at) VALUES (?, ?, ?, ?, ?, ?)',
            [$adminId, $action, $targetType, $targetId, $details !== null ? mb_substr($details, 0, 500) : null, now()]
        );
    }
}
