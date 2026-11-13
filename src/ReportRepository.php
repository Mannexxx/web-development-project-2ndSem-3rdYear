<?php

declare(strict_types=1);

namespace App\Repository;

final class ReportRepository extends Repository
{
    private const SELECT = 'SELECT rp.id, rp.listing_id, rp.reporter_id, rp.reason, rp.details, rp.status, rp.guideline_id,
                    rp.resolution_note, rp.handled_at, rp.created_at,
                    l.title AS listing_title, l.status AS listing_status, l.seller_id AS seller_id,
                    u.firstname AS reporter_firstname, u.lastname AS reporter_lastname
             FROM reports rp
             JOIN listings l ON l.id = rp.listing_id
             JOIN users u ON u.id = rp.reporter_id';

    public function create(int $listingId, int $reporterId, string $reason, string $details): int
    {
        return $this->insert(
            "INSERT INTO reports (listing_id, reporter_id, reason, details, status, created_at) VALUES (?, ?, ?, ?, 'open', ?)",
            [$listingId, $reporterId, $reason, $details, now()]
        );
    }

    public function addAttachment(int $reportId, string $path, string $originalName, string $mime, int $size): void
    {
        $this->run(
            'INSERT INTO report_attachments (report_id, file_path, original_name, mime_type, file_size) VALUES (?, ?, ?, ?, ?)',
            [$reportId, $path, $originalName, $mime, $size]
        );
    }

    public function hasOpenReport(int $listingId, int $reporterId): bool
    {
        return (int) $this->value(
            "SELECT COUNT(*) FROM reports WHERE listing_id = ? AND reporter_id = ? AND status = 'open'",
            [$listingId, $reporterId]
        ) > 0;
    }

    public function find(int $id): ?array
    {
        return $this->one(self::SELECT . ' WHERE rp.id = ?', [$id]);
    }

    public function open(): array
    {
        return $this->all(self::SELECT . " WHERE rp.status = 'open' ORDER BY rp.created_at ASC, rp.id ASC");
    }

    public function recentHandled(int $limit = 10): array
    {
        $limit = max(1, min(50, $limit));
        return $this->all(self::SELECT . " WHERE rp.status <> 'open' ORDER BY rp.handled_at DESC, rp.id DESC LIMIT $limit");
    }

    public function countOpen(): int
    {
        return (int) $this->value("SELECT COUNT(*) FROM reports WHERE status = 'open'");
    }

    public function attachments(int $reportId): array
    {
        return $this->all('SELECT id, original_name, mime_type, file_size FROM report_attachments WHERE report_id = ? ORDER BY id', [$reportId]);
    }

    public function attachment(int $id): ?array
    {
        return $this->one('SELECT id, report_id, file_path, original_name, mime_type FROM report_attachments WHERE id = ?', [$id]);
    }

    public function close(int $id, int $adminId, string $status, ?int $guidelineId, ?string $note): void
    {
        $this->run(
            'UPDATE reports SET status = ?, guideline_id = ?, resolution_note = ?, handled_by = ?, handled_at = ? WHERE id = ?',
            [$status, $guidelineId, $note !== '' ? $note : null, $adminId, now(), $id]
        );
    }

    /** Other open reports about a listing that was just removed. Returns the reporter ids. */
    public function closeOtherOpenForListing(int $listingId, int $exceptId, int $adminId, ?int $guidelineId, ?string $note): array
    {
        $rows = $this->all("SELECT id, reporter_id FROM reports WHERE listing_id = ? AND status = 'open' AND id <> ?", [$listingId, $exceptId]);
        foreach ($rows as $row) {
            $this->close((int) $row['id'], $adminId, 'resolved', $guidelineId, $note);
        }
        return array_map(static fn($r) => (int) $r['reporter_id'], $rows);
    }
}
