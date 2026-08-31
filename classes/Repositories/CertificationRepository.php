<?php
declare(strict_types=1);

namespace App\Repositories;

class CertificationRepository extends BaseRepository
{
    protected static string $table = 'certifications';

    public static function all(): array
    {
        $rows = self::columnExists('certifications', 'certificate_name')
            ? static::pdo()->query(
                'SELECT * FROM certifications ORDER BY sort_order ASC, issue_date DESC, id DESC'
            )->fetchAll()
            : static::pdo()->query(
                'SELECT * FROM certifications ORDER BY sort_order ASC, id DESC'
            )->fetchAll();

        return array_map([self::class, 'normalizeRow'], $rows);
    }

    public static function create(array $d): int
    {
        $payload = self::normalizePayload($d);

        if (self::columnExists('certifications', 'certificate_name')) {
            static::pdo()->prepare(
                'INSERT INTO certifications (
                    title, issuer, year,
                    certificate_name, issuing_organization, organization_logo, certificate_image,
                    issue_date, expiration_date, credential_id, credential_url, verification_url,
                    description, skills, sort_order, is_published, updated_at
                ) VALUES (
                    :title, :issuer, :year,
                    :certificate_name, :issuing_organization, :organization_logo, :certificate_image,
                    :issue_date, :expiration_date, :credential_id, :credential_url, :verification_url,
                    :description, :skills, :sort_order, :is_published, CURRENT_TIMESTAMP
                )'
            )->execute([
                ':title' => $payload['certificate_name'],
                ':issuer' => $payload['issuing_organization'],
                ':year' => $payload['issue_date'],
                ':certificate_name' => $payload['certificate_name'],
                ':issuing_organization' => $payload['issuing_organization'],
                ':organization_logo' => $payload['organization_logo'],
                ':certificate_image' => $payload['certificate_image'],
                ':issue_date' => $payload['issue_date'],
                ':expiration_date' => $payload['expiration_date'],
                ':credential_id' => $payload['credential_id'],
                ':credential_url' => $payload['credential_url'],
                ':verification_url' => $payload['verification_url'],
                ':description' => $payload['description'],
                ':skills' => $payload['skills'],
                ':sort_order' => $payload['sort_order'],
                ':is_published' => $payload['is_published'],
            ]);
        } else {
            static::pdo()->prepare(
                'INSERT INTO certifications (title, issuer, year, credential_url, sort_order) VALUES (:title, :issuer, :year, :credential_url, :sort_order)'
            )->execute([
                ':title' => $payload['certificate_name'],
                ':issuer' => $payload['issuing_organization'],
                ':year' => $payload['issue_date'],
                ':credential_url' => $payload['credential_url'],
                ':sort_order' => $payload['sort_order'],
            ]);
        }

