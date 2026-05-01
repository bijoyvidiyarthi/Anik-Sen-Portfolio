<?php
declare(strict_types=1);

namespace App;

class Project
{
    public const MAIN_CATEGORIES = [
        "video"   => "Video Editing & Motion Graphics",
        "graphic" => "Graphic Design",
    ];

    public const SUB_CATEGORIES = [
        "video" => [
            "Product Ads",
            "Educational",
            "Business Promotion",
            "Wedding/Pre-wedding",
            "Documentary",
            "Explainer Videos",
            "Podcast",
            "Marketing Videos",
        ],
        "graphic" => [
            "Photo-cards (FB/Web)",
            "Logos",
            "Banners",
            "Posters",
            "Thumbnails",
        ],
    ];

    public const MEDIA_KINDS = [
        "video"   => "Video player (modal)",
        "gallery" => "Image gallery (lightbox)",
        "link"    => "External link only",
    ];

    public static function all(bool $publishedOnly = false): array
    {
        $sql = "SELECT * FROM projects" . ($publishedOnly ? " WHERE is_published = 1" : "")
             . " ORDER BY sort_order, id";
        return Database::pdo()->query($sql)->fetchAll();
    }

    public static function find(int $id): ?array
    {
        $stmt = Database::pdo()->prepare("SELECT * FROM projects WHERE id = :id");
        $stmt->execute([":id" => $id]);
        return $stmt->fetch() ?: null;
    }

    public static function create(array $d, ?string $image = null, ?string $videoFile = null, ?string $videoPoster = null): int
    {
        Database::pdo()->prepare(
            "INSERT INTO projects
             (title, category, main_category, sub_category, media_kind, video_url,
              video_file, video_poster, software, skills_used, image, tech_stack,
              description, project_url, sort_order, is_published)
             VALUES (:t,:c,:mc,:sc,:mk,:vu,:vf,:vp,:sw,:sk,:i,:ts,:de,:u,:s,:p)"
        )->execute(self::params($d, $image, true, $videoFile, $videoPoster, true, true));
        return (int) Database::pdo()->lastInsertId();
    }

    public static function update(int $id, array $d, ?string $image = null, ?string $videoFile = null, ?string $videoPoster = null): void
    {
        $sql = "UPDATE projects SET
                  title=:t, category=:c, main_category=:mc, sub_category=:sc,
                  media_kind=:mk, video_url=:vu, software=:sw, skills_used=:sk,
                  tech_stack=:ts, description=:de, project_url=:u,
                  sort_order=:s, is_published=:p"
            . ($image       !== null ? ", image=:i"          : "")
            . ($videoFile   !== null ? ", video_file=:vf"    : "")
            . ($videoPoster !== null ? ", video_poster=:vp"  : "")
            . " WHERE id=:id";
        $params = self::params($d, $image, $image !== null, $videoFile, $videoPoster, $videoFile !== null, $videoPoster !== null);
        $params[":id"] = $id;
        Database::pdo()->prepare($sql)->execute($params);
    }

    /** Clear a stored video file or poster reference (after the file is unlinked). */
    public static function clearMedia(int $id, string $field): void
    {
        if (!in_array($field, ["video_file", "video_poster"], true)) return;
        Database::pdo()->prepare("UPDATE projects SET {$field} = NULL WHERE id = :id")
            ->execute([":id" => $id]);
    }

    public static function delete(int $id): void
    {
        Database::pdo()->prepare("DELETE FROM project_images WHERE project_id = :id")
            ->execute([":id" => $id]);
        Database::pdo()->prepare("DELETE FROM projects WHERE id = :id")
            ->execute([":id" => $id]);
    }

    /**
     * Flip the is_published flag and return the new state (1 or 0).
     * Used by the admin "Live / Draft" quick-toggle button so a project
     * can be hidden from the public site without entering the editor.
     */
    public static function togglePublish(int $id): int
    {
        $row = self::find($id);
        if (!$row) return 0;
        $next = empty($row["is_published"]) ? 1 : 0;
        Database::pdo()->prepare("UPDATE projects SET is_published = :p WHERE id = :id")
            ->execute([":p" => $next, ":id" => $id]);
        return $next;
    }

    /** Build the params array used by both create() and update(). */
    private static function params(
        array $d,
        ?string $image,
        bool $includeImage,
        ?string $videoFile = null,
        ?string $videoPoster = null,
        bool $includeVideoFile = false,
        bool $includeVideoPoster = false
    ): array {
        $main = ($d["main_category"] ?? "graphic") === "video" ? "video" : "graphic";
        $sub  = trim((string)($d["sub_category"] ?? ""));
        $mk   = (string)($d["media_kind"] ?? ($main === "video" ? "video" : "gallery"));
        if (!isset(self::MEDIA_KINDS[$mk])) {
            $mk = $main === "video" ? "video" : "gallery";
        }

        // Software: accept either array (multi-select) or comma string.
        $sw = $d["software"] ?? "";
        if (is_array($sw)) {
            $sw = implode(",", array_filter(array_map("trim", $sw)));
        }

        // Mirror new main_category into legacy `category` so older queries still work.
        $legacy = $main === "video" ? "Video Editing" : "Graphic Design";

        $p = [
            ":t"  => trim((string)($d["title"] ?? "")),
            ":c"  => $legacy,
            ":mc" => $main,
            ":sc" => $sub,
            ":mk" => $mk,
            ":vu" => trim((string)($d["video_url"] ?? "")),
            ":sw" => (string)$sw,
            ":sk" => trim((string)($d["skills_used"] ?? "")),
            ":ts" => trim((string)($d["tech_stack"] ?? "")),
            ":de" => trim((string)($d["description"] ?? "")),
            ":u"  => trim((string)($d["project_url"] ?? "")),
            ":s"  => (int)($d["sort_order"] ?? 0),
            ":p"  => !empty($d["is_published"]) ? 1 : 0,
        ];
        if ($includeImage) {
            $p[":i"] = (string)$image;
        }
        // The video file / poster placeholders are only added when the
        // matching include flag is set, so UPDATE statements that don't
        // touch those columns won't bind unused parameters.
        if ($includeVideoFile)   $p[":vf"] = $videoFile;
        if ($includeVideoPoster) $p[":vp"] = $videoPoster;
        return $p;
    }
}
