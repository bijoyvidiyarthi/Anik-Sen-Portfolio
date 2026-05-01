<?php
declare(strict_types=1);

namespace App;

class FileLibrary
{
    public static function all(?string $folder = null, string $search = ""): array
    {
        $where = [];
        $params = [];
        if ($folder !== null && $folder !== "") {
            $where[] = "folder = :f";
            $params[":f"] = $folder;
        }
        if ($search !== "") {
            $where[] = "(title LIKE :s OR original_name LIKE :s OR description LIKE :s)";
            $params[":s"] = "%" . $search . "%";
        }
        $sql = "SELECT * FROM file_library"
            . ($where ? " WHERE " . implode(" AND ", $where) : "")
            . " ORDER BY created_at DESC";
        $stmt = Database::pdo()->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public static function folders(): array
    {
        $rows = Database::pdo()->query(
            "SELECT folder, COUNT(*) AS n FROM file_library GROUP BY folder ORDER BY folder"
        )->fetchAll();
        return $rows;
    }

    public static function find(int $id): ?array
    {
        $stmt = Database::pdo()->prepare("SELECT * FROM file_library WHERE id=:id");
        $stmt->execute([":id" => $id]);
        return $stmt->fetch() ?: null;
    }

    public static function create(array $d): int
    {
        Database::pdo()->prepare(
            "INSERT INTO file_library (title, folder, filename, original_name, mime, size_bytes, description, is_active)
             VALUES (:t,:fo,:fn,:on,:m,:sz,:d,:a)"
        )->execute([
            ":t" => $d["title"], ":fo" => $d["folder"] ?: "general",
            ":fn" => $d["filename"], ":on" => $d["original_name"] ?? "",
            ":m" => $d["mime"] ?? "", ":sz" => (int)($d["size_bytes"] ?? 0),
            ":d" => $d["description"] ?? "",
            ":a" => !empty($d["is_active"]) ? 1 : 0,
        ]);
        return (int) Database::pdo()->lastInsertId();
    }

    public static function update(int $id, array $d): void
    {
        Database::pdo()->prepare(
            "UPDATE file_library SET title=:t, folder=:fo, description=:d, is_active=:a WHERE id=:id"
        )->execute([
            ":t" => $d["title"], ":fo" => $d["folder"] ?: "general",
            ":d" => $d["description"] ?? "",
            ":a" => !empty($d["is_active"]) ? 1 : 0,
            ":id" => $id,
        ]);
    }

    public static function delete(int $id, string $docDir): void
    {
        $row = self::find($id);
        if (!$row) return;
        Upload::delete($docDir, $row["filename"]);
        Database::pdo()->prepare("DELETE FROM file_library WHERE id=:id")->execute([":id" => $id]);
    }

    public static function activeCv(): ?array
    {
        $stmt = Database::pdo()->prepare(
            "SELECT * FROM file_library WHERE folder = 'cv' AND is_active = 1 ORDER BY created_at DESC LIMIT 1"
        );
        $stmt->execute();
        return $stmt->fetch() ?: null;
    }
}
