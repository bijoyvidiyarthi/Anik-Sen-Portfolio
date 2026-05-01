<?php
declare(strict_types=1);

namespace App;

class About
{
    public static function get(): array
    {
        return Database::pdo()->query("SELECT * FROM about_content ORDER BY id LIMIT 1")->fetch() ?: [];
    }

    public static function paragraphs(): array
    {
        $row = self::get();
        $bio = (string)($row["bio"] ?? "");
        $parts = preg_split('/\n\s*\n/', trim($bio)) ?: [];
        return array_values(array_filter(array_map("trim", $parts)));
    }

    public static function update(string $bio, ?string $profileImage = null): void
    {
        $row = self::get();
        $id = (int)($row["id"] ?? 0);
        if (!$id) {
            Database::pdo()->prepare("INSERT INTO about_content (bio, profile_image) VALUES (:b,:p)")
                ->execute([":b" => $bio, ":p" => (string)$profileImage]);
            return;
        }
        $sql = "UPDATE about_content SET bio = :b" . ($profileImage !== null ? ", profile_image = :p" : "") . " WHERE id = :id";
        $params = [":b" => $bio, ":id" => $id];
        if ($profileImage !== null) $params[":p"] = $profileImage;
        Database::pdo()->prepare($sql)->execute($params);
    }

    public static function expertise(): array
    {
        return Database::pdo()->query(
            "SELECT * FROM expertise_items ORDER BY sort_order, id"
        )->fetchAll();
    }

    public static function createExpertise(string $icon, string $title, string $desc, int $sort): int
    {
        Database::pdo()->prepare(
            "INSERT INTO expertise_items (icon, title, description, sort_order) VALUES (:i,:t,:d,:s)"
        )->execute([":i"=>$icon, ":t"=>$title, ":d"=>$desc, ":s"=>$sort]);
        return (int) Database::pdo()->lastInsertId();
    }

    public static function updateExpertise(int $id, string $icon, string $title, string $desc, int $sort): void
    {
        Database::pdo()->prepare(
            "UPDATE expertise_items SET icon=:i, title=:t, description=:d, sort_order=:s WHERE id=:id"
        )->execute([":i"=>$icon, ":t"=>$title, ":d"=>$desc, ":s"=>$sort, ":id"=>$id]);
    }

    public static function deleteExpertise(int $id): void
    {
        Database::pdo()->prepare("DELETE FROM expertise_items WHERE id = :id")->execute([":id"=>$id]);
    }
}
