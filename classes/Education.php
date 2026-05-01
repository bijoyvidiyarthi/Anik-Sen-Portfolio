<?php
declare(strict_types=1);

namespace App;

class Education
{
    public static function all(): array
    {
        return Database::pdo()->query("SELECT * FROM education ORDER BY sort_order, id")->fetchAll();
    }

    public static function find(int $id): ?array
    {
        $stmt = Database::pdo()->prepare("SELECT * FROM education WHERE id=:id");
        $stmt->execute([":id" => $id]);
        return $stmt->fetch() ?: null;
    }

    public static function create(array $d): int
    {
        Database::pdo()->prepare(
            "INSERT INTO education (year, degree, status, sort_order) VALUES (:y,:d,:s,:o)"
        )->execute([
            ":y" => $d["year"], ":d" => $d["degree"], ":s" => $d["status"],
            ":o" => (int)($d["sort_order"] ?? 0),
        ]);
        return (int) Database::pdo()->lastInsertId();
    }

    public static function update(int $id, array $d): void
    {
        Database::pdo()->prepare(
            "UPDATE education SET year=:y, degree=:d, status=:s, sort_order=:o WHERE id=:id"
        )->execute([
            ":y" => $d["year"], ":d" => $d["degree"], ":s" => $d["status"],
            ":o" => (int)($d["sort_order"] ?? 0), ":id" => $id,
        ]);
    }

    public static function delete(int $id): void
    {
        Database::pdo()->prepare("DELETE FROM education WHERE id=:id")->execute([":id"=>$id]);
    }
}
