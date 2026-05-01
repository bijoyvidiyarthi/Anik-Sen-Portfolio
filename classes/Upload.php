<?php
declare(strict_types=1);

namespace App;

use RuntimeException;

/**
 * File upload helper. Validates type, generates safe unique filename,
 * moves into target directory, returns the stored basename.
 */
class Upload
{
    public const IMAGE_EXTS = ["jpg", "jpeg", "png", "gif", "webp", "svg", "ico", "avif"];
    public const DOC_EXTS   = ["pdf", "doc", "docx", "xls", "xlsx", "ppt", "pptx", "txt", "csv", "zip", "rar"];
    public const VIDEO_EXTS = ["mp4", "webm", "mov", "m4v", "ogg", "ogv"];

    public const VIDEO_MIME_WHITELIST = [
        "video/mp4", "video/webm", "video/quicktime", "video/x-m4v",
        "video/ogg", "application/octet-stream",
    ];

    public const VIDEO_MAX_BYTES = 50 * 1024 * 1024; // 50MB hard cap

    public static function image(array $file, string $targetDir): string
    {
        return self::move($file, $targetDir, self::IMAGE_EXTS, 8 * 1024 * 1024);
    }

    /**
     * Strictly validated video upload (mp4, webm, mov, m4v, ogg).
     * Enforces:
     *   - file extension whitelist
     *   - 50MB hard size cap (configurable via $maxBytes)
     *   - finfo MIME signature whitelist (rejects masquerading binaries)
     * Returns the stored basename inside $targetDir.
     */
    public static function video(array $file, string $targetDir, int $maxBytes = self::VIDEO_MAX_BYTES): string
    {
        if (($file["error"] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            throw new RuntimeException(self::errorMessage((int) ($file["error"] ?? 0)));
        }
        if (($file["size"] ?? 0) > $maxBytes) {
            throw new RuntimeException("Video too large. Max " . round($maxBytes / 1024 / 1024) . "MB.");
        }

        $ext = strtolower(pathinfo((string)$file["name"], PATHINFO_EXTENSION));
        if (!in_array($ext, self::VIDEO_EXTS, true)) {
            throw new RuntimeException("Unsupported video format: .{$ext}. Allowed: " . implode(", ", self::VIDEO_EXTS));
        }

        $tmp = (string)($file["tmp_name"] ?? "");
        if ($tmp !== "" && is_readable($tmp) && function_exists("finfo_open")) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime  = $finfo ? (string)finfo_file($finfo, $tmp) : "";
            if ($finfo) finfo_close($finfo);
            if ($mime !== "" && !in_array($mime, self::VIDEO_MIME_WHITELIST, true)) {
                throw new RuntimeException("Detected MIME ($mime) is not an allowed video type.");
            }
        }

        return self::move($file, $targetDir, self::VIDEO_EXTS, $maxBytes);
    }

    /**
     * Resolve the canonical MIME for an uploaded video file based on its
     * stored extension (used by the frontend <source type="..."> attribute).
     */
    public static function videoMime(string $filename): string
    {
        return match (strtolower(pathinfo($filename, PATHINFO_EXTENSION))) {
            "mp4", "m4v" => "video/mp4",
            "webm"       => "video/webm",
            "mov"        => "video/quicktime",
            "ogg", "ogv" => "video/ogg",
            default      => "video/mp4",
        };
    }

    public static function doc(array $file, string $targetDir): string
    {
        return self::move($file, $targetDir, self::DOC_EXTS, 32 * 1024 * 1024);
    }

