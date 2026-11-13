<?php

declare(strict_types=1);

namespace App;

use PDO;
use PDOException;

final class Database
{
    private static ?PDO $connection = null;

    /** Lets tests (or another environment) supply their own connection. */
    public static function setConnection(?PDO $pdo): void
    {
        self::$connection = $pdo;
    }

    public static function getConnection(): PDO
    {
        if (self::$connection instanceof PDO) {
            return self::$connection;
        }

        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ];

        try {
            $connection = new PDO(
                'mysql:host=' . DB_HOST . ';port=' . DB_PORT . ';dbname=' . DB_NAME . ';charset=utf8mb4',
                DB_USER,
                DB_PASS,
                $options
            );
            // Keep MySQL's clock in step with PHP's (Philippines has no daylight saving time).
            $connection->exec("SET time_zone = '+08:00'");
            self::$connection = $connection;
        } catch (PDOException $e) {
            http_response_code(500);
            echo '<!doctype html><meta charset="utf-8"><title>Database error</title>';
            echo '<body style="font-family:system-ui,sans-serif;max-width:640px;margin:60px auto;padding:0 20px;line-height:1.5">';
            echo '<h1>Cannot connect to the database</h1>';
            echo '<p>Make sure MySQL is running in the XAMPP Control Panel and that you imported '
                . '<code>database/schema.sql</code> in phpMyAdmin (database name: <code>' . htmlspecialchars(DB_NAME) . '</code>).</p>';
            if (APP_DEBUG) {
                echo '<p><small>Details: ' . htmlspecialchars($e->getMessage()) . '</small></p>';
            }
            exit;
        }

        return self::$connection;
    }
}
