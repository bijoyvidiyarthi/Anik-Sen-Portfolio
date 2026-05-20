<?php
declare(strict_types=1);

namespace App\Repositories;

/**
 * S — Single Responsibility: only manages skills data access.
 * O — Open/Closed: extends BaseRepository; no modification of the base needed.
 * L — Liskov Substitution: substitutable wherever BaseRepository is expected.
 */
class SkillRepository extends BaseRepository
{
    protected static string $table = 'skills';

    public static function all(?string $kind = null): array
    {
        $sql  = 'SELECT * FROM skills' . ($kind ? ' WHERE kind = :k' : '') . ' ORDER BY sort_order, id';
        $stmt = static::pdo()->prepare($sql);
        $stmt->execute($kind ? [':k' => $kind] : []);
        return $stmt->fetchAll();
    }

    public static function create(array $d): int
    {
        static::pdo()->prepare(
            'INSERT INTO skills (name, kind, tag, letters, color, bg, sort_order)
             VALUES (:n,:k,:t,:l,:c,:b,:s)'
        )->execute([
            ':n' => (string)($d['name'] ?? ''),
            ':k' => (string)($d['kind'] ?? 'creative'),
            ':t' => (string)($d['tag'] ?? ''),
            ':l' => (string)($d['letters'] ?? ''),
            ':c' => (string)($d['color'] ?? ''),
            ':b' => (string)($d['bg'] ?? ''),
            ':s' => (int)($d['sort_order'] ?? 0),
        ]);
        return static::lastInsertId();
    }

    public static function update(int $id, array $d): void
    {
        static::pdo()->prepare(
            'UPDATE skills SET name=:n, kind=:k, tag=:t, letters=:l, color=:c, bg=:b, sort_order=:s WHERE id=:id'
        )->execute([
            ':n'  => (string)($d['name'] ?? ''),
            ':k'  => (string)($d['kind'] ?? 'creative'),
            ':t'  => (string)($d['tag'] ?? ''),
            ':l'  => (string)($d['letters'] ?? ''),
            ':c'  => (string)($d['color'] ?? ''),
            ':b'  => (string)($d['bg'] ?? ''),
            ':s'  => (int)($d['sort_order'] ?? 0),
            ':id' => $id,
        ]);
    }
}
