<?php
declare(strict_types=1);

namespace App\Services;

use RuntimeException;

/**
 * S — Single Responsibility: low-level filesystem operations only.
 * All upload strategy classes delegate to this helper, so filesystem
 * logic is never duplicated across strategies.
 */
final class FileStorageHelper
{
    public static function move(array $file, string $dir, array $allowed, int $maxBytes): string
    {
        $err = (int)($file['error'] ?? UPLOAD_ERR_NO_FILE);
        if ($err !== UPLOAD_ERR_OK) {
            throw new RuntimeException(self::errorMessage($err));
        }
        if (($file['size'] ?? 0) > $maxBytes) {
            throw new RuntimeException('File too large. Max ' . round($maxBytes / 1024 / 1024) . 'MB.');
        }

        $orig = (string)$file['name'];
        $ext  = strtolower(pathinfo($orig, PATHINFO_EXTENSION));
        if (!in_array($ext, $allowed, true)) {
            throw new RuntimeException("Unsupported file type: .{$ext}");
        }

        if (!is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }
        if (!is_writable($dir)) {
            throw new RuntimeException("Upload directory not writable: $dir");
        }

        $base   = self::slug(pathinfo($orig, PATHINFO_FILENAME));
        $name   = $base . '-' . bin2hex(random_bytes(4)) . '.' . $ext;
        $target = rtrim($dir, '/') . '/' . $name;

        if (!move_uploaded_file($file['tmp_name'], $target)) {
            throw new RuntimeException('Failed to save uploaded file.');
        }
        @chmod($target, 0644);
        return $name;
    }

    public static function remove(string $dir, ?string $filename): void
    {
        if (!$filename) {
            return;
        }
        $path = rtrim($dir, '/') . '/' . basename($filename);
        if (is_file($path)) {
            @unlink($path);
        }
    }

    public static function slug(string $s): string
    {
        $s = strtolower($s);
        $s = preg_replace('/[^a-z0-9]+/', '-', $s) ?? '';
        $s = trim($s, '-');
        return $s !== '' ? substr($s, 0, 60) : 'file';
    }

    public static function errorMessage(int $code): string
    {
        return match ($code) {
            UPLOAD_ERR_INI_SIZE,
            UPLOAD_ERR_FORM_SIZE  => 'File exceeds maximum upload size.',
            UPLOAD_ERR_PARTIAL    => 'File only partially uploaded.',
            UPLOAD_ERR_NO_FILE    => 'No file selected.',
            UPLOAD_ERR_NO_TMP_DIR => 'Server has no temp directory.',
            UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk.',
            UPLOAD_ERR_EXTENSION  => 'Upload blocked by PHP extension.',
            default               => "Unknown upload error ($code).",
        };
    }

    public static function humanSize(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $i = 0;
        $n = (float)$bytes;
        while ($n >= 1024 && $i < count($units) - 1) {
            $n /= 1024;
            $i++;
        }
        return ($i === 0 ? (int)$n : number_format($n, 1)) . ' ' . $units[$i];
    }
}
