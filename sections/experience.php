<?php /** @var array $experiences */ ?>
<section id="experience" class="section experience-section">
    <div class="container">
        <div class="centered-eyebrow reveal">
            <span class="icon-circle">
                <i class="fa-solid fa-briefcase"></i>
            </span>
            <h2 class="section-title centered"><?= htmlspecialchars(\App\Settings::get("experience_title", "Experience"), ENT_QUOTES, "UTF-8") ?></h2>
        </div>

        <?php if (empty($experiences)): ?>
            <div class="empty-state reveal">No experience details added yet.</div>
        <?php else: ?>
            <div class="timeline timeline-experience">
                <?php foreach ($experiences as $index => $experience):
                    $position = (string) ($experience["position"] ?? $experience["role"] ?? "");
                    $company = (string) ($experience["company_name"] ?? $experience["company"] ?? "");
                    $logo = (string) ($experience["company_logo"] ?? "");
                    $employmentType = (string) ($experience["employment_type"] ?? "Full-time");
                    $startDate = (string) ($experience["start_date"] ?? "");
                    $endDate = (string) ($experience["end_date"] ?? "");
                    $isCurrent = !empty($experience["is_current"]);
                    $location = (string) ($experience["location"] ?? "");
                    $workMode = (string) ($experience["work_mode"] ?? "");
                    $description = (string) ($experience["description"] ?? "");
                    $responsibilities = is_array($experience["responsibilities"] ?? null) ? $experience["responsibilities"] : [];
                    $achievements = is_array($experience["achievements"] ?? null) ? $experience["achievements"] : [];
                    $technologies = is_array($experience["technologies"] ?? null) ? $experience["technologies"] : [];
                    $companyUrl = (string) ($experience["company_url"] ?? "");
                    $periodLabel = (string) ($experience["period"] ?? "");
                    if ($periodLabel === '') {
                        $periodLabel = ($startDate !== '' ? $startDate : 'N/A') . ' — ' . ($isCurrent ? 'Present' : ($endDate !== '' ? $endDate : 'N/A'));
                    }
                ?>
                    <article class="timeline-item reveal" data-reveal="<?= $index % 2 === 0 ? 'left' : 'right' ?>">
                        <span class="timeline-dot" aria-hidden="true"></span>
                        <div class="timeline-content glass-card experience-card">
                            <?php if ($logo !== ''): ?>
                                <div class="experience-company-logo">
                                    <img src="/uploads/images/<?= rawurlencode($logo) ?>" alt="<?= htmlspecialchars($company !== '' ? $company : 'Company logo', ENT_QUOTES, 'UTF-8') ?>" loading="lazy">
                                </div>
                            <?php endif; ?>

                            <div class="experience-meta-row">
                                <span class="experience-period"><?= htmlspecialchars($periodLabel, ENT_QUOTES, 'UTF-8') ?></span>
                                <?php if ($isCurrent): ?>
                                    <span class="present-badge">Present</span>
                                <?php endif; ?>
                            </div>

                            <h3 class="experience-role"><?= htmlspecialchars($position, ENT_QUOTES, 'UTF-8') ?></h3>
                            <div class="experience-company-line">
                                <span class="experience-company"><?= htmlspecialchars($company, ENT_QUOTES, 'UTF-8') ?></span>
                                <?php if ($employmentType !== ''): ?>
                                    <span class="experience-employment-type"><?= htmlspecialchars($employmentType, ENT_QUOTES, 'UTF-8') ?></span>
                                <?php endif; ?>
                            </div>

                            <?php if ($location !== '' || $workMode !== ''): ?>
                                <div class="experience-location"><?= htmlspecialchars(trim($location . ($location !== '' && $workMode !== '' ? ' · ' : '') . $workMode), ENT_QUOTES, 'UTF-8') ?></div>
                            <?php endif; ?>

                            <?php if ($description !== ''): ?>
                                <p class="experience-description"><?= htmlspecialchars($description, ENT_QUOTES, 'UTF-8') ?></p>
                            <?php endif; ?>

                            <?php if (!empty($responsibilities)): ?>
                                <ul class="experience-list">
                                    <?php foreach ($responsibilities as $item): ?>
                                        <li><?= htmlspecialchars((string)$item, ENT_QUOTES, 'UTF-8') ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            <?php endif; ?>

                            <?php if (!empty($achievements)): ?>
                                <div class="achievement-block">
                                    <div class="achievement-label">Key achievements</div>
                                    <ul class="achievement-list">
                                        <?php foreach ($achievements as $item): ?>
                                            <li><?= htmlspecialchars((string)$item, ENT_QUOTES, 'UTF-8') ?></li>
                                        <?php endforeach; ?>
                                    </ul>
                                </div>
                            <?php endif; ?>

                            <?php if (!empty($technologies)): ?>
                                <div class="tech-stack">
                                    <?php foreach ($technologies as $tag): ?>
                                        <span class="tech-tag"><?= htmlspecialchars((string)$tag, ENT_QUOTES, 'UTF-8') ?></span>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>

                            <?php if ($companyUrl !== ''): ?>
                                <a class="experience-link" href="<?= htmlspecialchars($companyUrl, ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener noreferrer">
                                    Company site <i class="fa-solid fa-arrow-up-right-from-square"></i>
                                </a>
                            <?php endif; ?>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</section>
