<?php
declare(strict_types=1);

namespace App\Contracts;

/**
 * I — Interface Segregation: upload contract is separate from repository contract.
 * D — Dependency Inversion: services depend on this abstraction, not Upload directly.
 */
interface FileUploaderInterface
{
    /**
     * Validate and store an uploaded file.
     *
     * @param  array  $file       The entry from $_FILES
     * @param  string $targetDir  Absolute path to the destination directory
     * @return string             The stored filename (basename only)
     */
    public function upload(array $file, string $targetDir): string;

    /**
     * Remove a previously stored file from disk.
     */
    public function delete(string $dir, ?string $filename): void;
}
