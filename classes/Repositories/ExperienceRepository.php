<?php
declare(strict_types=1);

namespace App\Repositories;

class ExperienceRepository extends BaseRepository
{
    protected static string $table = 'experiences';

    public static function all(): array
    {
        $rows = self::columnExists('experiences', 'position')
            ? static::pdo()->query(
                'SELECT * FROM experiences ORDER BY CASE WHEN is_current = 1 THEN 0 ELSE 1 END, sort_order ASC, start_date DESC, id DESC'
            )->fetchAll()
            : static::pdo()->query(
                'SELECT * FROM experiences ORDER BY sort_order ASC, id DESC'
            )->fetchAll();

        return array_map([self::class, 'normalizeRow'], $rows);
    }

    public static function create(array $d): int
    {
        $payload = self::normalizePayload($d);

        if (self::columnExists('experiences', 'position')) {
            static::pdo()->prepare(
                'INSERT INTO experiences (
                    position, company_name, company_logo, employment_type, start_date, end_date,
                    is_current, location, work_mode, description, responsibilities, achievements,
                    technologies, company_url, sort_order, is_published, updated_at
                ) VALUES (
                    :position, :company_name, :company_logo, :employment_type, :start_date, :end_date,
                    :is_current, :location, :work_mode, :description, :responsibilities, :achievements,
                    :technologies, :company_url, :sort_order, :is_published, CURRENT_TIMESTAMP
                )'
            )->execute([
                ':position' => $payload['position'],
                ':company_name' => $payload['company_name'],
                ':company_logo' => $payload['company_logo'],
                ':employment_type' => $payload['employment_type'],
                ':start_date' => $payload['start_date'],
                ':end_date' => $payload['end_date'],
                ':is_current' => $payload['is_current'],
                ':location' => $payload['location'],
                ':work_mode' => $payload['work_mode'],
                ':description' => $payload['description'],
                ':responsibilities' => $payload['responsibilities'],
                ':achievements' => $payload['achievements'],
                ':technologies' => $payload['technologies'],
                ':company_url' => $payload['company_url'],
                ':sort_order' => $payload['sort_order'],
                ':is_published' => $payload['is_published'],
            ]);
        } else {
            static::pdo()->prepare(
                'INSERT INTO experiences (company, role, period, description, sort_order) VALUES (:company, :role, :period, :description, :sort_order)'
            )->execute([
                ':company' => $payload['company_name'],
                ':role' => $payload['position'],
                ':period' => $payload['period'],
                ':description' => $payload['description'],
                ':sort_order' => $payload['sort_order'],
            ]);
        }

