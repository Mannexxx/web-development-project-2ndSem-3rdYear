<?php

declare(strict_types=1);

if (!defined('APP_ROOT')) {
    http_response_code(403);
    exit;
}

use App\Database;

/* ---------------------------------------------------------------------------
 * Output, URLs, redirects
 * ------------------------------------------------------------------------- */

function e(mixed $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/** Absolute web path to a file inside this project. url('index.php?x=1') */
function url(string $path = ''): string
{
    return BASE_PATH . '/' . ltrim($path, '/');
}

function asset(string $path): string
{
    return url('assets/' . ltrim($path, '/')) . '?v=' . ASSET_VERSION;
}

function redirect(string $path): never
{
    $target = str_starts_with($path, '/') || preg_match('#^https?://#i', $path) ? $path : url($path);
    header('Location: ' . $target);
    exit;
}

/** Only allows local "page.php?query" redirects, never external sites. */
function safe_next(?string $next, string $default = 'index.php'): string
{
    $next = trim((string) $next);
    if ($next !== '' && preg_match('#^[A-Za-z0-9_\-/]+\.php(\?[A-Za-z0-9_=&%.\-]*)?$#', $next)) {
        return $next;
    }
    return $default;
}

function is_post(): bool
{
    return ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST';
}

function json_response(array $data, int $status = 200): never
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store');
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

/** Read the JSON body of an API request. */
function json_input(): array
{
    $raw = file_get_contents('php://input');
    $data = json_decode($raw === false ? '' : $raw, true);
    return is_array($data) ? $data : [];
}

/* ---------------------------------------------------------------------------
 * Formatting
 * ------------------------------------------------------------------------- */

function money(float|int|string|null $amount): string
{
    $n = (float) $amount;
    $decimals = abs($n - round($n)) < 0.005 ? 0 : 2;
    return '₱' . number_format($n, $decimals);
}

function now(): string
{
    return date('Y-m-d H:i:s');
}

function time_ago(?string $datetime): string
{
    if (!$datetime) {
        return '';
    }
    $ts = strtotime($datetime);
    if ($ts === false) {
        return '';
    }
    $diff = time() - $ts;
    if ($diff < 60) {
        return 'just now';
    }
    if ($diff < 3600) {
        $m = intdiv($diff, 60);
        return $m . ($m === 1 ? ' minute ago' : ' minutes ago');
    }
    if ($diff < 86400) {
        $h = intdiv($diff, 3600);
        return $h . ($h === 1 ? ' hour ago' : ' hours ago');
    }
    if ($diff < 86400 * 30) {
        $d = intdiv($diff, 86400);
        return $d . ($d === 1 ? ' day ago' : ' days ago');
    }
    return date('M j, Y', $ts);
}

function date_pretty(?string $datetime): string
{
    $ts = $datetime ? strtotime($datetime) : false;
    return $ts ? date('M j, Y', $ts) : '';
}

function condition_label(?string $c): string
{
    return match ($c) {
        'new' => 'New',
        'like_new' => 'Like new',
        'used' => 'Used',
        default => '',
    };
}

function listing_status_label(string $s): string
{
    return match ($s) {
        'draft' => 'Draft',
        'pending' => 'Pending review',
        'changes_requested' => 'Changes requested',
        'approved' => 'Live',
        'rejected' => 'Rejected',
        'removed' => 'Removed',
        'sold' => 'Sold',
        default => ucfirst($s),
    };
}

function reason_label(string $r): string
{
    return match ($r) {
        'misleading_listing' => 'Misleading listing',
        'prohibited_item' => 'Prohibited item',
        'other' => 'Other',
        default => ucfirst(str_replace('_', ' ', $r)),
    };
}

function ticket_category_label(string $c): string
{
    return match ($c) {
        'account' => 'Account help',
        'face_verification' => 'Face verification help',
        'listing' => 'Listing question',
        'transaction' => 'Transaction concern',
        default => 'Other',
    };
}

function full_name(array $row, string $prefix = ''): string
{
    return trim(($row[$prefix . 'firstname'] ?? '') . ' ' . ($row[$prefix . 'lastname'] ?? ''));
}

/** "Ana R." - the public form of a name used for reviewers and reporters. */
function short_name(array $row, string $prefix = ''): string
{
    $first = (string) ($row[$prefix . 'firstname'] ?? '');
    $last = (string) ($row[$prefix . 'lastname'] ?? '');
    return trim($first . ($last !== '' ? ' ' . mb_strtoupper(mb_substr($last, 0, 1)) . '.' : ''));
}

function town_label(string $town): string
{
    return $town . ', Cebu';
}

/** Turns "₱12,500" or "12500.5" into a float, or null if it is not a valid amount. */
function parse_price(string $input): ?float
{
    $clean = str_ireplace(['₱', 'php', ',', ' '], '', trim($input));
    if (!preg_match('/^\d{1,7}(\.\d{1,2})?$/', $clean)) {
        return null;
    }
    return (float) $clean;
}

function excerpt(string $text, int $length = 90): string
{
    $text = trim(preg_replace('/\s+/', ' ', $text) ?? '');
    return mb_strlen($text) > $length ? rtrim(mb_substr($text, 0, $length - 1)) . '…' : $text;
}

/* ---------------------------------------------------------------------------
 * Flash messages
 * ------------------------------------------------------------------------- */

/** @param string|string[] $message */
function flash(string $type, string|array $message): void
{
    $_SESSION['flash'][] = ['type' => $type, 'messages' => (array) $message];
}

function consume_flashes(): array
{
    $all = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $all;
}

/* ---------------------------------------------------------------------------
 * CSRF
 * ------------------------------------------------------------------------- */

function csrf_token(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf" value="' . e(csrf_token()) . '">';
}

function csrf_valid(): bool
{
    $sent = $_POST['csrf'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
    return is_string($sent) && $sent !== '' && hash_equals(csrf_token(), $sent);
}

/** Call at the top of every POST handler. */
function verify_csrf(): void
{
    if (!csrf_valid()) {
        if (str_contains($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json') || isset($_SERVER['HTTP_X_CSRF_TOKEN'])) {
            json_response(['ok' => false, 'message' => 'Your session expired. Please reload the page.'], 419);
        }
        abort(419, 'Your session expired. Please go back, reload the page and try again.');
    }
}

/* ---------------------------------------------------------------------------
 * Current user & access control
 * ------------------------------------------------------------------------- */

function repo(string $class): object
{
    static $cache = [];
    return $cache[$class] ??= new $class(Database::getConnection());
}

function current_user(bool $refresh = false): ?array
{
    static $user = null;
    static $loaded = false;
    if ($refresh) {
        $loaded = false;
    }
    if (!$loaded) {
        $loaded = true;
        $user = null;
        $id = (int) ($_SESSION['user_id'] ?? 0);
        if ($id > 0) {
            $user = repo(App\Repository\UserRepository::class)->find($id);
            if ($user === null) {
                unset($_SESSION['user_id']);
            }
        }
    }
    return $user;
}

function is_admin(?array $user = null): bool
{
    $user ??= current_user();
    return $user !== null && $user['role'] === 'admin';
}

function is_verified(?array $user = null): bool
{
    $user ??= current_user();
    return $user !== null && !empty($user['face_verified_at']);
}

function require_login(string $message = 'Please sign in to continue.'): array
{
    $user = current_user();
    if ($user === null) {
        flash('info', $message);
        $self = ltrim(substr((string) ($_SERVER['REQUEST_URI'] ?? ''), strlen(BASE_PATH)), '/');
        $next = is_post() ? '' : safe_next($self, '');
        redirect('login.php' . ($next !== '' ? '?next=' . rawurlencode($next) : ''));
    }
    return $user;
}

/** Signed in AND face-verified AND not restricted. Used for messaging and selling. */
function require_verified(string $action = 'do this'): array
{
    $user = require_login('Please sign in to ' . $action . '.');
    if (is_admin($user)) {
        return $user;
    }
    if (!is_verified($user)) {
        flash('info', 'Verify your profile with face verification to ' . $action . '.');
        redirect('register.php?step=verify');
    }
    if ($user['status'] === 'restricted') {
        flash('error', 'Your account is restricted because of repeated guideline violations. Please contact customer service.');
        redirect('support.php');
    }
    return $user;
}

function require_admin(): array
{
    $user = current_user();
    if ($user === null) {
        flash('info', 'Please sign in with an admin account.');
        redirect('login.php?next=' . rawurlencode('admin/index.php'));
    }
    if (!is_admin($user)) {
        abort(403, 'This area is for the admin team only.');
    }
    return $user;
}

/** Stable anonymous key for tracking a guest's browsing session. */
function view_session_key(): string
{
    if (empty($_SESSION['view_key'])) {
        $_SESSION['view_key'] = bin2hex(random_bytes(16));
    }
    return $_SESSION['view_key'];
}

/* ---------------------------------------------------------------------------
 * Rendering
 * ------------------------------------------------------------------------- */

/**
 * Render views/<view>.php inside views/layout.php.
 *
 * Layout options: title, tab (explore|sell|messages|profile), body_class,
 * scripts (array of js files), mobile_header (array|null), footer (array|null),
 * chrome ('site' | 'admin'), hide_tabs (bool)
 */
function render(string $view, array $data = [], array $layout = []): void
{
    $me = current_user();
    $layout += [
        'title' => APP_NAME,
        'tab' => null,
        'body_class' => '',
        'scripts' => [],
        'mobile_header' => null,
        'footer' => null,
        'chrome' => 'site',
        'hide_tabs' => false,
    ];
    extract($data, EXTR_SKIP);
    ob_start();
    require VIEW_PATH . '/' . $view . '.php';
    $content = (string) ob_get_clean();
    require VIEW_PATH . '/layout.php';
}

function abort(int $code, string $message = ''): never
{
    http_response_code($code);
    $titles = [403 => 'Not allowed', 404 => 'Page not found', 419 => 'Session expired', 500 => 'Something went wrong'];
    render('error', [
        'code' => $code,
        'heading' => $titles[$code] ?? 'Something went wrong',
        'message' => $message !== '' ? $message : 'Sorry, we could not find what you were looking for.',
    ], ['title' => ($titles[$code] ?? 'Error') . ' · ' . APP_NAME]);
    exit;
}

/** Small helper for templates: keep previously typed form values. */
function old(array $source, string $key, string $default = ''): string
{
    return e($source[$key] ?? $default);
}

/** Query string for the current page with some parameters replaced/removed. */
function query_with(array $params, array $replace = []): string
{
    $merged = array_merge($params, $replace);
    $merged = array_filter($merged, static fn($v) => $v !== null && $v !== '');
    return $merged === [] ? '' : '?' . http_build_query($merged);
}

/** "★★★★☆" for a whole-number rating (rounded). */
function stars(float|int|null $rating): string
{
    $n = max(0, min(5, (int) round((float) $rating)));
    return str_repeat('★', $n) . str_repeat('☆', 5 - $n);
}
