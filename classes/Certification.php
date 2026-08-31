<?php
declare(strict_types=1);

namespace App;

use App\Repositories\CertificationRepository;

class Certification
{
    public static function all(): array
    {
        return CertificationRepository::all();
    }

    public static function find(int $id): ?array
    {
        return CertificationRepository::find($id);
    }

    public static function create(array $d): int
    {
        return CertificationRepository::create($d);
    }

    public static function update(int $id, array $d): void
    {
        CertificationRepository::update($id, $d);
    }

    public static function delete(int $id): void
    {
        CertificationRepository::delete($id);
    }
}
