<?php
declare(strict_types=1);

namespace App;

/**
 * Header / footer navigation items, individually toggleable.
 */
class MenuItem
{
    /** All items, optionally filtered by location ('header' | 'footer'). */
    public static function all(?string $location = null): array
    {
        $sql = "SELECT * FROM menu_items";
        $params = [];
        if ($location !== null) {
            $sql .= " WHERE location = :loc";
            $params[":loc"] = $location;
        }
        $sql .= " ORDER BY location, sort_order, id";
        $stmt = Database::pdo()->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /** Only the visible items for a given location — for use on the frontend. */
    public static function visible(string $location): array
    {
        $stmt = Database::pdo()->prepare(
            "SELECT label, href FROM menu_items
             WHERE location = :loc AND is_visible = 1
             ORDER BY sort_order, id"
        );
        $stmt->execute([":loc" => $location]);
        return $stmt->fetchAll();
    }

    public static function find(int $id): ?array
    {
        $stmt = Database::pdo()->prepare("SELECT * FROM menu_items WHERE id = :id");
        $stmt->execute([":id" => $id]);
        return $stmt->fetch() ?: null;
    }

    public static function create(array $d): int
    {
        $stmt = Database::pdo()->prepare(
            "INSERT INTO menu_items (location, label, href, is_visible, sort_order)
             VALUES (:loc, :label, :href, :vis, :sort)"
        );
        $stmt->execute([
            ":loc"   => $d["location"] === "footer" ? "footer" : "header",
            ":label" => trim((string)($d["label"] ?? "")),
            ":href"  => trim((string)($d["href"] ?? "#")),
            ":vis"   => empty($d["is_visible"]) ? 0 : 1,
            ":sort"  => (int)($d["sort_order"] ?? 999),
        ]);
        return (int) Database::pdo()->lastInsertId();
    }

    public static function update(int $id, array $d): void
    {
        Database::pdo()->prepare(
            "UPDATE menu_items
                SET label = :label, href = :href, sort_order = :sort,
                    updated_at = CURRENT_TIMESTAMP
              WHERE id = :id"
        )->execute([
            ":label" => trim((string)($d["label"] ?? "")),
            ":href"  => trim((string)($d["href"] ?? "#")),
            ":sort"  => (int)($d["sort_order"] ?? 999),
            ":id"    => $id,
        ]);
    }

    public static function setVisible(int $id, bool $visible): void
    {
        Database::pdo()->prepare(
            "UPDATE menu_items SET is_visible = :v, updated_at = CURRENT_TIMESTAMP WHERE id = :id"
        )->execute([":v" => $visible ? 1 : 0, ":id" => $id]);
    }

    public static function delete(int $id): void
    {
        Database::pdo()->prepare("DELETE FROM menu_items WHERE id = :id")->execute([":id" => $id]);
    }
}
