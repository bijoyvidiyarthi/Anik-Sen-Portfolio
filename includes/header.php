<?php
/** @var array $site */
$assets = $site["asset_base"];

$hexToRgb = static function (string $hex): string {
    $hex = trim($hex);
    $hex = ltrim($hex, '#');
    if (strlen($hex) === 3) {
        $hex = preg_replace('/(.)/', '$1$1', $hex) ?? $hex;
    }
    if (strlen($hex) !== 6) {
        return "139, 92, 246";
    }
    $r = hexdec(substr($hex, 0, 2));
    $g = hexdec(substr($hex, 2, 2));
    $b = hexdec(substr($hex, 4, 2));
    return "$r, $g, $b";
};

// Header navigation comes from the menu_items table — admins can toggle each
// item from /admin/sections.php (Header Menu tab).
$nav = \App\MenuItem::visible("header");
if (!$nav) {
    // Sensible fallback if the table is empty for any reason.
    $nav = [
        ["label" => "Home",      "href" => "#hero"],
        ["label" => "About",     "href" => "#about"],
        ["label" => "Skills",    "href" => "#skills"],
        ["label" => "Work",      "href" => "#projects"],
        ["label" => "Education", "href" => "#education"],
        ["label" => "Reviews",   "href" => "#reviews"],
        ["label" => "Contact",   "href" => "#contact"],
    ];
}

$themePrimary = trim((string) \App\Settings::get("theme_primary", "#8b5cf6"));
$themeSecondary = trim((string) \App\Settings::get("theme_secondary", "#22d3ee"));
$themeAccent = trim((string) \App\Settings::get("theme_accent", "#f472b6"));
$themeBackground = trim((string) \App\Settings::get("theme_background", "#0a0b14"));
$themeAnimation = trim((string) \App\Settings::get("theme_animation", "dynamic"));
$theme3DEnabled = filter_var(\App\Settings::get("theme_3d_enabled", "1"), FILTER_VALIDATE_BOOLEAN) ? "on" : "off";
$theme3DDepth = max(8, min(28, (int) \App\Settings::get("theme_3d_depth", "18")));
$imageHoverEnabled = filter_var(\App\Settings::get("image_hover_enabled", "1"), FILTER_VALIDATE_BOOLEAN) ? "on" : "off";
$imageHoverDuration = (float) \App\Settings::get("image_hover_duration", "0.9");
$imageHoverEase = trim((string) \App\Settings::get("image_hover_easing", "cubic-bezier(0.22, 1, 0.36, 1)"));
$imageHoverScale = (float) \App\Settings::get("image_hover_scale", "1.05");
$imageHoverParallax = (int) \App\Settings::get("image_hover_parallax", "12");
$imageHoverTilt = (int) \App\Settings::get("image_hover_tilt", "8");
$imageHoverMouseStrength = (int) \App\Settings::get("image_hover_mouse_strength", "12");
$imageHoverEdge = filter_var(\App\Settings::get("image_hover_edge", "1"), FILTER_VALIDATE_BOOLEAN) ? "on" : "off";
$imageHoverEdgeBlur = (int) \App\Settings::get("image_hover_edge_blur", "12");
$imageHoverEdgeGlow = (float) \App\Settings::get("image_hover_edge_glow", "0.7");
$imageHoverReverse = filter_var(\App\Settings::get("image_hover_reverse", "1"), FILTER_VALIDATE_BOOLEAN) ? "on" : "off";
$imageHoverDirection = trim((string) \App\Settings::get("image_hover_direction", "lr"));
$imageHoverClipStyle = trim((string) \App\Settings::get("image_hover_clip_style", "inset"));
$imageHoverMobile = trim((string) \App\Settings::get("image_hover_mobile_behavior", "tap"));
$faviconHref = !empty($site["favicon"])
    ? "/uploads/images/" . htmlspecialchars($site["favicon"])
    : $assets . "/favicon.svg";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title><?= htmlspecialchars($site["name"]) ?> &mdash; Full-Stack Developer &amp; Product Engineer</title>
    <meta name="description" content="Portfolio of <?= htmlspecialchars($site["name"]) ?>, a Full-Stack Developer &amp; Product Engineer based in <?= htmlspecialchars($site["location"]) ?>.">
    <meta name="author" content="<?= htmlspecialchars($site["name"]) ?>">
    <meta name="theme-color" content="#0a0b14">

    <meta property="og:type"        content="website">
    <meta property="og:title"       content="<?= htmlspecialchars($site["name"]) ?> &mdash; Creative Studio">
    <meta property="og:description" content="<?= htmlspecialchars($site["tagline"]) ?>">
    <meta name="twitter:card"       content="summary_large_image">

    <link rel="icon" href="<?= $faviconHref ?>">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=Space+Grotesk:wght@500;600;700&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link rel="stylesheet" href="<?= $assets ?>/css/style.css">
    <style>
        :root {
            --primary: <?= htmlspecialchars($themePrimary) ?>;
            --primary-rgb: <?= htmlspecialchars($hexToRgb($themePrimary)) ?>;
            --secondary: <?= htmlspecialchars($themeSecondary) ?>;
            --secondary-rgb: <?= htmlspecialchars($hexToRgb($themeSecondary)) ?>;
            --accent: <?= htmlspecialchars($themeAccent) ?>;
            --accent-rgb: <?= htmlspecialchars($hexToRgb($themeAccent)) ?>;
            --bg: <?= htmlspecialchars($themeBackground) ?>;
            --bg-2: <?= htmlspecialchars($themeBackground) ?>;
            --theme-tilt: <?= (int)$theme3DDepth ?>deg;
            --motion-speed: 0.7s;
            --orb-duration: 18s;
            --image-hover-duration: <?= htmlspecialchars((string)$imageHoverDuration) ?>s;
            --image-hover-ease: <?= htmlspecialchars($imageHoverEase) ?>;
            --image-hover-scale: <?= htmlspecialchars((string)$imageHoverScale) ?>;
            --image-hover-parallax: <?= (int)$imageHoverParallax ?>px;
            --image-hover-tilt: <?= (int)$imageHoverTilt ?>deg;
            --image-hover-mouse-strength: <?= (int)$imageHoverMouseStrength ?>px;
            --image-hover-edge-blur: <?= (int)$imageHoverEdgeBlur ?>px;
            --image-hover-edge-glow: <?= htmlspecialchars((string)$imageHoverEdgeGlow) ?>;
            --image-hover-direction: <?= htmlspecialchars($imageHoverDirection) ?>;
            --image-hover-mobile: <?= htmlspecialchars($imageHoverMobile) ?>;
        }

        body[data-theme-animation="subtle"] { --motion-speed: 0.9s; --orb-duration: 22s; }
        body[data-theme-animation="dynamic"] { --motion-speed: 0.7s; --orb-duration: 16s; }
        body[data-theme-animation="bold"] { --motion-speed: 0.45s; --orb-duration: 10s; }
    </style>
