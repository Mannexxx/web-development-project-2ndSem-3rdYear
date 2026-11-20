<?php

declare(strict_types=1);

namespace App\Repository;

final class UserRepository extends Repository
{
    private const BASE = 'SELECT u.*, t.name AS town_name FROM users u JOIN towns t ON t.id = u.town_id';

    public function find(int $id): ?array
    {
        return $this->one(self::BASE . ' WHERE u.id = ?', [$id]);
    }

    public function findByEmail(string $email): ?array
    {
        return $this->one(self::BASE . ' WHERE u.email = ?', [strtolower(trim($email))]);
    }

    public function emailExists(string $email): bool
    {
        return $this->value('SELECT COUNT(*) FROM users WHERE email = ?', [strtolower(trim($email))]) > 0;
    }

    public function create(string $first, string $last, string $email, string $passwordHash, int $townId): int
    {
        return $this->insert(
            'INSERT INTO users (firstname, lastname, email, password_hash, town_id, terms_accepted_at) VALUES (?, ?, ?, ?, ?, ?)',
            [$first, $last, strtolower(trim($email)), $passwordHash, $townId, now()]
        );
    }

    public function touchLogin(int $id): void
    {
        $this->run('UPDATE users SET last_login_at = ? WHERE id = ?', [now(), $id]);
    }

    public function updatePasswordHash(int $id, string $hash): void
    {
        $this->run('UPDATE users SET password_hash = ? WHERE id = ?', [$hash, $id]);
    }

    /* ---- ratings -------------------------------------------------------- */

    /** @return array{seller: array{count:int, avg:?float}, buyer: array{count:int, avg:?float}} */
    public function ratings(int $userId): array
    {
        $out = [
            'seller' => ['count' => 0, 'avg' => null],
            'buyer' => ['count' => 0, 'avg' => null],
        ];
        $rows = $this->all('SELECT as_role, review_count, avg_rating FROM v_user_ratings WHERE user_id = ?', [$userId]);
        foreach ($rows as $row) {
            $out[$row['as_role']] = ['count' => (int) $row['review_count'], 'avg' => (float) $row['avg_rating']];
        }
        return $out;
    }

    /* ---- face verification ---------------------------------------------- */

    public function recordFaceAttempt(int $userId, string $status, string $consentAt, ?string $image, ?string $reason): void
    {
        $this->run(
            'INSERT INTO face_verifications (user_id, status, consent_at, reference_image, failure_reason) VALUES (?, ?, ?, ?, ?)',
            [$userId, $status, $consentAt, $image, $reason]
        );
    }

    public function failedFaceAttemptsSince(int $userId, string $since): int
    {
        return (int) $this->value(
            "SELECT COUNT(*) FROM face_verifications WHERE user_id = ? AND status = 'failed' AND created_at >= ?",
            [$userId, $since]
        );
    }

    public function markFaceVerified(int $userId): void
    {
        $this->run('UPDATE users SET face_verified_at = ? WHERE id = ?', [now(), $userId]);
    }

    /* ---- moderation ------------------------------------------------------ */

    /** Adds a strike; the 3rd strike restricts the account. Returns the new count. */
    public function addViolation(int $userId): int
    {
        $this->run('UPDATE users SET violation_count = violation_count + 1 WHERE id = ?', [$userId]);
        $count = (int) $this->value('SELECT violation_count FROM users WHERE id = ?', [$userId]);
        if ($count >= 3) {
            $this->run("UPDATE users SET status = 'restricted' WHERE id = ? AND role = 'member'", [$userId]);
        }
        return $count;
    }

    /* ---- login throttling ------------------------------------------------- */

    public function logLoginFailure(string $email, string $ip): void
    {
        $this->run('INSERT INTO login_attempts (email, ip_address, attempted_at) VALUES (?, ?, ?)', [strtolower($email), $ip, now()]);
    }

    public function recentLoginFailures(string $email, string $ip, string $since): array
    {
        return [
            'email' => (int) $this->value('SELECT COUNT(*) FROM login_attempts WHERE email = ? AND attempted_at >= ?', [strtolower($email), $since]),
            'ip' => (int) $this->value('SELECT COUNT(*) FROM login_attempts WHERE ip_address = ? AND attempted_at >= ?', [$ip, $since]),
        ];
    }

    public function clearLoginFailures(string $email): void
    {
        $this->run('DELETE FROM login_attempts WHERE email = ?', [strtolower($email)]);
    }
}
