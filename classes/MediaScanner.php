<?php
declare(strict_types=1);

namespace App;

use FilesystemIterator;

/**
 * Centralised media + file aggregator.
 *
 * Scans the database (projects, project_images, hero_content, clients,
 * about_content, admin_users, gallery_images) AND the filesystem under
 * /public/uploads/{images,videos,docs} to populate the admin Media Hub
 * with every asset the site references — automatically, with no manual
 * tagging required.
 *
 * Pure read-only OOP layer: every query uses prepared / parameterless
 * PDO statements via {@see Database::pdo()}.
 */
class MediaScanner
{
    /**
     * Returns a flat list of every image referenced by site content.
     * Each row: ["url", "title", "source" (table label), "filename", "kind"].
     */
    public static function images(): array
    {
        $config  = $GLOBALS["APP_CONFIG"] ?? [];
        $imgDir  = $config["paths"]["image_dir"]   ?? "";
        $assets  = "/assets/images";
        $pdo     = Database::pdo();

        $resolveUploaded = static function (string $name) use ($imgDir, $assets): array {
            if ($name === "") return ["", false];
            if (str_contains($name, "://")) return [$name, true];
            if ($imgDir !== "" && is_file($imgDir . "/" . $name)) {
                return ["/uploads/images/" . rawurlencode($name), true];
            }
            return [$assets . "/" . rawurlencode($name), false];
        };

        $out = [];

        // --- projects.image (cover) ---
        foreach ($pdo->query("SELECT id, title, image FROM projects WHERE image IS NOT NULL AND image != ''")->fetchAll() as $r) {
            [$url] = $resolveUploaded((string)$r["image"]);
            if ($url === "") continue;
            $out[] = [
                "url"      => $url,
                "title"    => "Cover · " . $r["title"],
                "source"   => "projects.image",
                "filename" => (string)$r["image"],
                "kind"     => "image",
                "ref_id"   => (int)$r["id"],
            ];
        }

        // --- project_images (extra gallery shots) ---
        foreach ($pdo->query(
            "SELECT pi.filename, p.title
               FROM project_images pi
               LEFT JOIN projects p ON p.id = pi.project_id"
        )->fetchAll() as $r) {
            [$url] = $resolveUploaded((string)$r["filename"]);
            if ($url === "") continue;
            $out[] = [
                "url"      => $url,
                "title"    => "Gallery · " . ($r["title"] ?? "Project"),
                "source"   => "project_images",
                "filename" => (string)$r["filename"],
                "kind"     => "image",
                "ref_id"   => 0,
            ];
        }

        // --- hero_content.avatar ---
        try {
            foreach ($pdo->query("SELECT name, avatar FROM hero_content WHERE avatar IS NOT NULL AND avatar != ''")->fetchAll() as $r) {
                [$url] = $resolveUploaded((string)$r["avatar"]);
                if ($url === "") continue;
                $out[] = [
                    "url"      => $url,
                    "title"    => "Hero avatar · " . $r["name"],
                    "source"   => "hero_content.avatar",
                    "filename" => (string)$r["avatar"],
                    "kind"     => "image",
                    "ref_id"   => 0,
                ];
            }
        } catch (\Throwable $e) { /* table missing on fresh installs */ }

        // --- about_content.profile_image ---
        try {
            foreach ($pdo->query("SELECT profile_image FROM about_content WHERE profile_image IS NOT NULL AND profile_image != ''")->fetchAll() as $r) {
                [$url] = $resolveUploaded((string)$r["profile_image"]);
                if ($url === "") continue;
                $out[] = [
                    "url"      => $url,
                    "title"    => "About · profile portrait",
                    "source"   => "about_content.profile_image",
                    "filename" => (string)$r["profile_image"],
                    "kind"     => "image",
                    "ref_id"   => 0,
                ];
            }
        } catch (\Throwable $e) {}

        // --- clients.logo (may be hot-linked URL) ---
        try {
            foreach ($pdo->query("SELECT id, name, logo FROM clients WHERE logo IS NOT NULL AND logo != ''")->fetchAll() as $r) {
                [$url] = $resolveUploaded((string)$r["logo"]);
                if ($url === "") continue;
                $out[] = [
                    "url"      => $url,
                    "title"    => "Client logo · " . $r["name"],
                    "source"   => "clients.logo",
                    "filename" => (string)$r["logo"],
                    "kind"     => "logo",
                    "ref_id"   => (int)$r["id"],
                ];
            }
        } catch (\Throwable $e) {}

        // --- admin_users.profile_pic ---
        try {
            foreach ($pdo->query("SELECT username, full_name, profile_pic FROM admin_users WHERE profile_pic IS NOT NULL AND profile_pic != ''")->fetchAll() as $r) {
                $name = (string)$r["profile_pic"];
                $url  = "/uploads/admins/" . rawurlencode($name);
                $out[] = [
                    "url"      => $url,
                    "title"    => "Admin avatar · " . ($r["full_name"] ?: $r["username"]),
                    "source"   => "admin_users.profile_pic",
                    "filename" => $name,
                    "kind"     => "avatar",
                    "ref_id"   => 0,
                ];
            }
        } catch (\Throwable $e) {}

        // --- gallery_images (curated gallery) ---
        try {
            foreach ($pdo->query(
                "SELECT gi.filename, gi.title, gc.name AS cat_name
                   FROM gallery_images gi
                   LEFT JOIN gallery_categories gc ON gc.id = gi.category_id"
            )->fetchAll() as $r) {
                [$url] = $resolveUploaded((string)$r["filename"]);
                if ($url === "") continue;
                $out[] = [
                    "url"      => $url,
                    "title"    => trim(($r["title"] ?: "Gallery image") . " · " . ($r["cat_name"] ?? "")),
                    "source"   => "gallery_images",
                    "filename" => (string)$r["filename"],
                    "kind"     => "image",
                    "ref_id"   => 0,
                ];
            }
        } catch (\Throwable $e) {}

        return $out;
    }

