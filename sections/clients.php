<?php
/** @var array $clients */
if (empty($clients)) return;

$resolve = static function (string $logo): string {
    if ($logo === "") return "";
    if (strpos($logo, "://") !== false) return $logo;
    return "/uploads/images/" . htmlspecialchars($logo);
};
?>
<section id="clients" class="section">
    <div class="container">
        <div class="reviews-header reveal">
            <h2 class="section-title centered"><?= htmlspecialchars(\App\Settings::get("clients_title", "Trusted Clients")) ?></h2>
            <p class="section-sub"><?= htmlspecialchars(\App\Settings::get("clients_subtitle", "Brands and creators I've had the privilege to work with.")) ?></p>
        </div>

        <div class="clients-strip reveal">
            <div class="clients-track">
                <?php
                // Render the list twice so the marquee loops seamlessly.
                for ($pass = 0; $pass < 2; $pass++):
                    foreach ($clients as $c):
                        $logo = $resolve((string)$c["logo"]);
                        $tag = $c["link_url"] ? "a" : "div";
                ?>
                    <<?= $tag ?>
                        class="client-card"
                        <?= $c["link_url"] ? 'href="' . htmlspecialchars((string)$c["link_url"]) . '" target="_blank" rel="noopener"' : '' ?>
                        <?= $pass === 1 ? 'aria-hidden="true"' : '' ?>
                        title="<?= htmlspecialchars($c["name"]) ?>">
                        <?php if ($logo): ?>
                            <img src="<?= $logo ?>" alt="<?= htmlspecialchars($c["name"]) ?> logo" loading="lazy"
                                 onerror="this.style.display='none';this.nextElementSibling.style.display='inline-flex';">
                            <span class="client-fallback" style="display:none"><?= htmlspecialchars(mb_substr($c["name"], 0, 1)) ?></span>
                        <?php else: ?>
                            <span class="client-fallback"><?= htmlspecialchars(mb_substr($c["name"], 0, 1)) ?></span>
                        <?php endif; ?>
                        <span class="client-name"><?= htmlspecialchars($c["name"]) ?></span>
                    </<?= $tag ?>>
                <?php endforeach; endfor; ?>
            </div>
        </div>
    </div>
</section>
