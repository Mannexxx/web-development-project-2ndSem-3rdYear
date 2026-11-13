<?php

declare(strict_types=1);

namespace App\Service;

use App\Repository\TownRepository;
use App\Repository\UserRepository;

final class AuthService
{
    private const MAX_EMAIL_FAILURES = 5;
    private const MAX_IP_FAILURES = 25;
    private const LOCK_MINUTES = 15;

    public function __construct(
        private readonly UserRepository $users,
        private readonly TownRepository $towns
    ) {
    }

    /** @return string[] */
    public static function passwordProblems(string $password): array
    {
        $errors = [];
        if (strlen($password) < 8) {
            $errors[] = 'Password must have at least 8 characters.';
        }
        if (!preg_match('/\d/', $password)) {
            $errors[] = 'Password must include a number.';
        }
        if (!preg_match('/[^A-Za-z0-9]/', $password)) {
            $errors[] = 'Password must include a symbol (for example ! @ # $).';
        }
        if (strlen($password) > 72) {
            $errors[] = 'Password must be 72 characters or fewer.';
        }
        return $errors;
    }

    /** @return array{errors: string[], user_id: ?int} */
    public function register(array $in): array
    {
        $first = trim((string) ($in['firstname'] ?? ''));
        $last = trim((string) ($in['lastname'] ?? ''));
        $email = strtolower(trim((string) ($in['email'] ?? '')));
        $password = (string) ($in['password'] ?? '');
        $townId = (int) ($in['town_id'] ?? 0);
        $agreed = !empty($in['agree']);

        $errors = [];
        $namePattern = '/^[\p{L}][\p{L} .\'\-]*$/u';
        if ($first === '' || mb_strlen($first) > 80 || !preg_match($namePattern, $first)) {
            $errors[] = 'Please enter your first name (letters only).';
        }
        if ($last === '' || mb_strlen($last) > 80 || !preg_match($namePattern, $last)) {
            $errors[] = 'Please enter your last name (letters only).';
        }
        if ($email === '' || strlen($email) > 190 || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Please enter a valid email address.';
        } elseif ($this->users->emailExists($email)) {
            $errors[] = 'An account with this email already exists. Try signing in instead.';
        }
        array_push($errors, ...self::passwordProblems($password));
        if ($townId <= 0 || $this->towns->find($townId) === null) {
            $errors[] = 'Please select your town on Bantayan Island.';
        }
        if (!$agreed) {
            $errors[] = 'Please agree to the Terms, Privacy Policy and Community Guidelines.';
        }
        if ($errors !== []) {
            return ['errors' => $errors, 'user_id' => null];
        }

        $id = $this->users->create($first, $last, $email, password_hash($password, PASSWORD_DEFAULT), $townId);
        return ['errors' => [], 'user_id' => $id];
    }

    /** @return array{ok: bool, error: ?string, user_id: ?int} */
    public function attempt(string $email, string $password): array
    {
        $email = strtolower(trim($email));
        $ip = (string) ($_SERVER['REMOTE_ADDR'] ?? '0.0.0.0');

        if ($email === '' || $password === '') {
            return ['ok' => false, 'error' => 'Please enter your email and password.', 'user_id' => null];
        }

        $since = date('Y-m-d H:i:s', time() - self::LOCK_MINUTES * 60);
        $recent = $this->users->recentLoginFailures($email, $ip, $since);
        if ($recent['email'] >= self::MAX_EMAIL_FAILURES || $recent['ip'] >= self::MAX_IP_FAILURES) {
            return ['ok' => false, 'error' => 'Too many failed attempts. Please wait ' . self::LOCK_MINUTES . ' minutes and try again.', 'user_id' => null];
        }

        $user = $this->users->findByEmail($email);
        // Always run a hash comparison so response time does not reveal which emails exist.
        $hash = $user['password_hash'] ?? '$2y$10$kKvfYp19DUpU5LBQZ52fT.e078mFZh9jyMJ1tjPrjJxUfPDR9MeLK';
        $valid = password_verify($password, $hash) && $user !== null;

        if (!$valid) {
            $this->users->logLoginFailure($email, $ip);
            return ['ok' => false, 'error' => 'Incorrect email or password.', 'user_id' => null];
        }

        $this->users->clearLoginFailures($email);
        if (password_needs_rehash($user['password_hash'], PASSWORD_DEFAULT)) {
            $this->users->updatePasswordHash((int) $user['id'], password_hash($password, PASSWORD_DEFAULT));
        }
        $this->users->touchLogin((int) $user['id']);

        return ['ok' => true, 'error' => null, 'user_id' => (int) $user['id']];
    }

    public static function signIn(int $userId): void
    {
        session_regenerate_id(true);
        unset($_SESSION['csrf']);
        $_SESSION['user_id'] = $userId;
        current_user(true);
    }

    public static function signOut(): void
    {
        unset($_SESSION['user_id'], $_SESSION['csrf']);
        session_regenerate_id(true);
        current_user(true);
    }
}
