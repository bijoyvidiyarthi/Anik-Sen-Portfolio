<?php
declare(strict_types=1);

namespace App;

class Client
{
    public static function all(bool $visibleOnly = false): array
    {
        $sql = "SELECT * FROM clients"
             . ($visibleOnly ? " WHERE is_visible = 1" : "")
             . " ORDER BY sort_order, id";
        return Database::pdo()->query($sql)->fetchAll();
    }

    public static function find(int $id): ?array
    {
        $stmt = Database::pdo()->prepare("SELECT * FROM clients WHERE id = :id");
        $stmt->execute([":id" => $id]);
        return $stmt->fetch() ?: null;
    }

    public static function create(array $d, ?string $logo = null): int
    {
        Database::pdo()->prepare(
            "INSERT INTO clients (name, logo, link_url, sort_order, is_visible)
             VALUES (:n, :l, :u, :s, :v)"
        )->execute([
            ":n" => trim((string)($d["name"] ?? "")),
            ":l" => (string)($logo ?? ($d["logo"] ?? "")),
            ":u" => trim((string)($d["link_url"] ?? "")),
            ":s" => (int)($d["sort_order"] ?? 0),
            ":v" => !empty($d["is_visible"]) ? 1 : 0,
        ]);
        return (int)Database::pdo()->lastInsertId();
    }

    public static function update(int $id, array $d, ?string $logo = null): void
    {
        $sql = "UPDATE clients SET name=:n, link_url=:u, sort_order=:s, is_visible=:v"
             . ($logo !== null ? ", logo=:l" : "")
             . " WHERE id=:id";
        $params = [
            ":n"  => trim((string)($d["name"] ?? "")),
            ":u"  => trim((string)($d["link_url"] ?? "")),
            ":s"  => (int)($d["sort_order"] ?? 0),
            ":v"  => !empty($d["is_visible"]) ? 1 : 0,
            ":id" => $id,
        ];
        if ($logo !== null) $params[":l"] = $logo;
        Database::pdo()->prepare($sql)->execute($params);
    }

    public static function toggleVisibility(int $id): void
    {
        Database::pdo()->prepare(
            "UPDATE clients SET is_visible = CASE WHEN is_visible = 1 THEN 0 ELSE 1 END WHERE id=:id"
        )->execute([":id" => $id]);
    }

    public static function delete(int $id): ?string
    {
        $row = self::find($id);
        if (!$row) return null;
        Database::pdo()->prepare("DELETE FROM clients WHERE id=:id")->execute([":id" => $id]);
        return (string)$row["logo"];
    }
}
