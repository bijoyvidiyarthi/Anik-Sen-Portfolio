<?php
declare(strict_types=1);

namespace App\Contracts;

/**
 * S — Single Responsibility: defines only data-access contract.
 * I — Interface Segregation: only the methods every repository must support.
 * D — Dependency Inversion: callers depend on this abstraction, not concrete classes.
 */
interface RepositoryInterface
{
    public static function all(): array;
    public static function find(int $id): ?array;
    public static function create(array $data): int;
    public static function update(int $id, array $data): void;
    public static function delete(int $id): void;
}
