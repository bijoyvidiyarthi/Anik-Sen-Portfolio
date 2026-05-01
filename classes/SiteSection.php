<?php
declare(strict_types=1);

namespace App;

/**
 * Section toggle registry — controls which frontend sections render.
 */
class SiteSection
{
    /** @var array<string,bool>|null */
    private static ?array $map = null;

    /** Return all sections ordered for the admin UI. */
    public static function all(): array
    {
        return Database::pdo()
            ->query("SELECT * FROM site_sections ORDER BY sort_order, id")
            ->fetchAll();
    }

    /** Quick lookup: is the given section key currently visible? */
    public static function isVisible(string $key): bool
    {
        if (self::$map === null) {
            self::$map = [];
            $rows = Database::pdo()
                ->query("SELECT `key`, is_visible FROM site_sections")
                ->fetchAll();
            foreach ($rows as $r) {
                self::$map[$r["key"]] = ((int) $r["is_visible"]) === 1;
            }
        }
        // Default to visible if a section is not registered yet.
        return self::$map[$key] ?? true;
    }

    public static function setVisible(string $key, bool $visible): void
    {
        Database::pdo()
            ->prepare("UPDATE site_sections SET is_visible = :v, updated_at = CURRENT_TIMESTAMP WHERE `key` = :k")
            ->execute([":v" => $visible ? 1 : 0, ":k" => $key]);
        self::$map = null;
    }
}
