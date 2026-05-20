<?php
/** @var array $site */
$assets = $site["asset_base"];

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

$faviconHref = !empty($site["favicon"])
    ? "/uploads/images/" . htmlspecialchars($site["favicon"])
    : $assets . "/favicon.svg";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title><?= htmlspecialchars($site["name"]) ?> &mdash; Graphic Designer &amp; Video Editor</title>
    <meta name="description" content="Portfolio of <?= htmlspecialchars($site["name"]) ?>, a Graphic Designer &amp; Video Editor based in <?= htmlspecialchars($site["location"]) ?>.">
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
</head>
<body>

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
