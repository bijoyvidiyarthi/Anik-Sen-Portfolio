<?php
declare(strict_types=1);

namespace App\Services;

use App\Contracts\FileUploaderInterface;

/**
 * S — Single Responsibility: handles only image file uploads.
 * O — Open/Closed: adding a new image format only touches this class,
 *     not any other uploader or the Upload façade.
 * L — Liskov Substitution: substitutable wherever FileUploaderInterface is expected.
 */
final class ImageUploader implements FileUploaderInterface
{
    private const ALLOWED_EXTS = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg', 'ico', 'avif'];
    private const MAX_BYTES    = 8 * 1024 * 1024; // 8 MB

    public function upload(array $file, string $targetDir): string
    {
        return FileStorageHelper::move($file, $targetDir, self::ALLOWED_EXTS, self::MAX_BYTES);
    }

    public function delete(string $dir, ?string $filename): void
    {
        FileStorageHelper::remove($dir, $filename);
    }
}
