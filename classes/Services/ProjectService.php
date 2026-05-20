<?php
declare(strict_types=1);

namespace App\Services;

use App\GalleryImage;
use App\ProjectImage;
use App\Repositories\ProjectRepository;
use App\Contracts\FileUploaderInterface;

/**
 * S — Single Responsibility: orchestrates project business logic only.
 *     Data access is delegated to ProjectRepository.
 *     File handling is delegated to FileUploadService.
 * D — Dependency Inversion: depends on FileUploaderInterface and
 *     ProjectRepository (via its RepositoryInterface contract).
 */
final class ProjectService
{
    private FileUploadService $uploads;
    private string $imgDir;
    private string $videoDir;

    public function __construct(string $imgDir, string $videoDir, ?FileUploadService $uploads = null)
    {
        $this->imgDir   = $imgDir;
        $this->videoDir = $videoDir;
        $this->uploads  = $uploads ?? new FileUploadService();
    }

    // ── Create ───────────────────────────────────────────────────────────────

    public function create(array $post, array $files): array
    {
        $image       = $this->maybeUploadImage($files['image'] ?? null);
        [$videoFile, $videoPoster] = $this->maybeUploadMedia($post, $files);

        $newId   = ProjectRepository::create($post, $image, $videoFile, $videoPoster);
        $created = ProjectRepository::find($newId);

        if ($created && ($created['media_kind'] ?? '') === 'video') {
            GalleryImage::syncProjectVideo(
                $newId,
                (string)$created['title'],
                $created['video_file'] ?? null,
                $created['video_url']  ?? null
            );
        }

        return ['id' => $newId, 'row' => $created];
    }

    // ── Update ───────────────────────────────────────────────────────────────

    public function update(int $id, array $post, array $files): void
    {
        $existing = ProjectRepository::find($id);

        $image = $this->maybeUploadImage($files['image'] ?? null);
        if ($image && $existing && !empty($existing['image'])) {
            $this->uploads->deleteFile($this->imgDir, $existing['image']);
        }

        [$videoFile, $videoPoster] = $this->maybeUploadMedia($post, $files);
        if ($videoFile && $existing && !empty($existing['video_file'])) {
            $this->uploads->deleteFile($this->videoDir, (string)$existing['video_file']);
        }
        if ($videoPoster && $existing && !empty($existing['video_poster'])) {
            $this->uploads->deleteFile($this->imgDir, (string)$existing['video_poster']);
        }

        ProjectRepository::update($id, $post, $image, $videoFile, $videoPoster);

        $saved = ProjectRepository::find($id);
        if ($saved && ($saved['media_kind'] ?? '') === 'video') {
            GalleryImage::syncProjectVideo(
                $id,
                (string)$saved['title'],
                $saved['video_file'] ?? null,
                $saved['video_url']  ?? null
            );
        } else {
            GalleryImage::removeProjectVideo($id);
        }
    }

    // ── Delete ───────────────────────────────────────────────────────────────

    public function delete(int $id): void
    {
        $row = ProjectRepository::find($id);
        if ($row) {
            if (!empty($row['image'])) {
                $this->uploads->deleteFile($this->imgDir, $row['image']);
            }
            if (!empty($row['video_file'])) {
                $this->uploads->deleteFile($this->videoDir, (string)$row['video_file']);
            }
            if (!empty($row['video_poster'])) {
                $this->uploads->deleteFile($this->imgDir, (string)$row['video_poster']);
            }
            foreach (ProjectImage::forProject($id) as $img) {
                $this->uploads->deleteFile($this->imgDir, $img['filename']);
            }
        }
        ProjectRepository::delete($id);
        GalleryImage::removeProjectVideo($id);
    }

    // ── Media removal ────────────────────────────────────────────────────────

    public function deleteVideoFile(int $id): void
    {
        $row = ProjectRepository::find($id);
        if ($row && !empty($row['video_file'])) {
            $this->uploads->deleteFile($this->videoDir, (string)$row['video_file']);
        }
        ProjectRepository::clearMedia($id, 'video_file');

        // Recalculate video_type: if a URL still exists → 'external', else '' (no video).
        $updated = ProjectRepository::find($id);
        $newType = (!empty($updated['video_url'])) ? 'external' : '';
        ProjectRepository::updateVideoType($id, $newType);

        if ($updated) {
            GalleryImage::syncProjectVideo(
                $id,
                (string)$updated['title'],
                null,
                $updated['video_url'] ?? null
            );
        }
    }

    public function deletePoster(int $id): void
    {
        $row = ProjectRepository::find($id);
        if ($row && !empty($row['video_poster'])) {
            $this->uploads->deleteFile($this->imgDir, (string)$row['video_poster']);
        }
        ProjectRepository::clearMedia($id, 'video_poster');
    }

    // ── Gallery images ───────────────────────────────────────────────────────

    public function addGalleryImages(int $projectId, array $filesArray): array
    {
        $count  = 0;
        $errors = [];

        if (!empty($filesArray['name'][0])) {
            foreach ($filesArray['name'] as $i => $name) {
                if (!$name) {
                    continue;
                }
                $f = [
                    'name'     => $filesArray['name'][$i],
                    'type'     => $filesArray['type'][$i],
                    'tmp_name' => $filesArray['tmp_name'][$i],
                    'error'    => $filesArray['error'][$i],
                    'size'     => $filesArray['size'][$i],
                ];
                try {
                    $fn = $this->uploads->uploadImage($f, $this->imgDir);
                    ProjectImage::add($projectId, $fn, '', ($count + 1) * 10);
                    $count++;
                } catch (\Throwable $imgErr) {
                    $errors[] = htmlspecialchars(basename((string)$name)) . ': ' . $imgErr->getMessage();
                }
            }
        }

        return ['count' => $count, 'errors' => $errors];
    }

    public function deleteGalleryImage(int $imageId): void
    {
        $name = ProjectImage::delete($imageId);
        if ($name) {
            $this->uploads->deleteFile($this->imgDir, $name);
        }
    }

    // ── Private helpers ──────────────────────────────────────────────────────

    private function maybeUploadImage(?array $file): ?string
    {
        if (empty($file['name'])) {
            return null;
        }
        return $this->uploads->uploadImage($file, $this->imgDir);
    }

    private function maybeUploadMedia(array $post, array $files): array
    {
        $kind      = trim((string)($post['media_kind'] ?? 'gallery'));
        $videoFile = null;
        $videoPoster = null;

        if ($kind === 'video') {
            if (!empty($files['video_file']['name']) && $this->videoDir) {
                $videoFile = $this->uploads->uploadVideo($files['video_file'], $this->videoDir);
            }
            if (!empty($files['video_poster']['name'])) {
                $videoPoster = $this->uploads->uploadImage($files['video_poster'], $this->imgDir);
            }
        }

        return [$videoFile, $videoPoster];
    }
}
