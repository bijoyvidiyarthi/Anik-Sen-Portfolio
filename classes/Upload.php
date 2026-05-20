<?php
declare(strict_types=1);

namespace App;

use App\Services\FileStorageHelper;
use App\Services\ImageUploader;
use App\Services\VideoUploader;
use App\Services\DocUploader;
use RuntimeException;

/**
 * Backward-compatible static façade over the SOLID upload services.
 *
 * O — Open/Closed: the underlying strategies (ImageUploader, VideoUploader,
 *     DocUploader) can be extended independently without touching this class.
 * D — Dependency Inversion: admin pages that still call Upload::image() etc.
 *     are insulated from changes in the concrete upload strategies.
 */
class Upload
{
    // Expose constants so existing code that reads Upload::IMAGE_EXTS etc. still works.
    public const IMAGE_EXTS = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg', 'ico', 'avif'];
    public const DOC_EXTS   = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'txt', 'csv', 'zip', 'rar'];
    public const VIDEO_EXTS = ['mp4', 'webm', 'mov', 'm4v', 'ogg', 'ogv'];

    public const VIDEO_MIME_WHITELIST = [
        'video/mp4', 'video/webm', 'video/quicktime', 'video/x-m4v',
        'video/ogg', 'application/octet-stream',
    ];

    public const VIDEO_MAX_BYTES = 50 * 1024 * 1024;

    public static function image(array $file, string $targetDir): string
    {
        return (new ImageUploader())->upload($file, $targetDir);
    }

    public static function video(array $file, string $targetDir, int $maxBytes = self::VIDEO_MAX_BYTES): string
    {
        return (new VideoUploader())->uploadWithLimit($file, $targetDir, $maxBytes);
    }

    public static function videoMime(string $filename): string
    {
        return VideoUploader::mimeForFilename($filename);
    }

    public static function doc(array $file, string $targetDir): string
    {
        return (new DocUploader())->upload($file, $targetDir);
    }

    /**
     * Strictly PDF-only upload with magic-byte validation.
     */
    public static function pdf(array $file, string $targetDir, int $maxBytes = 32 * 1024 * 1024): string
    {
        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            throw new RuntimeException(FileStorageHelper::errorMessage((int)($file['error'] ?? 0)));
        }
        $ext = strtolower(pathinfo((string)$file['name'], PATHINFO_EXTENSION));
        if ($ext !== 'pdf') {
            throw new RuntimeException('CV must be a .pdf file.');
        }
        $tmp = (string)($file['tmp_name'] ?? '');
        if ($tmp !== '' && is_readable($tmp)) {
            $head = (string)@file_get_contents($tmp, false, null, 0, 5);
            if ($head !== '%PDF-') {
                throw new RuntimeException('Uploaded file is not a valid PDF document.');
            }
            if (function_exists('finfo_open')) {
                $finfo = finfo_open(FILEINFO_MIME_TYPE);
                $mime  = $finfo ? (string)finfo_file($finfo, $tmp) : '';
                if ($finfo) {
                    finfo_close($finfo);
                }
                if ($mime !== '' && stripos($mime, 'pdf') === false) {
                    throw new RuntimeException("Detected MIME type ($mime) is not a PDF.");
                }
            }
        }
        return FileStorageHelper::move($file, $targetDir, ['pdf'], $maxBytes);
    }

    public static function any(array $file, string $targetDir): string
    {
        $allowed = array_merge(self::IMAGE_EXTS, self::DOC_EXTS);
        return FileStorageHelper::move($file, $targetDir, $allowed, 32 * 1024 * 1024);
    }

    public static function delete(string $dir, ?string $filename): void
    {
        FileStorageHelper::remove($dir, $filename);
    }

    public static function slug(string $s): string
    {
        return FileStorageHelper::slug($s);
    }

    public static function humanSize(int $bytes): string
    {
        return FileStorageHelper::humanSize($bytes);
    }
}
