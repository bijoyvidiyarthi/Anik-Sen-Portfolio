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
<section id="about" class="section about-section">
    <div class="container">
        <div class="section-eyebrow reveal">
            <span class="eyebrow-line" aria-hidden="true"></span>
            <h2 class="section-title"><?= htmlspecialchars(\App\Settings::get("about_title", "About Me"), ENT_QUOTES, "UTF-8") ?></h2>
        </div>

        <div class="about-grid">
            <div class="about-bio reveal">
                <?php if (!empty($bio)): ?>
                    <?php foreach ($bio as $paragraph): ?>
                        <p><?= htmlspecialchars((string) $paragraph, ENT_QUOTES, "UTF-8") ?></p>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p><?= htmlspecialchars(\App\Settings::get("about_default_text", "I design stories that turn ideas into memorable visual experiences."), ENT_QUOTES, "UTF-8") ?></p>
                <?php endif; ?>
            </div>

            <aside class="about-card glass-card reveal" aria-labelledby="about-card-title">
                <div class="about-card-glow" aria-hidden="true"></div>

                <h3 id="about-card-title" class="about-card-title">
                    <i class="fa-solid fa-user" aria-hidden="true"></i>
                    <?= htmlspecialchars(\App\Settings::get("about_card_title", "Core Expertise"), ENT_QUOTES, "UTF-8") ?>
                </h3>

                <?php if (!empty($expertise)): ?>
                    <ul class="expertise-list">
                        <?php foreach ($expertise as $item): ?>
                            <?php
                            $title = (string) ($item["title"] ?? "Expertise");
                            $description = (string) ($item["description"] ?? "");
                            $icon = $item["icon"] ?? "star";
                            ?>
                            <li class="expertise-item">
                                <span class="expertise-icon" aria-hidden="true">
                                    <i class="<?= htmlspecialchars($iconMap[$icon] ?? "fa-solid fa-star", ENT_QUOTES, "UTF-8") ?>"></i>
                                </span>
                                <div class="expertise-content">
                                    <div class="expertise-title"><?= htmlspecialchars($title, ENT_QUOTES, "UTF-8") ?></div>
                                    <?php if ($description !== ""): ?>
                                        <div class="expertise-desc"><?= htmlspecialchars($description, ENT_QUOTES, "UTF-8") ?></div>
                                    <?php endif; ?>
                                </div>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php else: ?>
                    <p class="empty-state"><?= htmlspecialchars(\App\Settings::get("about_expertise_empty", "Expertise details will appear here."), ENT_QUOTES, "UTF-8") ?></p>
                <?php endif; ?>
            </aside>
        </div>
    </div>
</section>