    /**
     * Returns every project video referenced in the database — both
     * self-hosted files in /uploads/videos/ AND legacy YouTube/Vimeo URLs.
     */
    public static function videos(): array
    {
        $pdo = Database::pdo();
        $out = [];
        try {
            foreach ($pdo->query(
                "SELECT id, title, video_url, video_file, video_poster
                   FROM projects
                  WHERE (video_file IS NOT NULL AND video_file != '')
                     OR (video_url  IS NOT NULL AND video_url  != '')"
            )->fetchAll() as $r) {
                $hasFile = !empty($r["video_file"]);
                $url = $hasFile
                    ? "/uploads/videos/" . rawurlencode((string)$r["video_file"])
                    : (string)$r["video_url"];
                $out[] = [
                    "url"      => $url,
                    "title"    => $r["title"],
                    "source"   => $hasFile ? "projects.video_file" : "projects.video_url",
                    "filename" => (string)($hasFile ? $r["video_file"] : $r["video_url"]),
                    "kind"     => $hasFile ? "local" : "external",
                    "poster"   => !empty($r["video_poster"]) ? "/uploads/images/" . rawurlencode((string)$r["video_poster"]) : null,
                    "ref_id"   => (int)$r["id"],
                ];
            }
        } catch (\Throwable $e) {}

        return $out;
    }

    /**
     * Lists every downloadable document under /uploads/docs/.
     * Cross-references the file_library table for friendly metadata.
     */
    public static function docs(): array
    {
        $config = $GLOBALS["APP_CONFIG"] ?? [];
        $dir    = $config["paths"]["doc_dir"] ?? "";
        $out    = [];

        // Build a quick lookup from file_library so we can show the friendly
        // title / folder when available.
        $meta = [];
        try {
            foreach (Database::pdo()->query(
                "SELECT filename, title, folder, original_name, mime, size_bytes, is_active
                   FROM file_library"
            )->fetchAll() as $r) {
                $meta[(string)$r["filename"]] = $r;
            }
        } catch (\Throwable $e) {}

        if ($dir === "" || !is_dir($dir)) return $out;

        $iter = new FilesystemIterator($dir, FilesystemIterator::SKIP_DOTS);
        foreach ($iter as $f) {
            if (!$f->isFile()) continue;
            $name = $f->getFilename();
            if (str_starts_with($name, ".")) continue; // skip dotfiles like .gitkeep

            $row  = $meta[$name] ?? null;
            $out[] = [
                "filename"      => $name,
                "url"           => "/uploads/docs/" . rawurlencode($name),
                "title"         => $row["title"]         ?? pathinfo($name, PATHINFO_FILENAME),
                "folder"        => $row["folder"]        ?? "uncategorised",
                "original_name" => $row["original_name"] ?? $name,
                "mime"          => $row["mime"]          ?? "",
                "size_bytes"    => (int)($row["size_bytes"] ?? $f->getSize()),
                "is_active"     => (bool)($row["is_active"] ?? false),
                "tracked"       => $row !== null,
                "ext"           => strtolower(pathinfo($name, PATHINFO_EXTENSION)),
                "modified_at"   => date("Y-m-d H:i", $f->getMTime()),
            ];
        }

        // Most-recent first.
        usort($out, static fn($a, $b) => strcmp($b["modified_at"], $a["modified_at"]));
        return $out;
    }

    /**
     * High-level summary tile data (counts) for the Media Hub header.
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
