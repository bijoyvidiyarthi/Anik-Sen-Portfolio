<?php
declare(strict_types=1);

namespace App\Repositories;

use App\Contracts\PublishableInterface;
use App\Database;

/**
 * S — Single Responsibility: only manages projects data access.
 * O — Open/Closed: extends BaseRepository; new behaviour is added via override, not modification.
 * L — Liskov Substitution: substitutable wherever BaseRepository is expected.
 * I — Interface Segregation: PublishableInterface is only applied here (not on all repos).
 * D — Dependency Inversion: consumers depend on RepositoryInterface, not this concrete class.
 */
class ProjectRepository extends BaseRepository implements PublishableInterface
{
    protected static string $table = 'projects';

    public const MAIN_CATEGORIES = [
        'video'   => 'Web Applications',
        'graphic' => 'Platform Systems',
    ];

    public const SUB_CATEGORIES = [
        'video' => [
            'SaaS Dashboard',
            'E-commerce Experience',
            'Marketplace App',
            'B2B Portal',
            'Productivity Tool',
            'Customer Experience',
            'Developer Platform',
            'Marketing Site',
        ],
        'graphic' => [
            'Backend APIs',
            'Automation',
            'Internal Tools',
            'Data & Analytics',
            'DevOps Workflows',
        ],
    ];

    public const MEDIA_KINDS = [
        'video'   => 'Product demo (modal)',
        'gallery' => 'Screenshot gallery (lightbox)',
        'link'    => 'Project link only',
    ];

    // ── RepositoryInterface ──────────────────────────────────────────────────

    public static function all(bool $publishedOnly = false): array
    {
        $sql = 'SELECT * FROM projects'
             . ($publishedOnly ? ' WHERE is_published = 1' : '')
             . ' ORDER BY sort_order, id';
        return static::pdo()->query($sql)->fetchAll();
    }

    public static function create(array $d, ?string $image = null, ?string $videoFile = null, ?string $videoPoster = null): int
    {
        static::pdo()->prepare(
            'INSERT INTO projects
             (title, category, main_category, sub_category, media_kind, video_url,
              video_file, video_poster, video_type, software, skills_used, image, tech_stack,
              description, project_url, sort_order, is_published)
             VALUES (:t,:c,:mc,:sc,:mk,:vu,:vf,:vp,:vt,:sw,:sk,:i,:ts,:de,:u,:s,:p)'
        )->execute(static::buildParams($d, $image, true, $videoFile, $videoPoster, true, true));
        return static::lastInsertId();
    }

    public static function update(int $id, array $d, ?string $image = null, ?string $videoFile = null, ?string $videoPoster = null): void
    {
        $existing = static::find($id);
        $sql = 'UPDATE projects SET
                  title=:t, category=:c, main_category=:mc, sub_category=:sc,
                  media_kind=:mk, video_url=:vu, video_type=:vt, software=:sw, skills_used=:sk,
                  tech_stack=:ts, description=:de, project_url=:u,
                  sort_order=:s, is_published=:p'
            . ($image       !== null ? ', image=:i'         : '')
            . ($videoFile   !== null ? ', video_file=:vf'   : '')
            . ($videoPoster !== null ? ', video_poster=:vp' : '')
            . ' WHERE id=:id';

        $params = static::buildParams(
            $d, $image, $image !== null,
            $videoFile, $videoPoster,
            $videoFile !== null, $videoPoster !== null,
            (string)($existing['video_file'] ?? ''),
            (string)($existing['video_type'] ?? '')
        );
        $params[':id'] = $id;
        static::pdo()->prepare($sql)->execute($params);
    }

    public static function delete(int $id): void
    {
        static::pdo()->prepare('DELETE FROM project_images WHERE project_id = :id')->execute([':id' => $id]);
        static::pdo()->prepare('DELETE FROM projects WHERE id = :id')->execute([':id' => $id]);
    }

    // ── PublishableInterface ─────────────────────────────────────────────────

