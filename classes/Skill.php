<?php
declare(strict_types=1);

namespace App;

class Skill
{
    public static function all(?string $kind = null): array
    {
        $sql = "SELECT * FROM skills" . ($kind ? " WHERE kind = :k" : "") . " ORDER BY sort_order, id";
        $stmt = Database::pdo()->prepare($sql);
        $stmt->execute($kind ? [":k" => $kind] : []);
        return $stmt->fetchAll();
    }

    public static function find(int $id): ?array
    {
        $stmt = Database::pdo()->prepare("SELECT * FROM skills WHERE id = :id");
        $stmt->execute([":id" => $id]);
        return $stmt->fetch() ?: null;
    }

    public static function create(array $d): int
    {
        Database::pdo()->prepare(
            "INSERT INTO skills (name, kind, tag, letters, color, bg, sort_order)
             VALUES (:n,:k,:t,:l,:c,:b,:s)"
        )->execute([
            ":n" => $d["name"], ":k" => $d["kind"] ?? "creative",
            ":t" => $d["tag"] ?? "", ":l" => $d["letters"] ?? "",
            ":c" => $d["color"] ?? "", ":b" => $d["bg"] ?? "",
            ":s" => (int) ($d["sort_order"] ?? 0),
        ]);
        return (int) Database::pdo()->lastInsertId();
    }

    public static function update(int $id, array $d): void
    {
        Database::pdo()->prepare(
            "UPDATE skills SET name=:n, kind=:k, tag=:t, letters=:l, color=:c, bg=:b, sort_order=:s WHERE id=:id"
        )->execute([
            ":n" => $d["name"], ":k" => $d["kind"] ?? "creative",
            ":t" => $d["tag"] ?? "", ":l" => $d["letters"] ?? "",
            ":c" => $d["color"] ?? "", ":b" => $d["bg"] ?? "",
            ":s" => (int) ($d["sort_order"] ?? 0), ":id" => $id,
        ]);
    }

    public static function delete(int $id): void
    {
        Database::pdo()->prepare("DELETE FROM skills WHERE id = :id")->execute([":id" => $id]);
    }
}
