<?php
/** @var array $site */
/** @var array $hero */
$assets    = $site["asset_base"];
$avatarSrc = !empty($hero["avatar"])
    ? "/uploads/images/" . htmlspecialchars((string) $hero["avatar"])
    : (file_exists(__DIR__ . "/../assets/images/hero-avatar.png")
        ? "{$assets}/images/hero-avatar.png"
        : "{$assets}/images/avatar.png");
$phrasesJson = htmlspecialchars(json_encode(array_values($hero["phrases_array"] ?? [])), ENT_QUOTES);
$badge      = $hero["badge_text"]   ?? "Available for freelance work";
$name       = $hero["name"]         ?? "Anik Sen";
$eyebrow    = $hero["eyebrow"]      ?? "Hi, I’m";
$lede       = $hero["lede"]         ?? "";
$ctaLabel   = $hero["cta_label"]    ?? "View Work";
$ctaLink    = $hero["cta_link"]     ?? "#projects";
$cta2Label  = $hero["cta2_label"]   ?? "Download CV";
$cta2Link   = $hero["cta2_link"]    ?? "/cv.php";
$chipT      = $hero["chip_title"]   ?? "Graphic Designer";
$chipS      = $hero["chip_sub"]     ?? "& Video Editor";

// Toggles default to ON if the column is missing or NULL (legacy installs).
$statsOn   = !isset($hero["stats_enabled"])      || (int)$hero["stats_enabled"]      === 1;
$cueOn     = !isset($hero["scroll_cue_enabled"]) || (int)$hero["scroll_cue_enabled"] === 1;
$orbsOn    = !isset($hero["show_orbs"])          || (int)$hero["show_orbs"]          === 1;

$cueLabel  = $hero["scroll_cue_label"] ?? "Scroll";

$stats = [
    ["v" => $hero["stat1_value"] ?? "6+",  "l" => $hero["stat1_label"] ?? "Years Experience"],
    ["v" => $hero["stat2_value"] ?? "20+", "l" => $hero["stat2_label"] ?? "Projects Delivered"],
    ["v" => $hero["stat3_value"] ?? "8+",  "l" => $hero["stat3_label"] ?? "Happy Clients"],
];
// Allow a 2-word label like "Years Experience" to wrap nicely between value & label.
$splitLabel = static function (string $label): string {
    $parts = preg_split('/\s+/', trim($label), 2);
    return count($parts) === 2
        ? htmlspecialchars($parts[0]) . "<br>" . htmlspecialchars($parts[1])
        : htmlspecialchars($label);
};
?>
<section id="hero" class="section section-hero">
    <?php if ($orbsOn): ?>
    <div class="hero-bg-orbs" aria-hidden="true">
        <span class="orb orb-1"></span>
        <span class="orb orb-2"></span>
        <span class="orb orb-3"></span>
    </div>
    <?php endif; ?>

    <div class="hero-container">
        <div class="hero-text fade-up">
            <?php if ($badge !== ""): ?>
            <span class="badge-pill">
                <span class="badge-dot"></span> <?= htmlspecialchars($badge) ?>
            </span>
            <?php endif; ?>

            <h1 class="hero-title">
                <?php if ($eyebrow !== ""): ?>
                    <span class="hero-eyebrow"><?= htmlspecialchars($eyebrow) ?></span>
                <?php endif; ?>
                <span class="hero-name"><?= htmlspecialchars($name) ?></span>
                <span class="hero-dynamic">
                    <span
                        class="typing-accent"
                        id="typingAccent"
                        data-phrases='<?= $phrasesJson ?>'
                        data-type-speed="80"
                        data-delete-speed="50"
                        data-pause="2000"
                        aria-live="polite"
                    ></span><span class="typed-cursor" aria-hidden="true">|</span>
                </span>
            </h1>

            <?php if ($lede !== ""): ?>
            <p class="hero-lede">
                <?= nl2br(htmlspecialchars($lede)) ?>
            </p>
            <?php endif; ?>

            <div class="hero-ctas">
                <?php if ($ctaLabel !== ""): ?>
                <a href="<?= htmlspecialchars($ctaLink) ?>" class="btn btn-pill btn-primary glow">
                    <?= htmlspecialchars($ctaLabel) ?> <i class="fa-solid fa-arrow-right"></i>
                </a>
                <?php endif; ?>
                <?php if ($cta2Label !== ""): ?>
                <a href="<?= htmlspecialchars($cta2Link) ?>" class="btn btn-pill btn-outline"
                   <?= str_starts_with($cta2Link, "/cv.php") ? "download" : "" ?>>
                    <i class="fa-solid fa-arrow-down"></i> <?= htmlspecialchars($cta2Label) ?>
                </a>
                <?php endif; ?>
            </div>

            <?php if ($statsOn): ?>
            <ul class="hero-stats" role="list">
                <?php foreach ($stats as $s): if (trim((string)$s["v"]) === "" && trim((string)$s["l"]) === "") continue; ?>
                <li class="hero-stat">
                    <span class="hero-stat-num"><?= htmlspecialchars((string)$s["v"]) ?></span>
                    <span class="hero-stat-label"><?= $splitLabel((string)$s["l"]) ?></span>
                </li>
                <?php endforeach; ?>
            </ul>
            <?php endif; ?>
        </div>

        <div class="hero-visual fade-up" style="--fade-delay: .2s">
            <!-- Soft slow-pulsing glow ring sits behind the frameless 3D avatar
                 to give the transparent character extra depth on the dark background. -->
            <div class="hero-glow" aria-hidden="true"></div>
            <div class="hero-avatar-wrap">
                <img
                    class="hero-avatar-img floaty"
                    src="<?= $avatarSrc ?>"
                    alt="3D illustration of <?= htmlspecialchars($name) ?> at his designer workspace"
                    loading="eager"
                    width="1024"
                    height="1024">
            </div>

            <?php if (trim($chipT) !== "" || trim($chipS) !== ""): ?>
            <div class="hero-chip">
                <span class="hero-chip-dot"></span>
                <div>
                    <div class="hero-chip-title"><?= htmlspecialchars($chipT) ?></div>
                    <div class="hero-chip-sub"><?= htmlspecialchars($chipS) ?></div>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <?php if ($cueOn): ?>
    <a href="#about" class="hero-scroll-cue" aria-label="Scroll to next section">
        <span class="hero-scroll-mouse"><span class="hero-scroll-wheel"></span></span>
        <span class="hero-scroll-label"><?= htmlspecialchars($cueLabel) ?></span>
    </a>
    <?php endif; ?>
</section>