    public static function togglePublish(int $id): int
    {
        $row = static::find($id);
        if (!$row) {
            return 0;
        }
        $next = empty($row['is_published']) ? 1 : 0;
        static::pdo()
            ->prepare('UPDATE projects SET is_published = :p WHERE id = :id')
            ->execute([':p' => $next, ':id' => $id]);
        return $next;
    }

    // ── Domain helpers ───────────────────────────────────────────────────────

    public static function clearMedia(int $id, string $field): void
    {
        if (!in_array($field, ['video_file', 'video_poster'], true)) {
            return;
        }
        static::pdo()
            ->prepare("UPDATE projects SET {$field} = NULL WHERE id = :id")
            ->execute([':id' => $id]);
    }

    /** Set video_type directly — used after media removal to keep the derived column consistent. */
    public static function updateVideoType(int $id, string $type): void
    {
        static::pdo()
            ->prepare("UPDATE projects SET video_type = :vt WHERE id = :id")
            ->execute([':vt' => $type, ':id' => $id]);
    }

    // ── Private helpers ──────────────────────────────────────────────────────

    private static function buildParams(
        array   $d,
        ?string $image,
        bool    $includeImage,
        ?string $videoFile          = null,
        ?string $videoPoster        = null,
        bool    $includeVideoFile   = false,
        bool    $includeVideoPoster = false,
        string  $existingVideoFile  = '',
        string  $existingVideoType  = ''
    ): array {
        $main   = ($d['main_category'] ?? 'graphic') === 'video' ? 'video' : 'graphic';
        $sub    = trim((string)($d['sub_category'] ?? ''));
        $mk     = (string)($d['media_kind'] ?? ($main === 'video' ? 'video' : 'gallery'));
        if (!isset(static::MEDIA_KINDS[$mk])) {
            $mk = $main === 'video' ? 'video' : 'gallery';
        }

        $sw = $d['software'] ?? '';
        if (is_array($sw)) {
            $sw = implode(',', array_filter(array_map('trim', $sw)));
        }

        $legacy = $main === 'video' ? 'Web Product' : 'Platform Engineering';

        // Resolve normalised embed URL (converts youtu.be/ID → youtube.com/embed/ID).
        $rawVideoUrl = trim((string)($d['video_url'] ?? ''));
        $videoUrl    = \App\Migrator::toEmbedUrl($rawVideoUrl);

        // Determine the effective local file name for type resolution:
        // use the newly uploaded file name if provided, otherwise fall back
        // to whatever was already stored (so existing files are not erased).
        $effectiveFile = $videoFile ?? ($existingVideoFile !== '' ? $existingVideoFile : null);

        // video_type: explicit POST override takes priority; otherwise derive automatically.
        $submittedType = trim((string)($d['video_type'] ?? ''));
        $videoType     = $submittedType !== ''
            ? $submittedType
            : \App\Migrator::resolveVideoType($videoUrl, $effectiveFile, $existingVideoType);

        $p = [
            ':t'  => trim((string)($d['title'] ?? '')),
            ':c'  => $legacy,
            ':mc' => $main,
            ':sc' => $sub,
            ':mk' => $mk,
            ':vu' => $videoUrl,
            ':vt' => $videoType,
            ':sw' => (string)$sw,
            ':sk' => trim((string)($d['skills_used'] ?? '')),
            ':ts' => trim((string)($d['tech_stack'] ?? '')),
            ':de' => trim((string)($d['description'] ?? '')),
            ':u'  => trim((string)($d['project_url'] ?? '')),
            ':s'  => (int)($d['sort_order'] ?? 0),
            ':p'  => !empty($d['is_published']) ? 1 : 0,
        ];
        if ($includeImage) {
            $p[':i'] = (string)$image;
        }
        if ($includeVideoFile) {
            $p[':vf'] = $videoFile;
        }
        if ($includeVideoPoster) {
            $p[':vp'] = $videoPoster;
        }
        return $p;
    }
}
