<?php
declare(strict_types=1);

namespace App;

class Review
{
    public static function all(): array
    {
        return Database::pdo()->query("SELECT * FROM reviews ORDER BY sort_order, id")->fetchAll();
    }

    public static function find(int $id): ?array
    {
        $stmt = Database::pdo()->prepare("SELECT * FROM reviews WHERE id=:id");
        $stmt->execute([":id" => $id]);
        return $stmt->fetch() ?: null;
    }

    public static function create(array $d): int
    {
        Database::pdo()->prepare(
            "INSERT INTO reviews (author, role, body, sort_order) VALUES (:a,:r,:b,:s)"
        )->execute([
            ":a" => $d["author"], ":r" => $d["role"], ":b" => $d["body"],
            ":s" => (int)($d["sort_order"] ?? 0),
        ]);
        return (int) Database::pdo()->lastInsertId();
    }

    public static function update(int $id, array $d): void
    {
        Database::pdo()->prepare(
            "UPDATE reviews SET author=:a, role=:r, body=:b, sort_order=:s WHERE id=:id"
        )->execute([
            ":a" => $d["author"], ":r" => $d["role"], ":b" => $d["body"],
            ":s" => (int)($d["sort_order"] ?? 0), ":id" => $id,
        ]);
    }

    public static function delete(int $id): void
    {
        Database::pdo()->prepare("DELETE FROM reviews WHERE id=:id")->execute([":id"=>$id]);
    }
}
