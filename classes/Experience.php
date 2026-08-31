<?php
declare(strict_types=1);

namespace App;

use App\Repositories\ExperienceRepository;

class Experience
{
    public static function all(): array
    {
        return ExperienceRepository::all();
    }

    public static function find(int $id): ?array
    {
        return ExperienceRepository::find($id);
    }

    public static function create(array $d): int
    {
        return ExperienceRepository::create($d);
    }

    public static function update(int $id, array $d): void
    {
        ExperienceRepository::update($id, $d);
    }

    public static function delete(int $id): void
    {
        ExperienceRepository::delete($id);
    }
}
