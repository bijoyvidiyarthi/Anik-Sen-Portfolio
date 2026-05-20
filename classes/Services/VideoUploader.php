<?php
declare(strict_types=1);

namespace App\Services;

use App\Contracts\FileUploaderInterface;
use RuntimeException;

/**
 * S — Single Responsibility: handles only video file uploads with MIME validation.
 * O — Open/Closed: MIME whitelist / extension list can be extended here
 *     without touching any other uploader strategy.
 * L — Liskov Substitution: substitutable wherever FileUploaderInterface is expected.
 */
final class VideoUploader implements FileUploaderInterface
{
    public const  MAX_BYTES     = 50 * 1024 * 1024; // 50 MB
    private const ALLOWED_EXTS  = ['mp4', 'webm', 'mov', 'm4v', 'ogg', 'ogv'];
    private const MIME_WHITELIST = [
        'video/mp4', 'video/webm', 'video/quicktime', 'video/x-m4v',
        'video/ogg', 'application/octet-stream',
    ];

    public function upload(array $file, string $targetDir): string
    {
        return $this->uploadWithLimit($file, $targetDir, self::MAX_BYTES);
    }

    public function uploadWithLimit(array $file, string $targetDir, int $maxBytes): string
    {
        $err = (int)($file['error'] ?? UPLOAD_ERR_NO_FILE);
        if ($err !== UPLOAD_ERR_OK) {
            throw new RuntimeException(FileStorageHelper::errorMessage($err));
        }
        if (($file['size'] ?? 0) > $maxBytes) {
            throw new RuntimeException('Video too large. Max ' . round($maxBytes / 1024 / 1024) . 'MB.');
        }

        $ext = strtolower(pathinfo((string)$file['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, self::ALLOWED_EXTS, true)) {
            throw new RuntimeException(
                "Unsupported video format: .{$ext}. Allowed: " . implode(', ', self::ALLOWED_EXTS)
            );
        }

        $tmp = (string)($file['tmp_name'] ?? '');
        if ($tmp !== '' && is_readable($tmp) && function_exists('finfo_open')) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime  = $finfo ? (string)finfo_file($finfo, $tmp) : '';
            if ($finfo) {
                finfo_close($finfo);
            }
            if ($mime !== '' && !in_array($mime, self::MIME_WHITELIST, true)) {
                throw new RuntimeException("Detected MIME ($mime) is not an allowed video type.");
            }
        }

        return FileStorageHelper::move($file, $targetDir, self::ALLOWED_EXTS, $maxBytes);
    }

    public function delete(string $dir, ?string $filename): void
    {
        FileStorageHelper::remove($dir, $filename);
    }

    /**
     * Resolve the correct MIME type string for a <source type="..."> attribute.
     */
    public static function mimeForFilename(string $filename): string
    {
        return match (strtolower(pathinfo($filename, PATHINFO_EXTENSION))) {
            'mp4', 'm4v' => 'video/mp4',
            'webm'       => 'video/webm',
            'mov'        => 'video/quicktime',
            'ogg', 'ogv' => 'video/ogg',
            default      => 'video/mp4',
        };
    }
}
