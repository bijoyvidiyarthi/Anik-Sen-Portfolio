<?php
declare(strict_types=1);

namespace App;

use App\Repositories\EducationRepository;

/**
 * Backward-compatible static façade over EducationRepository.
 * D — Dependency Inversion: depends on EducationRepository abstraction.
 */
class Education
{
    public static function all(): array
    {
        return EducationRepository::all();
    }

    public static function find(int $id): ?array
    {
        return EducationRepository::find($id);
    }

    public static function create(array $d): int
    {
        return EducationRepository::create($d);
    }

    public static function update(int $id, array $d): void
    {
        EducationRepository::update($id, $d);
    }

    public static function delete(int $id): void
    {
        EducationRepository::delete($id);
    }
}
