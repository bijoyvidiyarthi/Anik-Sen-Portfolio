<?php
declare(strict_types=1);

namespace App\Repositories;

/**
 * S — Single Responsibility: only manages reviews data access.
 * O — Open/Closed: extends BaseRepository without modifying it.
 */
class ReviewRepository extends BaseRepository
{
    protected static string $table = 'reviews';

    public static function create(array $d): int
    {
        static::pdo()->prepare(
            'INSERT INTO reviews (author, role, body, sort_order) VALUES (:a,:r,:b,:s)'
        )->execute([
            ':a' => (string)($d['author'] ?? ''),
            ':r' => (string)($d['role'] ?? ''),
            ':b' => (string)($d['body'] ?? ''),
            ':s' => (int)($d['sort_order'] ?? 0),
        ]);
        return static::lastInsertId();
    }

    public static function update(int $id, array $d): void
    {
        static::pdo()->prepare(
            'UPDATE reviews SET author=:a, role=:r, body=:b, sort_order=:s WHERE id=:id'
        )->execute([
            ':a'  => (string)($d['author'] ?? ''),
            ':r'  => (string)($d['role'] ?? ''),
            ':b'  => (string)($d['body'] ?? ''),
            ':s'  => (int)($d['sort_order'] ?? 0),
            ':id' => $id,
        ]);
    }
}