</head>
<body data-theme-animation="<?= htmlspecialchars($themeAnimation) ?>" data-3d-enabled="<?= htmlspecialchars($theme3DEnabled) ?>" data-image-hover-enabled="<?= htmlspecialchars($imageHoverEnabled) ?>" data-image-hover-direction="<?= htmlspecialchars($imageHoverDirection) ?>" data-image-hover-mobile="<?= htmlspecialchars($imageHoverMobile) ?>" data-image-hover-reverse="<?= htmlspecialchars($imageHoverReverse) ?>">

    <div class="background-orbs" aria-hidden="true">
        <div class="orb orb-violet"></div>
        <div class="orb orb-cyan"></div>
        <div class="orb orb-magenta"></div>
    </div>
    <div class="noise-overlay" aria-hidden="true"></div>

    <header class="navbar" id="navbar">
        <div class="container nav-inner">
            <a href="#hero" class="logo" aria-label="<?= htmlspecialchars($site["name"]) ?> home">
                <?php if (!empty($site["logo"])): ?>
                    <img src="/uploads/images/<?= htmlspecialchars($site["logo"]) ?>" alt="<?= htmlspecialchars($site["name"]) ?>" style="height:34px;width:auto;display:block">
                <?php else: ?>
                    ANIK<span class="logo-dot">.</span>SEN
                <?php endif; ?>
            </a>

            <nav class="nav-desktop" aria-label="Primary">
                <ul>
                    <?php foreach ($nav as $link): ?>
                        <li><a href="<?= $link["href"] ?>"><?= htmlspecialchars($link["label"]) ?></a></li>
                    <?php endforeach; ?>
                </ul>
                <?php
                $hireLabel = trim((string) \App\Settings::get("header_hire_label", "Hire Me"));
                $hireLink  = trim((string) \App\Settings::get("header_hire_link", "#contact"));
                if ($hireLabel !== ""):
                ?>
                <a href="<?= htmlspecialchars($hireLink ?: "#contact") ?>" class="btn btn-pill btn-primary-soft"><?= htmlspecialchars($hireLabel) ?></a>
                <?php endif; ?>
            </nav>

            <button class="nav-toggle" id="navToggle" aria-label="Open menu" aria-expanded="false">
                <i class="fa-solid fa-bars"></i>
            </button>
        </div>
    </header>

    <div class="nav-mobile" id="navMobile" aria-hidden="true" role="dialog" aria-modal="true" aria-label="Site menu">
        <div class="nav-mobile-panel">
            <button class="nav-mobile-close" id="navMobileClose" aria-label="Close menu" type="button">
                <i class="fa-solid fa-xmark" aria-hidden="true"></i>
            </button>
            <nav class="nav-mobile-body" aria-label="Mobile primary">
                <ul>
                    <?php foreach ($nav as $link): ?>
                        <li><a href="<?= $link["href"] ?>" class="nav-mobile-link"><?= htmlspecialchars($link["label"]) ?></a></li>
                    <?php endforeach; ?>
                </ul>
            </nav>
        </div>
    </div>

    <main>
