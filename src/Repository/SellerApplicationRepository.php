<?php

declare(strict_types=1);

namespace App\Repository;

final class SellerApplicationRepository extends Repository
{
    public function forUser(int $userId): ?array
    {
        return $this->one('SELECT * FROM seller_applications WHERE user_id = ?', [$userId]);
    }

    public function find(int $id): ?array
    {
        return $this->one(
            'SELECT sa.*, u.email, u.phone, u.username
             FROM seller_applications sa
             JOIN users u ON u.id = sa.user_id
             WHERE sa.id = ?',
            [$id]
        );
    }

    public function findByDiditSession(string $sessionId): ?array
    {
        return $this->one('SELECT * FROM seller_applications WHERE didit_session_id = ?', [$sessionId]);
    }

    public function pending(): array
    {
        return $this->all(
            "SELECT sa.id, sa.user_id, sa.first_name, sa.middle_name, sa.last_name, sa.date_of_birth,
                    sa.municipality, sa.status, sa.didit_status, sa.submitted_at
             FROM seller_applications sa
             WHERE sa.status IN ('pending','changes_requested')
             ORDER BY sa.submitted_at ASC"
        );
    }

    public function create(int $userId, array $data): int
    {
        return $this->insert(
            'INSERT INTO seller_applications
                (user_id, first_name, middle_name, last_name, sex, date_of_birth, house_number,
                 street_village, barangay, municipality, id_type, id_front_path, id_back_path,
                 terms_accepted_at, privacy_accepted_at, guidelines_accepted_at, biometric_consent_at,
                 agreement_version, submitted_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
            [
                $userId, $data['first_name'], $data['middle_name'], $data['last_name'], $data['sex'],
                $data['date_of_birth'], $data['house_number'], $data['street_village'], $data['barangay'],
                $data['municipality'], $data['id_type'], $data['id_front_path'], $data['id_back_path'],
                $data['accepted_at'], $data['accepted_at'], $data['accepted_at'], $data['accepted_at'],
                $data['agreement_version'], now(),
            ]
        );
    }

    public function resubmit(int $applicationId, array $data): void
    {
        $this->run(
            "UPDATE seller_applications SET
                first_name = ?, middle_name = ?, last_name = ?, sex = ?, date_of_birth = ?,
                house_number = ?, street_village = ?, barangay = ?, municipality = ?, id_type = ?,
                id_front_path = ?, id_back_path = ?, terms_accepted_at = ?, privacy_accepted_at = ?,
                guidelines_accepted_at = ?, biometric_consent_at = ?, agreement_version = ?,
                status = 'pending', didit_session_id = NULL, didit_status = 'Not Started',
                didit_updated_at = NULL, didit_event_created_at = NULL, submitted_at = ?,
                reviewed_at = NULL, decided_by = NULL, review_note = NULL, retain_until = NULL
             WHERE id = ?",
            [
                $data['first_name'], $data['middle_name'], $data['last_name'], $data['sex'],
                $data['date_of_birth'], $data['house_number'], $data['street_village'], $data['barangay'],
                $data['municipality'], $data['id_type'], $data['id_front_path'], $data['id_back_path'],
                $data['accepted_at'], $data['accepted_at'], $data['accepted_at'], $data['accepted_at'],
                $data['agreement_version'], now(), $applicationId,
            ]
        );
    }

    public function setDiditSession(int $applicationId, string $sessionId, string $status): void
    {
        $this->run(
            'UPDATE seller_applications SET didit_session_id = ?, didit_status = ?, didit_updated_at = ?, didit_event_created_at = NULL WHERE id = ?',
            [$sessionId, $status, now(), $applicationId]
        );
    }

    public function applyDiditStatus(int $applicationId, string $status, string $eventCreatedAt): bool
    {
        return $this->run(
            'UPDATE seller_applications SET didit_status = ?, didit_updated_at = ?, didit_event_created_at = ?
             WHERE id = ? AND (didit_event_created_at IS NULL OR didit_event_created_at <= ?)',
            [$status, now(), $eventCreatedAt, $applicationId, $eventCreatedAt]
        ) > 0;
    }

    public function decide(int $applicationId, int $adminId, string $status, string $note, ?string $retainUntil): void
    {
        $this->run(
            'UPDATE seller_applications SET status = ?, decided_by = ?, reviewed_at = ?, review_note = ?, retain_until = ?
             WHERE id = ?',
            [$status, $adminId, now(), $note !== '' ? $note : null, $retainUntil, $applicationId]
        );
    }

    public function expiredDocuments(): array
    {
        return $this->all(
            'SELECT id, id_front_path, id_back_path FROM seller_applications
             WHERE retain_until IS NOT NULL AND retain_until <= ? AND (id_front_path IS NOT NULL OR id_back_path IS NOT NULL)',
            [now()]
        );
    }

    public function clearExpiredDocuments(int $applicationId): void
    {
        $this->run('UPDATE seller_applications SET id_front_path = NULL, id_back_path = NULL WHERE id = ?', [$applicationId]);
    }
}
