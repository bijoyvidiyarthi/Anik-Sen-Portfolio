<?php
declare(strict_types=1);

namespace App\Services;

use App\Contracts\FileUploaderInterface;

/**
 * S — Single Responsibility: orchestrates file uploads by selecting
 *     the correct strategy for each media type.
 * O — Open/Closed: new file-type strategies can be registered via
 *     constructor injection without modifying this class.
 * D — Dependency Inversion: depends on FileUploaderInterface,
 *     not on concrete uploader classes directly.
 *
 * Concrete strategies live in their own files:
 *   - ImageUploader  → classes/Services/ImageUploader.php
 *   - VideoUploader  → classes/Services/VideoUploader.php
 *   - DocUploader    → classes/Services/DocUploader.php
 *   - FileStorageHelper (shared FS helper) → classes/Services/FileStorageHelper.php
 */
final class FileUploadService
{
    private FileUploaderInterface $imageUploader;
    private FileUploaderInterface $videoUploader;
    private FileUploaderInterface $docUploader;

    public function __construct(
        ?FileUploaderInterface $imageUploader = null,
        ?FileUploaderInterface $videoUploader = null,
        ?FileUploaderInterface $docUploader   = null
    ) {
        $this->imageUploader = $imageUploader ?? new ImageUploader();
        $this->videoUploader = $videoUploader ?? new VideoUploader();
        $this->docUploader   = $docUploader   ?? new DocUploader();
    }

    public function uploadImage(array $file, string $targetDir): string
    {
        return $this->imageUploader->upload($file, $targetDir);
    }

    public function uploadVideo(array $file, string $targetDir, int $maxBytes = VideoUploader::MAX_BYTES): string
    {
        if ($this->videoUploader instanceof VideoUploader) {
            return $this->videoUploader->uploadWithLimit($file, $targetDir, $maxBytes);
        }
        return $this->videoUploader->upload($file, $targetDir);
    }

    public function uploadDoc(array $file, string $targetDir): string
    {
        return $this->docUploader->upload($file, $targetDir);
    }

    public function deleteFile(string $dir, ?string $filename): void
    {
        FileStorageHelper::remove($dir, $filename);
    }
}
