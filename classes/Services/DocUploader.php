<?php
declare(strict_types=1);

namespace App\Services;

use App\Contracts\FileUploaderInterface;

/**
 * S — Single Responsibility: handles only document file uploads.
 * O — Open/Closed: new document formats can be added to ALLOWED_EXTS
 *     without affecting ImageUploader or VideoUploader.
 * L — Liskov Substitution: substitutable wherever FileUploaderInterface is expected.
 */
final class DocUploader implements FileUploaderInterface
{
    private const ALLOWED_EXTS = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'txt', 'csv', 'zip', 'rar'];
    private const MAX_BYTES    = 32 * 1024 * 1024; // 32 MB

    public function upload(array $file, string $targetDir): string
    {
        return FileStorageHelper::move($file, $targetDir, self::ALLOWED_EXTS, self::MAX_BYTES);
    }

    public function delete(string $dir, ?string $filename): void
    {
        FileStorageHelper::remove($dir, $filename);
    }
}