        return static::lastInsertId();
    }

    public static function update(int $id, array $d): void
    {
        $payload = self::normalizePayload($d);

        if (self::columnExists('certifications', 'certificate_name')) {
            static::pdo()->prepare(
                'UPDATE certifications SET
                   title=:title,
                   issuer=:issuer,
                   year=:year,
                   certificate_name=:certificate_name,
                   issuing_organization=:issuing_organization,
                   organization_logo=:organization_logo,
                   certificate_image=:certificate_image,
                   issue_date=:issue_date,
                   expiration_date=:expiration_date,
                   credential_id=:credential_id,
                   credential_url=:credential_url,
                   verification_url=:verification_url,
                   description=:description,
                   skills=:skills,
                   sort_order=:sort_order,
                   is_published=:is_published,
                   updated_at=CURRENT_TIMESTAMP
                 WHERE id=:id'
            )->execute([
                ':title' => $payload['certificate_name'],
                ':issuer' => $payload['issuing_organization'],
                ':year' => $payload['issue_date'],
                ':certificate_name' => $payload['certificate_name'],
                ':issuing_organization' => $payload['issuing_organization'],
                ':organization_logo' => $payload['organization_logo'],
                ':certificate_image' => $payload['certificate_image'],
                ':issue_date' => $payload['issue_date'],
                ':expiration_date' => $payload['expiration_date'],
                ':credential_id' => $payload['credential_id'],
                ':credential_url' => $payload['credential_url'],
                ':verification_url' => $payload['verification_url'],
                ':description' => $payload['description'],
                ':skills' => $payload['skills'],
                ':sort_order' => $payload['sort_order'],
                ':is_published' => $payload['is_published'],
                ':id' => $id,
            ]);
        } else {
            static::pdo()->prepare(
                'UPDATE certifications SET title=:title, issuer=:issuer, year=:year, credential_url=:credential_url, sort_order=:sort_order WHERE id=:id'
            )->execute([
                ':title' => $payload['certificate_name'],
                ':issuer' => $payload['issuing_organization'],
                ':year' => $payload['issue_date'],
                ':credential_url' => $payload['credential_url'],
                ':sort_order' => $payload['sort_order'],
                ':id' => $id,
            ]);
        }
    }

    private static function normalizeRow(array $row): array
    {
        $certificateName = (string) ($row['certificate_name'] ?? $row['title'] ?? '');
        $issuer = (string) ($row['issuing_organization'] ?? $row['issuer'] ?? '');
        $issueDate = (string) ($row['issue_date'] ?? $row['year'] ?? '');

        return [
            'id' => (int) ($row['id'] ?? 0),
            'certificate_name' => $certificateName,
            'title' => $certificateName,
            'issuing_organization' => $issuer,
            'issuer' => $issuer,
            'organization_logo' => (string) ($row['organization_logo'] ?? ''),
            'certificate_image' => (string) ($row['certificate_image'] ?? ''),
            'issue_date' => $issueDate,
            'year' => $issueDate,
            'expiration_date' => (string) ($row['expiration_date'] ?? ''),
            'credential_id' => (string) ($row['credential_id'] ?? ''),
            'credential_url' => (string) ($row['credential_url'] ?? ''),
            'verification_url' => (string) ($row['verification_url'] ?? ''),
            'description' => (string) ($row['description'] ?? ''),
            'skills' => self::decodeList((string) ($row['skills'] ?? '')),
            'sort_order' => (int) ($row['sort_order'] ?? 0),
            'is_published' => (int) ($row['is_published'] ?? 1),
            'created_at' => $row['created_at'] ?? null,
            'updated_at' => $row['updated_at'] ?? null,
        ];
    }

    private static function normalizePayload(array $d): array
    {
        $name = trim((string) ($d['certificate_name'] ?? $d['title'] ?? ''));
        $issuer = trim((string) ($d['issuing_organization'] ?? $d['issuer'] ?? ''));
        $issueDate = trim((string) ($d['issue_date'] ?? $d['year'] ?? ''));
        $expirationDate = trim((string) ($d['expiration_date'] ?? ''));

        return [
            'certificate_name' => $name,
            'issuing_organization' => $issuer,
            'organization_logo' => trim((string) ($d['organization_logo'] ?? '')),
            'certificate_image' => trim((string) ($d['certificate_image'] ?? '')),
            'issue_date' => $issueDate,
            'expiration_date' => $expirationDate,
            'credential_id' => trim((string) ($d['credential_id'] ?? '')),
            'credential_url' => trim((string) ($d['credential_url'] ?? '')),
            'verification_url' => trim((string) ($d['verification_url'] ?? '')),
            'description' => trim((string) ($d['description'] ?? '')),
            'skills' => self::encodeList($d['skills'] ?? $d['technologies'] ?? []),
            'sort_order' => (int) ($d['sort_order'] ?? 0),
            'is_published' => !empty($d['is_published']) ? 1 : 0,
        ];
    }

    private static function encodeList(mixed $value): string
    {
        $items = [];
        if (is_array($value)) {
            foreach ($value as $item) {
                $txt = trim((string) $item);
                if ($txt !== '') {
                    $items[] = $txt;
                }
            }
        } elseif (is_string($value)) {
            foreach (preg_split('/\r\n|\n|,/', $value) as $item) {
                $txt = trim((string) $item);
                if ($txt !== '') {
                    $items[] = $txt;
                }
            }
        }

        return $items ? json_encode(array_values(array_unique($items)), JSON_UNESCAPED_SLASHES) : '[]';
    }

    private static function decodeList(string $value): array
    {
        $val = trim($value);
        if ($val === '') {
            return [];
        }

        $decoded = json_decode($val, true);
        if (is_array($decoded)) {
            return array_values(array_filter(array_map('trim', array_map('strval', $decoded)), fn ($item) => $item !== ''));
        }

        $items = preg_split('/\r\n|\n|,/', $val) ?: [];
        return array_values(array_filter(array_map('trim', array_map('strval', $items)), fn ($item) => $item !== ''));
    }
}
