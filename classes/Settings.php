<?php
declare(strict_types=1);

namespace App;

class Settings
{
    /** @var array<string,string>|null */
    private static ?array $cache = null;

    public static function all(): array
    {
        if (self::$cache !== null) return self::$cache;
        $rows = Database::pdo()->query("SELECT `key`, value FROM settings")->fetchAll();
        $out = [];
        foreach ($rows as $r) { $out[$r["key"]] = (string) $r["value"]; }
        return self::$cache = $out;
    }

    public static function get(string $key, string $default = ""): string
    {
        $all = self::all();
        return $all[$key] ?? $default;
    }

    public static function set(string $key, string $value): void
    {
        $pdo = Database::pdo();
        $stmt = $pdo->prepare("UPDATE settings SET value = :v WHERE `key` = :k");
        $stmt->execute([":v" => $value, ":k" => $key]);
        if ($stmt->rowCount() === 0) {
            $pdo->prepare("INSERT INTO settings (`key`, value) VALUES (:k,:v)")
                ->execute([":k" => $key, ":v" => $value]);
        }
        self::$cache = null;
    }

    public static function setMany(array $kv): void
    {
        foreach ($kv as $k => $v) {
            self::set((string) $k, (string) $v);
        }
    }
}
