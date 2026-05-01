<?php
/** @var array $projects */
/** @var array $site */
use App\Project;
use App\ProjectImage;
use App\Software;

$assets = $site["asset_base"];

// Resolve cover image path. Accepts:
//   - http(s):// URL → pass through
//   - filename in /uploads/images/ → /uploads/images/<file>
//   - otherwise → /assets/images/<file> (bundled placeholder)
$resolveImg = static function (string $img) use ($assets): string {
    if ($img === "") return "";
    if (strpos($img, "://") !== false) return $img;
    if (file_exists(__DIR__ . "/../uploads/images/" . $img)) return "/uploads/images/" . htmlspecialchars($img);
    return "{$assets}/images/" . htmlspecialchars($img);
};
$resolveExtra = static function (string $img): string {
    if (strpos($img, "://") !== false) return $img;
    return "/uploads/images/" . htmlspecialchars($img);
};

// Build payload + sub-category counts per main category.
$payload = [];
$subCounts = ["video" => [], "graphic" => []];

foreach ($projects as $p) {
    $main = ($p["main_category"] ?? "graphic") === "video" ? "video" : "graphic";
    $sub  = (string)($p["sub_category"] ?? "");
    $kind = (string)($p["media_kind"] ?? ($main === "video" ? "video" : "gallery"));
    $sw   = Software::parse((string)($p["software"] ?? ""));

    // Gallery images (cover first, then extras)
    $images = [];
    $cover = $resolveImg((string)($p["image"] ?? ""));
    if ($cover) $images[] = $cover;
    foreach (ProjectImage::forProject((int)$p["id"]) as $g) {
        $images[] = $resolveExtra((string)$g["filename"]);
    }
    if (!$images) $images[] = "{$assets}/images/project-1.png";

    // Self-hosted video metadata (preferred over the YouTube/Vimeo fallback).
    $videoFile  = (string)($p["video_file"] ?? "");
    $videoSrc   = "";
    $videoMime  = "";
    if ($videoFile !== "") {
        $videoSrc = "/uploads/videos/" . rawurlencode($videoFile);
        $ext      = strtolower(pathinfo($videoFile, PATHINFO_EXTENSION));
        $videoMime = match ($ext) {
            "mp4", "m4v" => "video/mp4",
            "webm"       => "video/webm",
            "mov"        => "video/quicktime",
            "ogg", "ogv" => "video/ogg",
            default      => "",
        };
    }
    $posterName = (string)($p["video_poster"] ?? "");
    $posterUrl  = $posterName !== "" ? "/uploads/images/" . rawurlencode($posterName) : ($images[0] ?? "");

    $payload[] = [
        "id"        => (int)$p["id"],
        "title"     => $p["title"],
        "main"      => $main,
        "sub"       => $sub,
        "kind"      => $kind,
        "videoUrl"  => (string)($p["video_url"] ?? ""),
        "videoFile" => $videoSrc,
        "videoMime" => $videoMime,
        "poster"    => $posterUrl,
        "extUrl"    => (string)($p["project_url"] ?? ""),
        "skills"    => (string)($p["skills_used"] ?? ""),
        "description" => (string)($p["description"] ?? ""),
        "software"  => array_values(array_map(static fn($k, $v) => [
            "label" => $v[0], "letters" => $v[1], "color" => $v[2], "bg" => $v[3],
        ], array_keys($sw), $sw)),
        "images"    => $images,
        "cover"     => $images[0],
    ];

    if ($sub) {
        $subCounts[$main][$sub] = ($subCounts[$main][$sub] ?? 0) + 1;
    }
}
?>
<?php
$totalAll     = count($payload);
$totalVideo   = count(array_filter($payload, fn($p) => $p["main"] === "video"));
$totalGraphic = count(array_filter($payload, fn($p) => $p["main"] === "graphic"));
?>
<section id="projects" class="section">
    <div class="container">
        <div class="projects-header reveal">
            <span class="section-eyebrow"><?= htmlspecialchars(\App\Settings::get("projects_eyebrow", "Portfolio")) ?></span>
            <h2 class="section-title centered"><?= htmlspecialchars(\App\Settings::get("projects_title", "Selected Work")) ?></h2>
            <p class="section-sub"><?= htmlspecialchars(\App\Settings::get("projects_subtitle", "Curated projects across motion and graphic design — tap any card to dive in.")) ?></p>

            <div class="filter-stack">
                <div class="filter-bar main-filter" role="tablist" aria-label="Main category">
                    <button class="filter-btn active" data-main="all">
                        <i class="fa-solid fa-grip"></i> <?= htmlspecialchars(\App\Settings::get("projects_filter_all", "All Work")) ?>
                        <span class="sub-count"><?= $totalAll ?></span>
                    </button>
                    <button class="filter-btn" data-main="video">
                        <i class="fa-solid fa-circle-play"></i> <?= htmlspecialchars(\App\Settings::get("projects_filter_video", "Video & Motion")) ?>
                        <?php if ($totalVideo): ?><span class="sub-count"><?= $totalVideo ?></span><?php endif; ?>
                    </button>
                    <button class="filter-btn" data-main="graphic">
                        <i class="fa-solid fa-palette"></i> <?= htmlspecialchars(\App\Settings::get("projects_filter_graphic", "Graphic Design")) ?>
                        <?php if ($totalGraphic): ?><span class="sub-count"><?= $totalGraphic ?></span><?php endif; ?>
                    </button>
                </div>

                <?php foreach (["video", "graphic"] as $m): if (empty($subCounts[$m])) continue; ?>
                    <div class="filter-bar sub-filter" data-sub-for="<?= $m ?>" role="tablist" aria-label="<?= $m ?> sub-categories" hidden>
                        <button class="filter-btn sub active" data-sub="all">All</button>
                        <?php foreach (Project::SUB_CATEGORIES[$m] as $sub):
                            if (empty($subCounts[$m][$sub])) continue; ?>
                            <button class="filter-btn sub" data-sub="<?= htmlspecialchars($sub) ?>">
                                <?= htmlspecialchars($sub) ?>
                                <span class="sub-count"><?= (int)$subCounts[$m][$sub] ?></span>
                            </button>
                        <?php endforeach; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="projects-toolbar reveal">
            <span class="pc-count" id="projectsCounter">Showing <strong>0</strong> of <strong>0</strong></span>
        </div>

        <div class="projects-empty" id="projectsEmpty" hidden>
            <i class="fa-regular fa-folder-open"></i>
            <div><?= htmlspecialchars(\App\Settings::get("projects_empty_label", "No projects in this category yet — try another tab.")) ?></div>
        </div>

        <div class="projects-grid v2" id="projectsGrid">
            <?php foreach ($payload as $p): ?>
                <article class="project-card v2 reveal"
                         data-main="<?= htmlspecialchars($p["main"]) ?>"
                         data-sub="<?= htmlspecialchars($p["sub"]) ?>"
                         data-kind="<?= htmlspecialchars($p["kind"]) ?>"
                         data-id="<?= (int)$p["id"] ?>">
                    <div class="pc-media">
                        <img src="<?= htmlspecialchars($p["cover"]) ?>"
                             alt="<?= htmlspecialchars($p["title"]) ?>"
                             loading="lazy">
                        <span class="pc-badge"><?= htmlspecialchars($p["sub"] ?: ($p["main"] === "video" ? "Video" : "Graphic")) ?></span>
                        <?php if ($p["kind"] === "video"): ?>
                            <span class="pc-play"><i class="fa-solid fa-play"></i></span>
                        <?php elseif ($p["kind"] === "gallery"): ?>
                            <span class="pc-play kind-gallery"><i class="fa-solid fa-expand"></i></span>
                        <?php else: ?>
                            <span class="pc-play kind-link"><i class="fa-solid fa-arrow-up-right-from-square"></i></span>
                        <?php endif; ?>
                    </div>
                    <div class="pc-body">
                        <h3 class="pc-title"><?= htmlspecialchars($p["title"]) ?></h3>
                        <?php if ($p["skills"]): ?>
                            <div class="pc-skills"><?= htmlspecialchars($p["skills"]) ?></div>
                        <?php endif; ?>
                        <?php if ($p["software"]): ?>
                            <div class="pc-software">
                                <?php foreach ($p["software"] as $s): ?>
                                    <span class="sw-chip" title="<?= htmlspecialchars($s["label"]) ?>"
                                          style="background:<?= htmlspecialchars($s["bg"]) ?>;color:<?= htmlspecialchars($s["color"]) ?>">
                                        <?= htmlspecialchars($s["letters"]) ?>
                                    </span>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>

        <div class="projects-loadmore-row reveal">
            <button class="btn-loadmore" id="projectsLoadMore" type="button" hidden>
                <i class="fa-solid fa-circle-plus"></i>
                <span><?= htmlspecialchars(\App\Settings::get("projects_loadmore_label", "Load More")) ?></span>
                <span class="sub-count" id="projectsLoadMoreCount"></span>
            </button>
        </div>
    </div>

    <!-- ============ Video player modal ============
         The close button lives INSIDE the stage so it sits at the corner of the
         actual video frame (not floating in the viewport corner where it would
         look stranded on wide screens or get visually overlapped by the iframe).
         The inline onclick is a hard fallback in case the document-level
         delegated handler fails for any reason. -->
    <div class="pj-modal" id="pjVideo" role="dialog" aria-modal="true" hidden>
        <div class="pj-modal-stage video-stage">
            <button type="button" class="pj-close" data-close aria-label="Close video"
                    onclick="if(window.__pjClose)window.__pjClose(event)">
                <i class="fa-solid fa-times"></i>
            </button>
            <div class="pj-player" id="pjVideoMount"></div>
        </div>
    </div>

    <!-- ============ Lightbox (graphic galleries) ============ -->
    <div class="pj-modal pj-lightbox" id="pjLightbox" role="dialog" aria-modal="true" hidden>
        <button class="pj-nav prev" data-lb-prev><i class="fa-solid fa-chevron-left"></i></button>
        <button class="pj-nav next" data-lb-next><i class="fa-solid fa-chevron-right"></i></button>
        <div class="pj-zoom-bar">
            <button data-zoom="-1" aria-label="Zoom out"><i class="fa-solid fa-minus"></i></button>
            <span id="pjZoomLevel">100%</span>
            <button data-zoom="1" aria-label="Zoom in"><i class="fa-solid fa-plus"></i></button>
            <button data-zoom="0" aria-label="Reset zoom"><i class="fa-solid fa-rotate-left"></i></button>
        </div>
        <div class="pj-modal-stage" id="pjLbStage">
            <button type="button" class="pj-close" data-close aria-label="Close lightbox"
                    onclick="if(window.__pjClose)window.__pjClose(event)">
                <i class="fa-solid fa-times"></i>
            </button>
            <img id="pjLbImg" src="" alt="">
        </div>
        <div class="pj-counter" id="pjLbCounter"></div>
    </div>

    <script id="pjData" type="application/json"><?= json_encode($payload, JSON_UNESCAPED_SLASHES) ?></script>
</section>
