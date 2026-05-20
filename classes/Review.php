<?php
declare(strict_types=1);

namespace App;

use App\Repositories\ReviewRepository;

/**
 * Backward-compatible static façade over ReviewRepository.
 * D — Dependency Inversion: depends on ReviewRepository abstraction.
 */
class Review
{
    public static function all(): array
    {
        return ReviewRepository::all();
    }

    public static function find(int $id): ?array
    {
        return ReviewRepository::find($id);
    }

    public static function create(array $d): int
    {
        return ReviewRepository::create($d);
    }

    public static function update(int $id, array $d): void
    {
        ReviewRepository::update($id, $d);
    }

    public static function delete(int $id): void
    {
        ReviewRepository::delete($id);
    }
}
