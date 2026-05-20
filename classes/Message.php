<?php
declare(strict_types=1);

namespace App;

class Message
{
    public static function create(array $d): int
    {
        Database::pdo()->prepare(
            "INSERT INTO messages (name, email, subject, message) VALUES (:n,:e,:s,:m)"
        )->execute([
            ":n" => $d["name"], ":e" => $d["email"],
            ":s" => $d["subject"], ":m" => $d["message"],
        ]);
        return (int) Database::pdo()->lastInsertId();
    }

    public static function all(string $search = "", string $filter = "all"): array
    {
        $sql = "SELECT * FROM messages";
        $where = []; $params = [];
        if ($filter === "unread") $where[] = "is_read = 0";
        if ($filter === "read")   $where[] = "is_read = 1";
        if ($search !== "") {
            $where[] = "(name LIKE :s OR email LIKE :s OR subject LIKE :s OR message LIKE :s)";
            $params[":s"] = "%" . $search . "%";
        }
        if ($where) $sql .= " WHERE " . implode(" AND ", $where);
        $sql .= " ORDER BY created_at DESC";
        $stmt = Database::pdo()->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public static function find(int $id): ?array
    {
        $stmt = Database::pdo()->prepare("SELECT * FROM messages WHERE id=:id");
        $stmt->execute([":id"=>$id]);
        return $stmt->fetch() ?: null;
    }

    public static function markRead(int $id, bool $read = true): void
    {
        Database::pdo()->prepare("UPDATE messages SET is_read = :r WHERE id = :id")
            ->execute([":r" => $read ? 1 : 0, ":id" => $id]);
    }

    public static function delete(int $id): void
    {
        Database::pdo()->prepare("DELETE FROM messages WHERE id=:id")->execute([":id"=>$id]);
    }

    public static function unreadCount(): int
    {
        return (int) Database::pdo()->query("SELECT COUNT(*) FROM messages WHERE is_read = 0")->fetchColumn();
    }

    public static function totalCount(): int
    {
        return (int) Database::pdo()->query("SELECT COUNT(*) FROM messages")->fetchColumn();
    }
}
