<?php
declare(strict_types=1);

namespace App;

use PDO;
use PDOException;
use RuntimeException;

/**
 * Singleton PDO database connection.
 * Supports SQLite (default) and MySQL via config["db"]["driver"].
 */
class Database
{
    private static ?PDO $pdo = null;
    private static string $driver = "sqlite";

    public static function init(array $config): PDO
    {
        if (self::$pdo instanceof PDO) {
            return self::$pdo;
        }

        $driver = $config["db"]["driver"] ?? "sqlite";
        self::$driver = $driver;

        if ($driver === "sqlite") {
            $path = $config["db"]["sqlite"]["path"];
            $dir  = dirname($path);
            if (!is_dir($dir)) {
                @mkdir($dir, 0775, true);
            }
            $dsn = "sqlite:" . $path;
            $opts = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ];
            try {
                self::$pdo = new PDO($dsn, null, null, $opts);
                self::$pdo->exec("PRAGMA foreign_keys = ON");
                self::$pdo->exec("PRAGMA journal_mode = WAL");
            } catch (PDOException $e) {
                throw new RuntimeException("SQLite connection failed: " . $e->getMessage());
            }
            return self::$pdo;
        }

        // MySQL
        $c = $config["db"]["mysql"];
        $dsn = sprintf(
            "mysql:host=%s;port=%s;dbname=%s;charset=%s",
            $c["host"], $c["port"], $c["name"], $c["charset"]
        );
        try {
            self::$pdo = new PDO($dsn, $c["user"], $c["password"], [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]);
        } catch (PDOException $e) {
            throw new RuntimeException("MySQL connection failed: " . $e->getMessage());
        }
        return self::$pdo;
    }

    public static function pdo(): PDO
    {
        if (!self::$pdo instanceof PDO) {
            throw new RuntimeException("Database not initialized. Call Database::init() first.");
        }
        return self::$pdo;
    }

    public static function driver(): string
    {
        return self::$driver;
    }

    public static function isMysql(): bool
    {
        return self::$driver === "mysql";
    }
}
