<?php
declare(strict_types=1);

namespace App;

use App\Repositories\SkillRepository;

/**
 * Backward-compatible static façade over SkillRepository.
 * D — Dependency Inversion: depends on SkillRepository abstraction.
 */
class Skill
{
    public static function all(?string $kind = null): array
    {
        return SkillRepository::all($kind);
    }

    public static function find(int $id): ?array
    {
        return SkillRepository::find($id);
    }

    public static function create(array $d): int
    {
        return SkillRepository::create($d);
    }

    public static function update(int $id, array $d): void
    {
        SkillRepository::update($id, $d);
    }

    public static function delete(int $id): void
    {
        SkillRepository::delete($id);
    }
}
