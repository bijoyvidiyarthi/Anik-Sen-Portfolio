<?php
declare(strict_types=1);

namespace App;

class GalleryCategory
{
    public static function all(): array
    {
        return Database::pdo()->query(
            "SELECT * FROM gallery_categories ORDER BY parent_id IS NULL DESC, parent_id, sort_order, id"
        )->fetchAll();
    }

    /** Categories with image counts. */
    public static function withCounts(): array
    {
        $rows = Database::pdo()->query(
            "SELECT c.*, (SELECT COUNT(*) FROM gallery_images i WHERE i.category_id = c.id) AS image_count
             FROM gallery_categories c
             ORDER BY c.parent_id IS NULL DESC, c.parent_id, c.sort_order, c.id"
        )->fetchAll();
        return $rows;
    }

    public static function tree(): array
    {
        $all = self::withCounts();
        $tree = [];
        $byId = [];
        foreach ($all as $c) {
            $c["children"] = [];
            $byId[$c["id"]] = $c;
        }
        foreach ($byId as $id => $c) {
            if (!empty($c["parent_id"]) && isset($byId[$c["parent_id"]])) {
                $byId[$c["parent_id"]]["children"][] = &$byId[$id];
            }
        }
        foreach ($byId as $id => $c) {
            if (empty($c["parent_id"])) {
                $tree[] = $byId[$id];
            }
        }
        return $tree;
    }

    public static function find(int $id): ?array
    {
        $stmt = Database::pdo()->prepare("SELECT * FROM gallery_categories WHERE id=:id");
        $stmt->execute([":id" => $id]);
        return $stmt->fetch() ?: null;
    }

    public static function findBySlug(string $slug): ?array
    {
        $stmt = Database::pdo()->prepare("SELECT * FROM gallery_categories WHERE slug=:s");
        $stmt->execute([":s" => $slug]);
        return $stmt->fetch() ?: null;
    }

    public static function create(array $d): int
    {
        $slug = self::uniqueSlug($d["name"], (int)($d["parent_id"] ?? 0));
        Database::pdo()->prepare(
            "INSERT INTO gallery_categories (name, parent_id, slug, description, sort_order)
             VALUES (:n,:p,:s,:d,:o)"
        )->execute([
            ":n" => $d["name"], ":p" => !empty($d["parent_id"]) ? (int)$d["parent_id"] : null,
            ":s" => $slug, ":d" => $d["description"] ?? "",
            ":o" => (int)($d["sort_order"] ?? 0),
        ]);
        return (int) Database::pdo()->lastInsertId();
    }

    public static function update(int $id, array $d): void
    {
        Database::pdo()->prepare(
            "UPDATE gallery_categories SET name=:n, parent_id=:p, description=:d, sort_order=:o WHERE id=:id"
        )->execute([
            ":n" => $d["name"], ":p" => !empty($d["parent_id"]) ? (int)$d["parent_id"] : null,
            ":d" => $d["description"] ?? "", ":o" => (int)($d["sort_order"] ?? 0),
            ":id" => $id,
        ]);
    }

    public static function delete(int $id): void
    {
        // Move children to top-level rather than orphan-cascade
        Database::pdo()->prepare("UPDATE gallery_categories SET parent_id = NULL WHERE parent_id = :id")
            ->execute([":id" => $id]);
        Database::pdo()->prepare("DELETE FROM gallery_categories WHERE id=:id")->execute([":id"=>$id]);
    }

    private static function uniqueSlug(string $name, int $parentId = 0): string
    {
        $base = Upload::slug($name);
        $slug = $base;
        $i = 1;
        while (self::findBySlug($slug)) {
            $i++;
            $slug = $base . "-" . $i;
        }
        return $slug;
    }
}
