<?php /** @var array $education */ ?>
<section id="education" class="section">
    <div class="container education-container">
        <div class="centered-eyebrow reveal">
            <span class="icon-circle">
                <i class="fa-solid fa-graduation-cap"></i>
            </span>
            <h2 class="section-title centered"><?= htmlspecialchars(\App\Settings::get("education_title", "Education")) ?></h2>
        </div>

        <div class="education-grid">
            <?php foreach ($education as $i => $edu): ?>
                <div class="glass-card education-card reveal" data-reveal="<?= $i % 2 === 0 ? 'left' : 'right' ?>">
                    <i class="fa-solid fa-graduation-cap edu-watermark" aria-hidden="true"></i>
                    <div class="edu-year"><?= htmlspecialchars($edu["year"]) ?></div>
                    <h3 class="edu-degree"><?= htmlspecialchars($edu["degree"]) ?></h3>
                    <div class="edu-status"><?= htmlspecialchars($edu["status"]) ?></div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
