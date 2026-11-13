<?php

declare(strict_types=1);

/**
 * Bantayan Marketplace - bootstrap.
 * Every page starts with: require_once __DIR__ . '/config.php';
 *
 * XAMPP defaults are used below. Change them only if your MySQL setup differs.
 */

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
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_name('bantayan_session');
    session_start();
}

require_once APP_ROOT . '/src/helpers.php';
