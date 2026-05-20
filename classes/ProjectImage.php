<?php
declare(strict_types=1);

namespace App;

class ProjectImage
{
    public static function forProject(int $projectId): array
    {
        $stmt = Database::pdo()->prepare(
            "SELECT * FROM project_images WHERE project_id = :pid ORDER BY sort_order, id"
        );
        $stmt->execute([":pid" => $projectId]);
        return $stmt->fetchAll();
    }

    public static function find(int $id): ?array
    {
        $stmt = Database::pdo()->prepare("SELECT * FROM project_images WHERE id = :id");
        $stmt->execute([":id" => $id]);
        return $stmt->fetch() ?: null;
    }

    public static function add(int $projectId, string $filename, string $alt = "", int $sort = 0): int
    {
        Database::pdo()->prepare(
            "INSERT INTO project_images (project_id, filename, alt_text, sort_order)
             VALUES (:p,:f,:a,:s)"
        )->execute([
            ":p" => $projectId, ":f" => $filename, ":a" => $alt, ":s" => $sort,
        ]);
        return (int)Database::pdo()->lastInsertId();
    }

    public static function delete(int $id): ?string
    {
        $row = self::find($id);
        if (!$row) return null;
        Database::pdo()->prepare("DELETE FROM project_images WHERE id = :id")->execute([":id" => $id]);
        return (string)$row["filename"];
    }
}
