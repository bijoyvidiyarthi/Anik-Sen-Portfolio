<?php
/** @var array $site */
$social = $site["social"];
?>
<section id="contact" class="section">
    <div class="container contact-grid">
        <div class="contact-info reveal">
            <?php
            // Title supports an explicit "<br>" or pipe to break across two lines.
            $contactTitle = (string) \App\Settings::get("contact_title", "Let's work together.");
            $contactTitleHtml = nl2br(htmlspecialchars(str_replace(["<br>", "|"], "\n", $contactTitle)));
            ?>
            <h2 class="contact-title"><?= $contactTitleHtml ?></h2>
            <p class="contact-sub">
                <?= nl2br(htmlspecialchars(\App\Settings::get("contact_subtitle", "Have a project in mind? Looking for a versatile creative to join your team? Drop me a message and let's craft something amazing."))) ?>
            </p>

            <ul class="contact-meta">
                <li>
                    <span class="contact-meta-icon"><i class="fa-solid fa-envelope"></i></span>
                    <a href="mailto:<?= htmlspecialchars($site["email"]) ?>"><?= htmlspecialchars($site["email"]) ?></a>
                </li>
                <li>
                    <span class="contact-meta-icon"><i class="fa-solid fa-location-dot"></i></span>
                    <span><?= htmlspecialchars($site["location"]) ?></span>
                </li>
            </ul>

            <div class="social-row">
                <?php if (!empty($social["facebook"])): ?>
                <a href="<?= htmlspecialchars($social["facebook"]) ?>" target="_blank" rel="noopener" class="social-btn" aria-label="Facebook">
                    <i class="fa-brands fa-facebook-f"></i>
                </a>
                <?php endif; ?>
                <?php if (!empty($social["linkedin"])): ?>
                <a href="<?= htmlspecialchars($social["linkedin"]) ?>" target="_blank" rel="noopener" class="social-btn" aria-label="LinkedIn">
                    <i class="fa-brands fa-linkedin-in"></i>
                </a>
                <?php endif; ?>
                <?php if (!empty($social["behance"])): ?>
                <a href="<?= htmlspecialchars($social["behance"]) ?>" target="_blank" rel="noopener" class="social-btn" aria-label="Behance">
                    <i class="fa-brands fa-behance"></i>
                </a>
                <?php endif; ?>
            </div>
        </div>

        <form
            class="glass-card contact-form reveal"
            id="contactForm"
            action="contact.php"
            method="POST"
            novalidate>

            <input type="hidden" name="_csrf" value="<?= htmlspecialchars(\App\Csrf::token(), ENT_QUOTES) ?>">

            <?php
            $S = static fn(string $k, string $d): string => htmlspecialchars(\App\Settings::get($k, $d));
            ?>
            <div class="field-row">
                <div class="field">
                    <label for="cf-name"><?= $S("contact_label_name", "Name") ?></label>
                    <input type="text" id="cf-name" name="name" placeholder="<?= $S("contact_placeholder_name", "John Doe") ?>" required minlength="2" autocomplete="name">
                    <small class="field-error" data-error-for="name"></small>
                </div>
                <div class="field">
                    <label for="cf-email"><?= $S("contact_label_email", "Email") ?></label>
                    <input type="email" id="cf-email" name="email" placeholder="<?= $S("contact_placeholder_email", "john@example.com") ?>" required autocomplete="email">
                    <small class="field-error" data-error-for="email"></small>
                </div>
            </div>

            <div class="field">
                <label for="cf-subject"><?= $S("contact_label_subject", "Subject") ?></label>
                <input type="text" id="cf-subject" name="subject" placeholder="<?= $S("contact_placeholder_subject", "Project Inquiry") ?>" required minlength="5">
                <small class="field-error" data-error-for="subject"></small>
            </div>

            <div class="field">
                <label for="cf-message"><?= $S("contact_label_message", "Message") ?></label>
                <textarea id="cf-message" name="message" rows="5" placeholder="<?= $S("contact_placeholder_message", "Tell me about your project...") ?>" required minlength="10"></textarea>
                <small class="field-error" data-error-for="message"></small>
            </div>

            <button type="submit" class="btn btn-primary btn-block glow" id="contactSubmit">
                <span class="btn-label"><?= $S("contact_submit_label", "Send Message") ?></span>
                <i class="fa-solid fa-paper-plane"></i>
            </button>
        </form>
    </div>
</section>
