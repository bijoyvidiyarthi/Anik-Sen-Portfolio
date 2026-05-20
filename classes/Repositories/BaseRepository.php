<?php
declare(strict_types=1);

namespace App\Repositories;

use App\Database;
use App\Contracts\RepositoryInterface;

/**
 * O — Open/Closed: child classes extend this base without modifying it.
 * L — Liskov Substitution: any subclass can replace this wherever BaseRepository is expected.
 *
 * Provides the common PDO helpers that all concrete repositories reuse.
 * Concrete repositories only override the table name and column map.
 */
abstract class BaseRepository implements RepositoryInterface
{
    /** The database table name. Child classes MUST define this. */
    protected static string $table = '';

    // ── Helpers ─────────────────────────────────────────────────────────────

    protected static function pdo(): \PDO
    {
        return Database::pdo();
    }

    protected static function tableName(): string
    {
        if (static::$table === '') {
            throw new \LogicException(static::class . ' must define $table.');
        }
        return static::$table;
    }

    // ── Default RepositoryInterface implementation ───────────────────────────

    public static function all(): array
    {
        $sql = 'SELECT * FROM ' . static::tableName() . ' ORDER BY sort_order, id';
        return static::pdo()->query($sql)->fetchAll();
    }

    public static function find(int $id): ?array
    {
        $stmt = static::pdo()->prepare(
            'SELECT * FROM ' . static::tableName() . ' WHERE id = :id'
        );
        $stmt->execute([':id' => $id]);
        return $stmt->fetch() ?: null;
    }

    /**
     * Default create() — child classes override when they need
     * column-specific mapping, file references, etc.
     */
    public static function create(array $data): int
    {
        throw new \BadMethodCallException(static::class . ' must override create().');
    }

    /**
     * Default update() — child classes override.
     */
    public static function update(int $id, array $data): void
    {
        throw new \BadMethodCallException(static::class . ' must override update().');
    }

    public static function delete(int $id): void
    {
        static::pdo()
            ->prepare('DELETE FROM ' . static::tableName() . ' WHERE id = :id')
            ->execute([':id' => $id]);
    }

    // ── Utility ──────────────────────────────────────────────────────────────

    protected static function lastInsertId(): int
    {
        return (int) static::pdo()->lastInsertId();
    }

    protected static function columnExists(string $column): bool
    {
        $table = static::tableName();
        try {
            static::pdo()->query("SELECT {$column} FROM {$table} LIMIT 0");
            return true;
        } catch (\PDOException $e) {
            return false;
        }
    }
}
