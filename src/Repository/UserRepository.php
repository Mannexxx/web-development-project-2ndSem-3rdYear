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

    public function findByLogin(string $login): ?array
    {
        $login = strtolower(trim($login));
        if (filter_var($login, FILTER_VALIDATE_EMAIL)) {
            return $this->findByEmail($login);
        }
        return $this->one(self::BASE . ' WHERE u.username = ?', [$login]);
    }

    public function usernameExists(string $username): bool
    {
        return (int) $this->value(
            'SELECT COUNT(*) FROM users WHERE username = ?',
            [strtolower(trim($username))]
        ) > 0;
    }

    public function usernameExistsExcept(string $username, int $excludeUserId): bool
    {
        return (int) $this->value(
            'SELECT COUNT(*) FROM users WHERE username = ? AND id != ?',
            [strtolower(trim($username)), $excludeUserId]
        ) > 0;
    }

    public function emailExists(string $email): bool
    {
        return $this->value('SELECT COUNT(*) FROM users WHERE email = ?', [strtolower(trim($email))]) > 0;
    }

    public function phoneExists(string $phone): bool
    {
        return $this->value('SELECT COUNT(*) FROM users WHERE phone = ? OR phone = ?', [
            $phone,
            preg_replace('/^\\+63/', '0', $phone) ?? $phone,
        ]) > 0;
    }

    public function create(string $first, string $last, string $username, ?string $email, ?string $phone, ?string $phoneCarrier, string $passwordHash, int $townId): int
    {
        return $this->insert(
            'INSERT INTO users (firstname, lastname, username, email, phone, phone_carrier, password_hash, town_id, terms_accepted_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)',
            [$first, $last, strtolower(trim($username)), $email !== null ? strtolower(trim($email)) : null, $phone, $phoneCarrier, $passwordHash, $townId, now()]
        );
    }

    /** @return string[] Relative paths of personal images removed with the account. */
    public function deactivateAccount(int $userId): array
    {
        $user = $this->one('SELECT email, role FROM users WHERE id = ?', [$userId]);
        if ($user === null) {
            throw new \RuntimeException('This account could not be found.');
        }
        if ($user['role'] === 'admin') {
            throw new \RuntimeException('Admin accounts cannot be deactivated here.');
        }

        $files = array_column(
            $this->all(
                'SELECT p.file_path FROM listing_photos p JOIN listings l ON l.id = p.listing_id WHERE l.seller_id = ?
                 UNION ALL
                 SELECT reference_image FROM face_verifications WHERE user_id = ? AND reference_image IS NOT NULL
                 UNION ALL
                 SELECT profile_pic FROM users WHERE id = ? AND profile_pic IS NOT NULL',
                [$userId, $userId, $userId]
            ),
            'file_path'
        );
        $randomPasswordHash = password_hash(bin2hex(random_bytes(32)), PASSWORD_DEFAULT);

        $this->db->beginTransaction();
        try {
            $this->run("UPDATE listings SET status = 'removed' WHERE seller_id = ?", [$userId]);
            $this->run(
                'DELETE p FROM listing_photos p JOIN listings l ON l.id = p.listing_id WHERE l.seller_id = ?',
                [$userId]
            );
            $this->run('DELETE FROM face_verifications WHERE user_id = ?', [$userId]);
            $this->run('DELETE FROM verification_codes WHERE user_id = ?', [$userId]);
            $this->run('DELETE FROM product_views WHERE user_id = ?', [$userId]);
            $this->run('DELETE FROM notifications WHERE user_id = ?', [$userId]);
            if ($user['email'] !== null) {
                $this->run('DELETE FROM login_attempts WHERE email = ?', [$user['email']]);
            }
            $this->run(
                "UPDATE users
                 SET firstname = 'Deleted', lastname = 'Account', username = ?, email = NULL, phone = NULL,
                     phone_carrier = NULL, email_verified_at = NULL, phone_verified_at = NULL,
                     password_hash = ?, status = 'deactivated', violation_count = 0, profile_pic = NULL,
                     face_verified_at = NULL
                 WHERE id = ?",
                ['deleted-' . $userId, $randomPasswordHash, $userId]
            );
            $this->db->commit();
        } catch (\Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $e;
        }

        return array_values(array_filter($files, static fn ($path): bool => is_string($path) && $path !== ''));
    }

    public function emailExistsExcept(string $email, int $excludeUserId): bool
    {
        return (int) $this->value(
            'SELECT COUNT(*) FROM users WHERE email = ? AND id != ?',
            [strtolower(trim($email)), $excludeUserId]
        ) > 0;
    }

    public function updateProfile(int $id, string $first, string $last, string $username, string $email, int $townId, ?string $profilePic, bool $emailChanged): void
    {
        if ($emailChanged) {
            $this->run(
                'UPDATE users SET firstname = ?, lastname = ?, username = ?, email = ?, email_verified_at = NULL, town_id = ?, profile_pic = ? WHERE id = ?',
                [$first, $last, strtolower(trim($username)), strtolower(trim($email)), $townId, $profilePic, $id]
            );
            return;
        }
        $this->run(
            'UPDATE users SET firstname = ?, lastname = ?, username = ?, email = ?, town_id = ?, profile_pic = ? WHERE id = ?',
            [$first, $last, strtolower(trim($username)), strtolower(trim($email)), $townId, $profilePic, $id]
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

    public function createVerificationCode(int $userId, string $channel, string $destination, string $codeHash, string $expiresAt): void
    {
        $this->run(
            'INSERT INTO verification_codes (user_id, channel, destination, code_hash, expires_at) VALUES (?, ?, ?, ?, ?)',
            [$userId, $channel, $destination, $codeHash, $expiresAt]
        );
    }

    public function invalidateVerificationCodes(int $userId, string $channel): void
    {
        $this->run(
            "UPDATE verification_codes SET consumed_at = COALESCE(consumed_at, ?) WHERE user_id = ? AND channel = ? AND consumed_at IS NULL",
            [now(), $userId, $channel]
        );
    }

    public function latestVerificationCode(int $userId, string $channel): ?array
    {
        return $this->one(
            "SELECT * FROM verification_codes WHERE user_id = ? AND channel = ? AND consumed_at IS NULL ORDER BY id DESC LIMIT 1",
            [$userId, $channel]
        );
    }

    public function hasRecentVerificationCode(int $userId, string $channel, string $since): bool
    {
        return (int) $this->value(
            'SELECT COUNT(*) FROM verification_codes WHERE user_id = ? AND channel = ? AND created_at >= ?',
            [$userId, $channel, $since]
        ) > 0;
    }

    public function incrementVerificationAttempts(int $id): void
    {
        $this->run('UPDATE verification_codes SET attempts = attempts + 1 WHERE id = ?', [$id]);
    }

    public function invalidateVerificationCode(int $id): void
    {
        $this->run('UPDATE verification_codes SET consumed_at = ? WHERE id = ?', [now(), $id]);
    }

    public function markEmailVerified(int $userId): void
    {
        $this->run('UPDATE users SET email_verified_at = ? WHERE id = ?', [now(), $userId]);
    }

    public function markPhoneVerified(int $userId): void
    {
        $this->run('UPDATE users SET phone_verified_at = ? WHERE id = ?', [now(), $userId]);
    }

    /* ---- ratings -------------------------------------------------------- */

    /** @return array{seller: array{count:int, avg:?float}, buyer: array{count:int, avg:?float}} */
    public function ratings(int $userId): array
    {
        $out = [
            'seller' => ['count' => 0, 'avg' => null],
            'buyer' => ['count' => 0, 'avg' => null],
        ];
        $rows = $this->all(
            "SELECT CASE reviewer_role WHEN 'buyer' THEN 'seller' ELSE 'buyer' END AS as_role,
                    COUNT(*) AS review_count, ROUND(AVG(rating), 1) AS avg_rating
             FROM reviews
             WHERE reviewee_id = ?
             GROUP BY reviewer_role",
            [$userId]
        );
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
        $this->run("UPDATE users SET violation_count = violation_count + 1 WHERE id = ? AND status <> 'deactivated'", [$userId]);
        $count = (int) $this->value('SELECT violation_count FROM users WHERE id = ?', [$userId]);
        if ($count >= 3) {
            $this->run("UPDATE users SET status = 'restricted' WHERE id = ? AND role = 'member' AND status <> 'deactivated'", [$userId]);
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
