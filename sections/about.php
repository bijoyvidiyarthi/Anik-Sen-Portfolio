<?php
/** @var array $about */
/** @var array $expertise */
$bio = \App\About::paragraphs();
$iconMap = [
    "image"   => "fa-solid fa-image",
    "palette" => "fa-solid fa-palette",
    "video"   => "fa-solid fa-video",
    "camera"  => "fa-solid fa-camera",
    "pen"     => "fa-solid fa-pen-nib",
    "star"    => "fa-solid fa-star",
];
?>
<section id="about" class="section">
    <div class="container">
        <div class="section-eyebrow reveal">
            <span class="eyebrow-line"></span>
            <h2 class="section-title"><?= htmlspecialchars(\App\Settings::get("about_title", "About Me")) ?></h2>
        </div>

        <div class="about-grid">
            <div class="about-bio reveal">
                <?php foreach ($bio as $para): ?>
                    <p><?= htmlspecialchars($para) ?></p>
                <?php endforeach; ?>
            </div>

            <aside class="about-card glass-card reveal">
                <h3 class="about-card-title">
                    <i class="fa-solid fa-user"></i> <?= htmlspecialchars(\App\Settings::get("about_card_title", "Core Expertise")) ?>
                </h3>

                <ul class="expertise-list">
                    <?php foreach ($expertise as $item): ?>
                        <li class="expertise-item">
                            <span class="expertise-icon">
                                <i class="<?= $iconMap[$item["icon"]] ?? "fa-solid fa-star" ?>"></i>
                            </span>
                            <div>
                                <div class="expertise-title"><?= htmlspecialchars($item["title"]) ?></div>
                                <div class="expertise-desc"><?= htmlspecialchars($item["description"]) ?></div>
                            </div>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </aside>
        </div>
    </div>
</section>
