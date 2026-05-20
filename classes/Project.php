<?php
declare(strict_types=1);

namespace App;

use App\Repositories\ProjectRepository;

/**
 * Backward-compatible static façade over ProjectRepository.
 *
 * D — Dependency Inversion: this thin wrapper depends on the
 *     ProjectRepository abstraction, not raw SQL.
 *
 * All admin pages continue calling Project::find(), Project::all(), etc.
 * without modification. The new admin/projects.php also has access to
 * the richer ProjectService for orchestrating uploads + gallery sync.
 */
class Project
{
    public const MAIN_CATEGORIES = ProjectRepository::MAIN_CATEGORIES;
    public const SUB_CATEGORIES  = ProjectRepository::SUB_CATEGORIES;
    public const MEDIA_KINDS     = ProjectRepository::MEDIA_KINDS;

    public static function all(bool $publishedOnly = false): array
    {
        return ProjectRepository::all($publishedOnly);
    }

    public static function find(int $id): ?array
    {
        return ProjectRepository::find($id);
    }

    public static function create(array $d, ?string $image = null, ?string $videoFile = null, ?string $videoPoster = null): int
    {
        return ProjectRepository::create($d, $image, $videoFile, $videoPoster);
    }

    public static function update(int $id, array $d, ?string $image = null, ?string $videoFile = null, ?string $videoPoster = null): void
    {
        ProjectRepository::update($id, $d, $image, $videoFile, $videoPoster);
    }

    public static function delete(int $id): void
    {
        ProjectRepository::delete($id);
    }

    public static function togglePublish(int $id): int
    {
        return ProjectRepository::togglePublish($id);
    }

    public static function clearMedia(int $id, string $field): void
    {
        ProjectRepository::clearMedia($id, $field);
    }
}