        return static::lastInsertId();
    }

    public static function update(int $id, array $d): void
    {
        $payload = self::normalizePayload($d);

        if (self::columnExists('experiences', 'position')) {
            static::pdo()->prepare(
                'UPDATE experiences SET
                    position=:position,
                    company_name=:company_name,
                    company_logo=:company_logo,
                    employment_type=:employment_type,
                    start_date=:start_date,
                    end_date=:end_date,
                    is_current=:is_current,
                    location=:location,
                    work_mode=:work_mode,
                    description=:description,
                    responsibilities=:responsibilities,
                    achievements=:achievements,
                    technologies=:technologies,
                    company_url=:company_url,
                    sort_order=:sort_order,
                    is_published=:is_published,
                    updated_at=CURRENT_TIMESTAMP
                 WHERE id=:id'
            )->execute([
                ':position' => $payload['position'],
                ':company_name' => $payload['company_name'],
                ':company_logo' => $payload['company_logo'],
                ':employment_type' => $payload['employment_type'],
                ':start_date' => $payload['start_date'],
                ':end_date' => $payload['end_date'],
                ':is_current' => $payload['is_current'],
                ':location' => $payload['location'],
                ':work_mode' => $payload['work_mode'],
                ':description' => $payload['description'],
                ':responsibilities' => $payload['responsibilities'],
                ':achievements' => $payload['achievements'],
                ':technologies' => $payload['technologies'],
                ':company_url' => $payload['company_url'],
                ':sort_order' => $payload['sort_order'],
                ':is_published' => $payload['is_published'],
                ':id' => $id,
            ]);
        } else {
            static::pdo()->prepare(
                'UPDATE experiences SET company=:company, role=:role, period=:period, description=:description, sort_order=:sort_order WHERE id=:id'
            )->execute([
                ':company' => $payload['company_name'],
                ':role' => $payload['position'],
                ':period' => $payload['period'],
                ':description' => $payload['description'],
                ':sort_order' => $payload['sort_order'],
                ':id' => $id,
            ]);
        }
    }

    private static function normalizeRow(array $row): array
    {
        $position = (string) ($row['position'] ?? $row['role'] ?? '');
        $companyName = (string) ($row['company_name'] ?? $row['company'] ?? '');
        $startDate = (string) ($row['start_date'] ?? '');
        $endDate = (string) ($row['end_date'] ?? '');
        $period = (string) ($row['period'] ?? '');
        $isCurrent = !empty($row['is_current']) || (!empty($period) && stripos($period, 'present') !== false);

        return [
            'id' => (int) ($row['id'] ?? 0),
            'position' => $position,
            'role' => $position,
            'company_name' => $companyName,
            'company' => $companyName,
            'company_logo' => (string) ($row['company_logo'] ?? ''),
            'employment_type' => (string) ($row['employment_type'] ?? 'Full-time'),
            'start_date' => $startDate,
            'end_date' => $endDate,
            'is_current' => (int) ($isCurrent ? 1 : 0),
            'location' => (string) ($row['location'] ?? ''),
            'work_mode' => (string) ($row['work_mode'] ?? ''),
            'description' => (string) ($row['description'] ?? ''),
            'period' => $period !== '' ? $period : self::buildLegacyPeriod($startDate, $endDate, $isCurrent),
            'responsibilities' => self::decodeList((string) ($row['responsibilities'] ?? '')),
            'achievements' => self::decodeList((string) ($row['achievements'] ?? '')),
            'technologies' => self::decodeList((string) ($row['technologies'] ?? '')),
            'company_url' => (string) ($row['company_url'] ?? ''),
            'sort_order' => (int) ($row['sort_order'] ?? 0),
            'is_published' => (int) ($row['is_published'] ?? 1),
            'created_at' => $row['created_at'] ?? null,
            'updated_at' => $row['updated_at'] ?? null,
        ];
    }

    private static function normalizePayload(array $d): array
    {
        $position = trim((string) ($d['position'] ?? $d['role'] ?? ''));
        $companyName = trim((string) ($d['company_name'] ?? $d['company'] ?? ''));
        $companyLogo = trim((string) ($d['company_logo'] ?? ''));
        $startDate = trim((string) ($d['start_date'] ?? ''));
        $endDate = trim((string) ($d['end_date'] ?? ''));
        $isCurrent = !empty($d['is_current']) ? 1 : 0;
        $period = trim((string) ($d['period'] ?? self::buildLegacyPeriod($startDate, $endDate, (bool) $isCurrent)));

        return [
            'position' => $position,
            'company_name' => $companyName,
            'company_logo' => $companyLogo,
            'employment_type' => trim((string) ($d['employment_type'] ?? 'Full-time')),
            'start_date' => $startDate,
            'end_date' => $endDate,
            'is_current' => $isCurrent,
            'location' => trim((string) ($d['location'] ?? '')),
            'work_mode' => trim((string) ($d['work_mode'] ?? '')),
            'description' => trim((string) ($d['description'] ?? '')),
            'responsibilities' => self::encodeList($d['responsibilities'] ?? []),
            'achievements' => self::encodeList($d['achievements'] ?? []),
            'technologies' => self::encodeList($d['technologies'] ?? []),
            'company_url' => trim((string) ($d['company_url'] ?? '')),
            'sort_order' => (int) ($d['sort_order'] ?? 0),
            'is_published' => !empty($d['is_published']) ? 1 : 0,
            'period' => $period,
        ];
    }

    private static function buildLegacyPeriod(string $startDate, string $endDate, bool $isCurrent): string
    {
        $start = $startDate !== '' ? $startDate : 'N/A';
        $end = $isCurrent ? 'Present' : ($endDate !== '' ? $endDate : 'N/A');
        return $start . ' — ' . $end;
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
