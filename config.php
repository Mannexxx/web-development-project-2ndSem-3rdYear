<?php

declare(strict_types=1);

/**
 * Bantayan Marketplace - bootstrap.
 * Every page starts with: require_once __DIR__ . '/config.php';
 *
 * XAMPP defaults are used below. Change them only if your MySQL setup differs.
 */

<<<<<<< HEAD
// Provider credentials belong in the ignored config.local.php or environment variables.
$providerConfigPath = __DIR__ . '/config.local.php';
$providerConfig = is_file($providerConfigPath) ? require $providerConfigPath : [];
if (!is_array($providerConfig)) {
    throw new RuntimeException('config.local.php must return an array.');
}

$providerSetting = static function (string $environment, string $key, string $default = '') use ($providerConfig): string {
    $value = getenv($environment);
    if ($value !== false && trim((string) $value) !== '') {
        return trim((string) $value);
    }
    return trim((string) ($providerConfig[$key] ?? $default));
};

// Defaults preserve the XAMPP setup; production database credentials belong in config.local.php.
define('DB_HOST', $providerSetting('DB_HOST', 'db_host', 'localhost'));
define('DB_PORT', (int) $providerSetting('DB_PORT', 'db_port', '3306'));
define('DB_NAME', $providerSetting('DB_NAME', 'db_name', 'bantayan_market'));
define('DB_USER', $providerSetting('DB_USER', 'db_user', 'root'));
define('DB_PASS', $providerSetting('DB_PASS', 'db_pass'));

define('APP_NAME', 'Bantayan Marketplace');
$debugSetting = strtolower($providerSetting('APP_DEBUG', 'app_debug', 'false'));
if (!in_array($debugSetting, ['true', 'false', '1', '0', 'on', 'off'], true)) {
    throw new RuntimeException('APP_DEBUG must be set to true or false.');
}
define('APP_DEBUG', filter_var($debugSetting, FILTER_VALIDATE_BOOLEAN));
define('APP_TZ', 'Asia/Manila');
define('MAX_PHOTOS', 8);
define('MAX_UPLOAD_BYTES', 5 * 1024 * 1024);
define('ASSET_VERSION', '28');

define('SMTP_HOST', $providerSetting('SMTP_HOST', 'smtp_host'));
define('SMTP_PORT', (int) $providerSetting('SMTP_PORT', 'smtp_port', '587'));
define('SMTP_USERNAME', $providerSetting('SMTP_USERNAME', 'smtp_username'));
define('SMTP_PASSWORD', $providerSetting('SMTP_PASSWORD', 'smtp_password'));
define('SMTP_ENCRYPTION', $providerSetting('SMTP_ENCRYPTION', 'smtp_encryption', 'tls'));
define('SMTP_FROM_EMAIL', $providerSetting('SMTP_FROM_EMAIL', 'smtp_from_email'));
define('SMTP_FROM_NAME', $providerSetting('SMTP_FROM_NAME', 'smtp_from_name', APP_NAME));
define('GOOGLE_CLIENT_ID', $providerSetting('GOOGLE_CLIENT_ID', 'google_client_id'));
define('GOOGLE_CLIENT_SECRET', $providerSetting('GOOGLE_CLIENT_SECRET', 'google_client_secret'));
define('GOOGLE_REDIRECT_URI', $providerSetting('GOOGLE_REDIRECT_URI', 'google_redirect_uri'));

if (is_file(__DIR__ . '/vendor/autoload.php')) {
    require_once __DIR__ . '/vendor/autoload.php';
}
=======
// ---- Database (XAMPP default: user "root", empty password) -----------------
const DB_HOST = 'localhost';
const DB_PORT = 3306;
const DB_NAME = 'bantayan_market';
const DB_USER = 'root';
const DB_PASS = '';

// ---- Application ------------------------------------------------------------
const APP_NAME  = 'Bantayan Marketplace';
const APP_DEBUG = true;                 // set to false on a public server
const APP_TZ    = 'Asia/Manila';
const MAX_PHOTOS = 8;
const MAX_UPLOAD_BYTES = 5 * 1024 * 1024; // 5 MB per file
const ASSET_VERSION = '1';
>>>>>>> 730317b9a6c05588ee6649cb65a4921ce2d6c073

date_default_timezone_set(APP_TZ);
mb_internal_encoding('UTF-8');

if (APP_DEBUG) {
    ini_set('display_errors', '1');
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', '0');
}

define('APP_ROOT', str_replace('\\', '/', __DIR__));
define('VIEW_PATH', APP_ROOT . '/views');
define('UPLOAD_PATH', APP_ROOT . '/uploads');
define('STORAGE_PATH', APP_ROOT . '/storage');

/**
 * Web path of this folder, e.g. "/marketplace" when installed in htdocs/marketplace,
 * or "" when the project is the web root.
 */
(static function (): void {
    $docRoot = realpath($_SERVER['DOCUMENT_ROOT'] ?? '') ?: '';
    $docRoot = rtrim(str_replace('\\', '/', $docRoot), '/');
    $root = APP_ROOT;
    $base = '';
    if ($docRoot !== '' && stripos($root, $docRoot) === 0) {
        $base = substr($root, strlen($docRoot));
    }
    define('BASE_PATH', rtrim($base, '/'));
})();

// ---- Autoloader: App\Foo\Bar -> src/Foo/Bar.php ------------------------------
spl_autoload_register(static function (string $class): void {
    $prefix = 'App\\';
    if (strncmp($class, $prefix, strlen($prefix)) !== 0) {
        return;
    }
    $path = APP_ROOT . '/src/' . str_replace('\\', '/', substr($class, strlen($prefix))) . '.php';
    if (is_file($path)) {
        require_once $path;
    }
});

// ---- Session -------------------------------------------------------------------
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
<<<<<<< HEAD
        'secure' => isset($_SERVER['HTTPS']) && strtolower((string) $_SERVER['HTTPS']) !== 'off',
=======
>>>>>>> 730317b9a6c05588ee6649cb65a4921ce2d6c073
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_name('bantayan_session');
    session_start();
}

require_once APP_ROOT . '/src/helpers.php';
