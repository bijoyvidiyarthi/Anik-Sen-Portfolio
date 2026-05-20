<?php
declare(strict_types=1);

namespace App;

class GalleryImage
{
    public static function inCategory(int $categoryId): array
    {
        $stmt = Database::pdo()->prepare(
            "SELECT * FROM gallery_images WHERE category_id = :c ORDER BY sort_order, id"
        );
        $stmt->execute([":c" => $categoryId]);
        return $stmt->fetchAll();
    }

    public static function find(int $id): ?array
    {
        $stmt = Database::pdo()->prepare("SELECT * FROM gallery_images WHERE id = :id");
        $stmt->execute([":id" => $id]);
        return $stmt->fetch() ?: null;
    }

    public static function create(
        int $categoryId,
        string $filename,
        string $title = "",
        string $alt = "",
        int $sort = 0,
        string $mediaType = "image",
        string $videoPath = "",
        ?int $projectId = null
    ): int {
        Database::pdo()->prepare(
            "INSERT INTO gallery_images
               (category_id, filename, title, alt_text, sort_order, media_type, video_path, project_id)
             VALUES (:c,:f,:t,:a,:s,:mt,:vp,:pid)"
        )->execute([
            ":c"   => $categoryId,
            ":f"   => $filename,
            ":t"   => $title,
            ":a"   => $alt,
            ":s"   => $sort,
            ":mt"  => $mediaType,
            ":vp"  => $videoPath,
            ":pid" => $projectId,
        ]);
        return (int) Database::pdo()->lastInsertId();
    }

    public static function update(int $id, array $d): void
    {
        Database::pdo()->prepare(
            "UPDATE gallery_images
             SET title=:t, alt_text=:a, sort_order=:s, category_id=:c, media_type=:mt, video_path=:vp
             WHERE id=:id"
        )->execute([
            ":t"  => $d["title"]      ?? "",
            ":a"  => $d["alt_text"]   ?? "",
            ":s"  => (int)($d["sort_order"] ?? 0),
            ":c"  => (int)$d["category_id"],
            ":mt" => $d["media_type"] ?? "image",
            ":vp" => $d["video_path"] ?? "",
            ":id" => $id,
        ]);
    }

    public static function delete(int $id, string $imageDir): void
    {
        $row = self::find($id);
        if (!$row) return;
        // Never delete physical files for auto-synced project videos (managed separately).
        // Also skip physical deletion for icons/SVGs that may be shared site assets.
        $skip = in_array($row["media_type"], ["icon", "logo", "video"], true)
             || !empty($row["project_id"]);
        if (!$skip) {
            Upload::delete($imageDir, $row["filename"]);
        }
        Database::pdo()->prepare("DELETE FROM gallery_images WHERE id=:id")->execute([":id" => $id]);
    }

    public static function totalCount(): int
    {
        return (int) Database::pdo()->query("SELECT COUNT(*) FROM gallery_images")->fetchColumn();
    }

    public static function countByType(): array
    {
        $rows = Database::pdo()->query(
            "SELECT media_type, COUNT(*) AS cnt FROM gallery_images GROUP BY media_type"
        )->fetchAll();
        $out = [];
        foreach ($rows as $r) {
            $out[$r["media_type"]] = (int)$r["cnt"];
        }
        return $out;
    }

    /**
     * Upsert a gallery entry in the "Videos" category for a project.
     * Called automatically when a video project is created or updated.
     *
     * @param int         $projectId  The projects.id
     * @param string      $title      Project title (used as gallery title)
     * @param string|null $videoFile  Local filename in /uploads/videos/ (may be null)
     * @param string|null $videoUrl   YouTube / Vimeo URL (fallback when no local file)
     */
    public static function syncProjectVideo(
        int $projectId,
        string $title,
        ?string $videoFile,
        ?string $videoUrl
    ): void {
        $pdo = Database::pdo();

        // Resolve the Videos gallery category.
        $videoCatId = (int)$pdo->query(
            "SELECT id FROM gallery_categories WHERE name='Videos' LIMIT 1"
        )->fetchColumn();
        if (!$videoCatId) return; // Videos category doesn't exist yet — skip gracefully.

        $videoPath = "";
        if (!empty($videoFile)) {
            $videoPath = "/uploads/videos/" . $videoFile;
        } elseif (!empty($videoUrl)) {
            $videoPath = $videoUrl;
        }

        if ($videoPath === "") {
            // No video material — remove any stale gallery entry.
            self::removeProjectVideo($projectId);
            return;
        }

        // Check if a gallery entry already exists for this project.
        $stmt = $pdo->prepare(
            "SELECT id FROM gallery_images WHERE project_id=:pid AND category_id=:cid LIMIT 1"
        );
        $stmt->execute([":pid" => $projectId, ":cid" => $videoCatId]);
        $existingId = (int)$stmt->fetchColumn();

        $fn = !empty($videoFile) ? $videoFile : "";

        if ($existingId) {
            // Update the existing entry.
            $pdo->prepare(
                "UPDATE gallery_images
                 SET title=:t, filename=:f, video_path=:vp, media_type='video'
                 WHERE id=:id"
            )->execute([
                ":t"  => $title,
                ":f"  => $fn,
                ":vp" => $videoPath,
                ":id" => $existingId,
            ]);
        } else {
            // Insert a new gallery entry.
            $pdo->prepare(
                "INSERT INTO gallery_images
                   (category_id, filename, title, alt_text, sort_order, media_type, video_path, project_id)
                 VALUES (:cat,:f,:t,:a,:s,'video',:vp,:pid)"
            )->execute([
                ":cat" => $videoCatId,
                ":f"   => $fn,
                ":t"   => $title,
                ":a"   => "Auto-synced video project",
                ":s"   => $projectId * 10,
                ":vp"  => $videoPath,
                ":pid" => $projectId,
            ]);
        }
    }

    /**
     * Remove the auto-synced gallery entry when a project is deleted or
     * converted away from the "video" media kind.
     */
    public static function removeProjectVideo(int $projectId): void
    {
        Database::pdo()->prepare(
            "DELETE FROM gallery_images WHERE project_id=:pid AND media_type='video'"
        )->execute([":pid" => $projectId]);
    }
}
