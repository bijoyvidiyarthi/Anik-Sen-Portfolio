<?php /** @var array $certifications */ ?>
<section id="certifications" class="section certification-section">
    <div class="container">
        <div class="centered-eyebrow reveal">
            <span class="icon-circle">
                <i class="fa-solid fa-certificate"></i>
            </span>
            <h2 class="section-title centered"><?= htmlspecialchars(\App\Settings::get("certifications_title", "Certifications & Credentials"), ENT_QUOTES, "UTF-8") ?></h2>
        </div>

        <?php if (empty($certifications)): ?>
            <div class="empty-state reveal">No certifications added yet.</div>
        <?php else: ?>
            <div class="certifications-grid">
                <?php foreach ($certifications as $item):
                    $name = (string) ($item["certificate_name"] ?? $item["title"] ?? "");
                    $issuer = (string) ($item["issuing_organization"] ?? $item["issuer"] ?? "");
                    $image = (string) ($item["certificate_image"] ?? "");
                    $issueDate = (string) ($item["issue_date"] ?? $item["year"] ?? "");
                    $expirationDate = (string) ($item["expiration_date"] ?? "");
                    $credentialId = (string) ($item["credential_id"] ?? "");
                    $credentialUrl = (string) ($item["credential_url"] ?? "");
                    $verificationUrl = (string) ($item["verification_url"] ?? "");
                    $description = (string) ($item["description"] ?? "");
                    $skills = is_array($item["skills"] ?? null) ? $item["skills"] : [];
                    $formattedIssue = $issueDate !== '' && strtotime($issueDate) !== false ? date('M Y', strtotime($issueDate)) : $issueDate;
                    $formattedExpiration = $expirationDate !== '' && strtotime($expirationDate) !== false ? date('M Y', strtotime($expirationDate)) : $expirationDate;
                ?>
                    <article class="glass-card certification-card reveal">
                        <button type="button" class="cert-preview image-hover-swap" data-direction="lr" data-edge="on" data-mobile="tap" data-cert-image="<?= htmlspecialchars($image !== '' ? '/uploads/images/' . rawurlencode($image) : '', ENT_QUOTES, 'UTF-8') ?>" data-cert-title="<?= htmlspecialchars($name, ENT_QUOTES, 'UTF-8') ?>" aria-label="View certificate preview for <?= htmlspecialchars($name, ENT_QUOTES, 'UTF-8') ?>">
                            <?php if ($image !== ''): ?>
                                <img class="image-hover-base" src="/uploads/images/<?= rawurlencode($image) ?>" alt="<?= htmlspecialchars($name, ENT_QUOTES, 'UTF-8') ?> certificate preview" loading="lazy">
                                <img class="image-hover-overlay" src="/uploads/images/<?= rawurlencode($image) ?>" alt="" aria-hidden="true" loading="lazy">
                                <span class="image-hover-edge"></span>
                                <span class="cert-preview-overlay"><i class="fa-solid fa-magnifying-glass"></i> View Certificate</span>
                            <?php else: ?>
                                <div class="cert-placeholder">
                                    <i class="fa-solid fa-image"></i>
                                    <span>Image Not Available</span>
                                </div>
                            <?php endif; ?>
                        </button>

                        <div class="certification-body">
                            <div class="certification-meta-row">
                                <span class="certification-year"><?= htmlspecialchars($formattedIssue !== '' ? $formattedIssue : 'N/A', ENT_QUOTES, 'UTF-8') ?></span>
                                <?php if ($formattedExpiration !== ''): ?>
                                    <span class="certification-expiration">Expires <?= htmlspecialchars($formattedExpiration, ENT_QUOTES, 'UTF-8') ?></span>
                                <?php endif; ?>
                            </div>

                            <h3 class="certification-title"><?= htmlspecialchars($name, ENT_QUOTES, 'UTF-8') ?></h3>
                            <div class="certification-issuer"><?= htmlspecialchars($issuer, ENT_QUOTES, 'UTF-8') ?></div>

                            <?php if ($credentialId !== ''): ?>
                                <div class="certification-id">Credential ID: <?= htmlspecialchars($credentialId, ENT_QUOTES, 'UTF-8') ?></div>
                            <?php endif; ?>

                            <?php if ($description !== ''): ?>
                                <p class="certification-description"><?= htmlspecialchars($description, ENT_QUOTES, 'UTF-8') ?></p>
                            <?php endif; ?>

                            <?php if (!empty($skills)): ?>
                                <div class="tech-stack cert-skills">
                                    <?php foreach ($skills as $skill): ?>
                                        <span class="tech-tag"><?= htmlspecialchars((string)$skill, ENT_QUOTES, 'UTF-8') ?></span>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>

                            <div class="certification-actions">
                                <?php if ($credentialUrl !== ''): ?>
                                    <a href="<?= htmlspecialchars($credentialUrl, ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener noreferrer" class="cert-link primary">
                                        View Certificate
                                    </a>
                                <?php endif; ?>
                                <?php if ($verificationUrl !== ''): ?>
                                    <a href="<?= htmlspecialchars($verificationUrl, ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener noreferrer" class="cert-link secondary">
                                        Verify Credential
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <div class="certificate-lightbox" id="certificateLightbox" aria-hidden="true" role="dialog" aria-modal="true">
        <button type="button" class="lightbox-close" aria-label="Close preview">&times;</button>
        <div class="lightbox-inner">
            <img id="certificateLightboxImage" src="" alt="Certificate preview">
        </div>
    </div>
</section>
