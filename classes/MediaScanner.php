<?php
declare(strict_types=1);

namespace App;

use PDO;
use Throwable;

/**
 * Centralised media + file aggregator.
 *
 * Pulls asset references from the database only — no filesystem scanning.
 * Only locally-uploaded filenames are included; external addresses stored
 * in the DB are skipped for security/privacy.
 */
class MediaScanner
{
    /**
     * Validates if $val is a plain local filename.
     */
    private static function isLocal(?string $val): bool
    {
        if (!$val) return false;
        // Allows alphanumeric, dots, and hyphens. Blocks slashes, schemes, and path traversal.
        return preg_match('/^\w[\w.\-]+$/', $val) === 1;
    }

    /**
     * Returns a list of every locally-uploaded image referenced by site content.
     */
    public static function images(): array
    {
        $pdo = Database::pdo();
        $out = [];

        // Definition of image sources: [table, column, label_prefix, kind, path, id_col]
        $sources = [
            ['projects', 'image', 'Cover — ', 'image', '/uploads/images/', 'id'],
            ['project_images', 'filename', 'Gallery — ', 'image', '/uploads/images/', null],
            ['hero_content', 'avatar', 'Hero avatar — ', 'avatar', '/uploads/images/', null],
            ['about_content', 'profile_image', 'About — profile', 'image', '/uploads/images/', null],
            ['clients', 'logo', 'Client logo — ', 'logo', '/uploads/images/', 'id'],
            ['admin_users', 'profile_pic', 'Admin avatar — ', 'avatar', '/uploads/admins/', null],
            ['gallery_images', 'filename', 'Gallery — ', 'image', '/uploads/images/', null]
        ];

        foreach ($sources as [$table, $col, $prefix, $kind, $path, $idCol]) {
            try {
                // Determine display label column (usually 'title' or 'name' or 'username')
                $labelCol = 'title';
                if ($table === 'hero_content' || $table === 'clients') $labelCol = 'name';
                if ($table === 'admin_users') $labelCol = "COALESCE(full_name, username)";
                if ($table === 'about_content') $labelCol = "'portrait'";

                $query = "SELECT $col as filename, $labelCol as label " . ($idCol ? ", $idCol as ref_id " : "") . " FROM $table WHERE $col IS NOT NULL AND $col != ''";
                
                $stmt = $pdo->query($query);
                while ($r = $stmt->fetch(PDO::FETCH_ASSOC)) {
                    $name = (string)$r['filename'];
                    if (!self::isLocal($name)) continue;

                    $out[] = [
                        "url"      => $path . rawurlencode($name),
                        "title"    => $prefix . ($r['label'] ?? 'Untitled'),
                        "source"   => "$table.$col",
                        "filename" => $name,
                        "kind"     => $kind,
                        "ref_id"   => isset($r['ref_id']) ? (int)$r['ref_id'] : 0
                    ];
                }
            } catch (Throwable $e) {
                // Silently skip tables that don't exist or have schema issues
                continue;
            }
        }

        return $out;
    }

    /**
     * Returns project videos. Local files return paths; external URLs are base64-encoded.
     */
    public static function videos(): array
    {
        $pdo = Database::pdo();
        $out = [];
        try {
            $stmt = $pdo->query("SELECT id, title, video_url, video_file, video_poster FROM projects 
                                 WHERE (video_file != '') OR (video_url != '')");
            
            while ($r = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $hasFile = !empty($r["video_file"]) && self::isLocal((string)$r["video_file"]);
                
                $poster = (!empty($r["video_poster"]) && self::isLocal((string)$r["video_poster"]))
                          ? "/uploads/images/" . rawurlencode((string)$r["video_poster"])
                          : null;

                $out[] = [
                    "local_url" => $hasFile ? "/uploads/videos/" . rawurlencode((string)$r["video_file"]) : null,
                    "ext_b64"   => (!$hasFile && !empty($r["video_url"])) ? base64_encode((string)$r["video_url"]) : null,
                    "title"     => (string)($r["title"] ?? "Untitled Video"),
                    "kind"      => $hasFile ? "local" : "external",
                    "poster"    => $poster,
                    "ref_id"    => (int)$r["id"],
                ];
            }
        } catch (Throwable $e) {}
        return $out;
    }

    /**
     * Lists documents from the file_library.
     */
    public static function docs(): array
    {
        $out = [];
        try {
            $stmt = Database::pdo()->query("SELECT filename, title, folder, original_name, size_bytes, created_at 
                                            FROM file_library ORDER BY created_at DESC");
            
            while ($r = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $name = (string)$r["filename"];
                $out[] = [
                    "filename"      => $name,
                    "url"           => "/uploads/docs/" . rawurlencode($name),
                    "title"         => (string)($r["title"] ?: $r["original_name"] ?: $name),
                    "folder"        => (string)($r["folder"] ?? "general"),
                    "original_name" => (string)($r["original_name"] ?? $name),
                    "size_bytes"    => (int)$r["size_bytes"],
                    "ext"           => strtolower(pathinfo($name, PATHINFO_EXTENSION)),
                    "modified_at"   => (string)$r["created_at"],
                ];
            }
        } catch (Throwable $e) {}
        return $out;
    }

    /**
     * Summary counts for Media Hub.
     */
    public static function summary(): array
    {
        return [
            "images" => count(self::images()),
            "videos" => count(self::videos()),
            "docs"   => count(self::docs()),
        ];
    }
}