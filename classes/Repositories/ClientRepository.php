<?php
declare(strict_types=1);

namespace App\Repositories;

/**
 * S — Single Responsibility: only manages clients data access.
 * O — Open/Closed: extends BaseRepository without modifying it.
 */
class ClientRepository extends BaseRepository
{
    protected static string $table = 'clients';

    public static function all(bool $visibleOnly = false): array
    {
        $sql = 'SELECT * FROM clients'
             . ($visibleOnly ? ' WHERE is_visible = 1' : '')
             . ' ORDER BY sort_order, id';
        return static::pdo()->query($sql)->fetchAll();
    }

    public static function create(array $d, ?string $logo = null): int
    {
        static::pdo()->prepare(
            'INSERT INTO clients (name, logo, link_url, sort_order, is_visible)
             VALUES (:n, :l, :u, :s, :v)'
        )->execute([
            ':n' => trim((string)($d['name'] ?? '')),
            ':l' => (string)($logo ?? ($d['logo'] ?? '')),
            ':u' => trim((string)($d['link_url'] ?? '')),
            ':s' => (int)($d['sort_order'] ?? 0),
            ':v' => !empty($d['is_visible']) ? 1 : 0,
        ]);
        return static::lastInsertId();
    }

    public static function update(int $id, array $d, ?string $logo = null): void
    {
        $sql = 'UPDATE clients SET name=:n, link_url=:u, sort_order=:s, is_visible=:v'
             . ($logo !== null ? ', logo=:l' : '')
             . ' WHERE id=:id';
        $params = [
            ':n'  => trim((string)($d['name'] ?? '')),
            ':u'  => trim((string)($d['link_url'] ?? '')),
            ':s'  => (int)($d['sort_order'] ?? 0),
            ':v'  => !empty($d['is_visible']) ? 1 : 0,
            ':id' => $id,
        ];
        if ($logo !== null) {
            $params[':l'] = $logo;
        }
        static::pdo()->prepare($sql)->execute($params);
    }

    public static function toggleVisibility(int $id): void
    {
        static::pdo()->prepare(
            'UPDATE clients SET is_visible = CASE WHEN is_visible = 1 THEN 0 ELSE 1 END WHERE id=:id'
        )->execute([':id' => $id]);
    }

    public static function deleteAndReturnLogo(int $id): ?string
    {
        $row = static::find($id);
        if (!$row) {
            return null;
        }
        static::delete($id);
        return (string)$row['logo'];
    }

    public static function isLocalLogo(string $logo): bool
    {
        return $logo !== '' && preg_match('/^\w[\w.\-]+$/', $logo) === 1;
    }

    public static function logoUrl(string $logo): string
    {
        if (!static::isLocalLogo($logo)) {
            return '';
        }
        return '/uploads/images/' . rawurlencode($logo);
    }
}
