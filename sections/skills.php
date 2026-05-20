<?php
/** @var array $creative */
/** @var array $software */
?>
<section id="skills" class="section">
    <div class="container">
        <div class="section-eyebrow centered reveal">
            <span class="eyebrow-line"></span>
            <h2 class="section-title"><?= htmlspecialchars(\App\Settings::get("skills_title", "Skills & Toolkit")) ?></h2>
            <span class="eyebrow-line"></span>
        </div>

        <div class="skills-grid">
            <div class="glass-card skills-card reveal">
                <h3 class="skills-card-title gradient-text"><?= htmlspecialchars(\App\Settings::get("skills_creative", "Creative Skills")) ?></h3>
                <div class="skill-tags">
                    <?php foreach ($creative as $i => $skill): ?>
                        <span class="skill-tag" style="--delay: <?= $i * 0.05 ?>s">
                            <?= htmlspecialchars($skill["name"]) ?>
                        </span>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="glass-card skills-card reveal">
                <h3 class="skills-card-title gradient-text alt"><?= htmlspecialchars(\App\Settings::get("skills_software", "Software Toolkit")) ?></h3>
                <div class="software-grid">
                    <?php foreach ($software as $tool): ?>
                        <?php
                            $color = htmlspecialchars($tool["color"] ?: "#fff");
                            $bg    = htmlspecialchars($tool["bg"]    ?: "#161623");
                            $name  = htmlspecialchars($tool["name"]);
                            $letters = htmlspecialchars($tool["letters"] ?: mb_substr($tool["name"], 0, 2));
                        ?>
                        <div class="software-tile" style="--brand: <?= $color ?>; --brand-bg: <?= $bg ?>">
                            <div class="software-logo" role="img" aria-label="<?= $name ?> logo">
                                <span class="logo-letters"><?= $letters ?></span>
                            </div>
                            <div class="software-meta">
                                <span class="software-name"><?= $name ?></span>
                                <span class="software-tag"><?= htmlspecialchars($tool["tag"] ?? "") ?></span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</section>
