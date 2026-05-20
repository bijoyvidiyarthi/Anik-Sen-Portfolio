<?php
declare(strict_types=1);

namespace App\Repositories;

/**
 * S — Single Responsibility: only manages education records data access.
 * O — Open/Closed: extends BaseRepository without modifying it.
 */
class EducationRepository extends BaseRepository
{
    protected static string $table = 'education';

    public static function create(array $d): int
    {
        static::pdo()->prepare(
            'INSERT INTO education (year, degree, status, sort_order) VALUES (:y,:d,:s,:o)'
        )->execute([
            ':y' => (string)($d['year'] ?? ''),
            ':d' => (string)($d['degree'] ?? ''),
            ':s' => (string)($d['status'] ?? ''),
            ':o' => (int)($d['sort_order'] ?? 0),
        ]);
        return static::lastInsertId();
    }

    public static function update(int $id, array $d): void
    {
        static::pdo()->prepare(
            'UPDATE education SET year=:y, degree=:d, status=:s, sort_order=:o WHERE id=:id'
        )->execute([
            ':y'  => (string)($d['year'] ?? ''),
            ':d'  => (string)($d['degree'] ?? ''),
            ':s'  => (string)($d['status'] ?? ''),
            ':o'  => (int)($d['sort_order'] ?? 0),
            ':id' => $id,
        ]);
    }
}
