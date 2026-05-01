<?php /** @var array $reviews */ ?>
<section id="reviews" class="section">
    <div class="container">
        <div class="reviews-header reveal">
            <h2 class="section-title centered"><?= htmlspecialchars(\App\Settings::get("reviews_title", "Client Reviews")) ?></h2>
            <p class="section-sub"><?= htmlspecialchars(\App\Settings::get("reviews_subtitle", "What people say about working with me.")) ?></p>
        </div>

        <div class="carousel reveal" id="reviewsCarousel">
            <div class="carousel-viewport">
                <ul class="carousel-track" id="reviewsTrack">
                    <?php foreach ($reviews as $r): ?>
                        <li class="carousel-slide">
                            <div class="glass-card review-card">
                                <i class="fa-solid fa-quote-right review-quote" aria-hidden="true"></i>
                                <p class="review-text">&ldquo;<?= htmlspecialchars($r["body"]) ?>&rdquo;</p>
                                <div class="review-author">
                                    <div class="review-avatar">
                                        <?= htmlspecialchars(mb_substr($r["author"], 0, 1)) ?>
                                    </div>
                                    <div>
                                        <div class="review-name"><?= htmlspecialchars($r["author"]) ?></div>
                                        <div class="review-role"><?= htmlspecialchars($r["role"]) ?></div>
                                    </div>
                                </div>
                            </div>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>

            <div class="carousel-controls">
                <button class="carousel-btn" id="reviewsPrev" aria-label="Previous review">
                    <i class="fa-solid fa-chevron-left"></i>
                </button>
                <button class="carousel-btn" id="reviewsNext" aria-label="Next review">
                    <i class="fa-solid fa-chevron-right"></i>
                </button>
            </div>
        </div>
    </div>
</section>
