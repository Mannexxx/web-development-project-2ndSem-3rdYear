<?php

declare(strict_types=1);

namespace App;

/** Small helpers for CSRF protection on browser forms. */
final class FormSecurity
{
    public static function token(): string
    {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return (string) $_SESSION['csrf_token'];
    }

    public static function validToken(mixed $submitted): bool
    {
        return is_string($submitted)
            && isset($_SESSION['csrf_token'])
            && hash_equals((string) $_SESSION['csrf_token'], $submitted);
    }

    /** Restrict post-auth redirects to known local pages and numeric product IDs. */
    public static function localReturnPath(mixed $candidate): string
    {
        if (!is_string($candidate)) {
            return 'index.php';
        }
<<<<<<< HEAD
        if (in_array($candidate, ['index.php', 'sell.php', 'messages.php', 'admin/index.php'], true)) {
            return $candidate;
        }
        if (preg_match('/\Aproduct\.php\?id=([1-9][0-9]*)\z/', $candidate, $matches)) {
            return 'listing.php?id=' . $matches[1];
=======
        if (in_array($candidate, ['index.php', 'sell.php', 'messages.php', 'admin.php'], true)) {
            return $candidate;
        }
        if (preg_match('/\Aproduct\.php\?id=([1-9][0-9]*)\z/', $candidate, $matches)) {
            return 'product.php?id=' . $matches[1];
>>>>>>> 730317b9a6c05588ee6649cb65a4921ce2d6c073
        }
        return 'index.php';
    }
}