    /**
     * Strictly PDF-only upload: validates the file extension AND the actual
     * MIME signature with finfo to block disguised binaries.
     */
    public static function pdf(array $file, string $targetDir, int $maxBytes = 32 * 1024 * 1024): string
    {
        if (($file["error"] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            throw new RuntimeException(self::errorMessage((int) ($file["error"] ?? 0)));
        }
        $ext = strtolower(pathinfo((string) $file["name"], PATHINFO_EXTENSION));
        if ($ext !== "pdf") {
            throw new RuntimeException("CV must be a .pdf file.");
        }

        $tmp = (string) ($file["tmp_name"] ?? "");
        if ($tmp !== "" && is_readable($tmp)) {
            // Magic-bytes check: real PDFs start with "%PDF-".
            $head = (string) @file_get_contents($tmp, false, null, 0, 5);
            if ($head !== "%PDF-") {
                throw new RuntimeException("Uploaded file is not a valid PDF document.");
            }
            if (function_exists("finfo_open")) {
                $finfo = finfo_open(FILEINFO_MIME_TYPE);
                $mime  = $finfo ? (string) finfo_file($finfo, $tmp) : "";
                if ($finfo) finfo_close($finfo);
                if ($mime !== "" && stripos($mime, "pdf") === false) {
                    throw new RuntimeException("Detected MIME type ($mime) is not a PDF.");
                }
            }
        }

        return self::move($file, $targetDir, ["pdf"], $maxBytes);
    }

    public static function any(array $file, string $targetDir): string
    {
        $allowed = array_merge(self::IMAGE_EXTS, self::DOC_EXTS);
        return self::move($file, $targetDir, $allowed, 32 * 1024 * 1024);
    }

    private static function move(array $file, string $dir, array $allowed, int $maxBytes): string
    {
        if (($file["error"] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            throw new RuntimeException(self::errorMessage((int) ($file["error"] ?? 0)));
        }
        if (($file["size"] ?? 0) > $maxBytes) {
            throw new RuntimeException("File too large. Max " . round($maxBytes / 1024 / 1024) . "MB.");
        }

        $orig = (string) $file["name"];
        $ext  = strtolower(pathinfo($orig, PATHINFO_EXTENSION));
        if (!in_array($ext, $allowed, true)) {
            throw new RuntimeException("Unsupported file type: .{$ext}");
        }

        if (!is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }
        if (!is_writable($dir)) {
            throw new RuntimeException("Upload directory not writable: $dir");
        }

        $base = self::slug(pathinfo($orig, PATHINFO_FILENAME));
        $name = $base . "-" . bin2hex(random_bytes(4)) . "." . $ext;
        $target = rtrim($dir, "/") . "/" . $name;

        if (!move_uploaded_file($file["tmp_name"], $target)) {
            throw new RuntimeException("Failed to save uploaded file.");
        }
        @chmod($target, 0644);
        return $name;
    }

    public static function delete(string $dir, ?string $filename): void
    {
        if (!$filename) return;
        $path = rtrim($dir, "/") . "/" . basename($filename);
        if (is_file($path)) {
            @unlink($path);
        }
    }

    public static function slug(string $s): string
    {
        $s = strtolower($s);
        $s = preg_replace('/[^a-z0-9]+/', "-", $s) ?? "";
        $s = trim($s, "-");
        return $s !== "" ? substr($s, 0, 60) : "file";
    }

    public static function humanSize(int $bytes): string
    {
        $units = ["B", "KB", "MB", "GB"];
        $i = 0;
        $n = (float) $bytes;
        while ($n >= 1024 && $i < count($units) - 1) {
            $n /= 1024; $i++;
        }
        return ($i === 0 ? (int) $n : number_format($n, 1)) . " " . $units[$i];
    }

    private static function errorMessage(int $code): string
    {
        return match ($code) {
            UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => "File exceeds maximum upload size.",
            UPLOAD_ERR_PARTIAL    => "File only partially uploaded.",
            UPLOAD_ERR_NO_FILE    => "No file selected.",
            UPLOAD_ERR_NO_TMP_DIR => "Server has no temp directory.",
            UPLOAD_ERR_CANT_WRITE => "Failed to write file to disk.",
            UPLOAD_ERR_EXTENSION  => "Upload blocked by PHP extension.",
            default               => "Unknown upload error ($code).",
        };
    }
}
