<?php
declare(strict_types=1);

namespace App\Contracts;

/**
 * I — Interface Segregation: only entities that support publish/draft toggling
 * need to implement this. Not forced onto every repository.
 */
interface PublishableInterface
{
    public static function togglePublish(int $id): int;
}
