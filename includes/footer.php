<?php /** @var array $site */ ?>
    </main>

    <footer class="footer" role="contentinfo">
        <div class="container">
            <div class="footer-row">
                <div class="logo footer-logo">
                    <?php if (!empty($site["logo"])): ?>
                        <img src="/uploads/images/<?= htmlspecialchars($site["logo"]) ?>" alt="<?= htmlspecialchars($site["name"]) ?>" style="height:30px">
                    <?php else: ?>
                        ANIK<span class="logo-dot">.</span>SEN
                    <?php endif; ?>
                </div>

                <nav class="footer-links" aria-label="Footer">
                    <?php
                    $footerNav = \App\MenuItem::visible("footer");
                    if (!$footerNav) {
                        $footerNav = [
                            ["label" => "About",     "href" => "#about"],
                            ["label" => "Skills",    "href" => "#skills"],
                            ["label" => "Work",      "href" => "#projects"],
                            ["label" => "Education", "href" => "#education"],
                            ["label" => "Contact",   "href" => "#contact"],
                        ];
                    }
                    foreach ($footerNav as $f): ?>
                        <a href="<?= htmlspecialchars($f["href"]) ?>"><?= htmlspecialchars($f["label"]) ?></a>
                    <?php endforeach; ?>
                </nav>

                <button class="back-to-top" id="backToTop" aria-label="Back to top">
                    <i class="fa-solid fa-arrow-up"></i>
                </button>
            </div>

            <div class="footer-meta">
                <p>&copy; <?= date("Y") ?> <?= htmlspecialchars($site["name"]) ?>. <?= htmlspecialchars(\App\Settings::get("footer_copyright", "All rights reserved.")) ?></p>
            </div>
        </div>
    </footer>

    <div class="toast" id="toast" role="status" aria-live="polite"></div>

    <script src="<?= $site["asset_base"] ?>/js/main.js" defer></script>
</body>
</html>
