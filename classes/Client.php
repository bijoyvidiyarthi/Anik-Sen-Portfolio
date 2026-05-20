<?php
declare(strict_types=1);

namespace App;

use App\Repositories\ClientRepository;

/**
 * Backward-compatible static façade over ClientRepository.
 * D — Dependency Inversion: depends on ClientRepository abstraction.
 */
class Client
{
    public static function all(bool $visibleOnly = false): array
    {
        return ClientRepository::all($visibleOnly);
    }

    public static function find(int $id): ?array
    {
        return ClientRepository::find($id);
    }

    public static function create(array $d, ?string $logo = null): int
    {
        return ClientRepository::create($d, $logo);
    }

    public static function update(int $id, array $d, ?string $logo = null): void
    {
        ClientRepository::update($id, $d, $logo);
    }

    public static function toggleVisibility(int $id): void
    {
        ClientRepository::toggleVisibility($id);
    }

    public static function delete(int $id): ?string
    {
        return ClientRepository::deleteAndReturnLogo($id);
    }

    public static function isLocalLogo(string $logo): bool
    {
        return ClientRepository::isLocalLogo($logo);
    }

    public static function logoUrl(string $logo): string
    {
        return ClientRepository::logoUrl($logo);
    }
}
