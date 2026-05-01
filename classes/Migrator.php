<?php
declare(strict_types=1);

namespace App;

/**
 * Schema bootstrapper. Idempotent — safe to call on every request.
 * Handles first-boot full creation + incremental ALTER migrations on every load.
 */
class Migrator
{
    public static function ensure(array $config): void
    {
        $pdo = Database::pdo();

        // settings table is the marker for "have we done first-boot?"
        $needsFirstBoot = false;
        try {
            $pdo->query("SELECT COUNT(*) FROM settings")->fetchColumn();
        } catch (\PDOException $e) {
            $needsFirstBoot = true;
        }

        if ($needsFirstBoot) {
            self::firstBoot($config);
        }

        // Always run incremental migrations (each is guarded for idempotency).
        self::runIncrementalMigrations();
    }

    private static function firstBoot(array $config): void
    {
        $pdo = Database::pdo();
        $isMysql = Database::isMysql();

        $autoInc = $isMysql ? "INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY"
                            : "INTEGER PRIMARY KEY AUTOINCREMENT";
        $ts      = $isMysql ? "DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP"
                            : "DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP";
        $tinyint = $isMysql ? "TINYINT(1) NOT NULL DEFAULT 1" : "INTEGER NOT NULL DEFAULT 1";
        $intz    = $isMysql ? "INT NOT NULL DEFAULT 0"        : "INTEGER NOT NULL DEFAULT 0";
        $eng     = $isMysql ? "ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci" : "";

        $pdo->beginTransaction();
        try {
            $pdo->exec("CREATE TABLE settings (
                id $autoInc,
                `key` VARCHAR(80) NOT NULL UNIQUE,
                value TEXT NULL,
                updated_at $ts
            ) $eng");

            $pdo->exec("CREATE TABLE admin_users (
                id $autoInc,
                username VARCHAR(80) NOT NULL UNIQUE,
                email VARCHAR(180) NOT NULL UNIQUE,
                password_hash VARCHAR(255) NOT NULL,
                last_login_at DATETIME NULL,
                created_at $ts
            ) $eng");

            $pdo->exec("CREATE TABLE hero_content (
                id $autoInc,
                badge_text VARCHAR(180) NULL,
                name VARCHAR(120) NOT NULL,
                phrases TEXT NULL,
                cta_label VARCHAR(80) NULL,
                cta_link VARCHAR(255) NULL,
                avatar VARCHAR(255) NULL,
                chip_title VARCHAR(120) NULL,
                chip_sub VARCHAR(120) NULL,
                updated_at $ts
            ) $eng");

            $pdo->exec("CREATE TABLE about_content (
                id $autoInc,
                bio TEXT NOT NULL,
                profile_image VARCHAR(255) NULL,
                updated_at $ts
            ) $eng");

            $pdo->exec("CREATE TABLE expertise_items (
                id $autoInc,
                icon VARCHAR(40) NOT NULL,
                title VARCHAR(120) NOT NULL,
                description VARCHAR(255) NOT NULL,
                sort_order $intz,
                created_at $ts
            ) $eng");

            $pdo->exec("CREATE TABLE projects (
                id $autoInc,
                title VARCHAR(180) NOT NULL,
                category VARCHAR(80) NOT NULL,
                image VARCHAR(255) NULL,
                tech_stack VARCHAR(255) NULL,
                description TEXT NULL,
                project_url VARCHAR(255) NULL,
                sort_order $intz,
                is_published $tinyint,
                created_at $ts
            ) $eng");

            $pdo->exec("CREATE TABLE skills (
                id $autoInc,
                name VARCHAR(120) NOT NULL,
                kind VARCHAR(40) NOT NULL DEFAULT 'creative',
                tag VARCHAR(80) NULL,
                letters VARCHAR(8) NULL,
                color VARCHAR(20) NULL,
                bg VARCHAR(20) NULL,
                sort_order $intz,
                created_at $ts
            ) $eng");

            $pdo->exec("CREATE TABLE education (
                id $autoInc,
                year VARCHAR(40) NOT NULL,
                degree VARCHAR(180) NOT NULL,
                status VARCHAR(120) NOT NULL,
                sort_order $intz,
                created_at $ts
            ) $eng");

            $pdo->exec("CREATE TABLE reviews (
                id $autoInc,
                author VARCHAR(120) NOT NULL,
                role VARCHAR(120) NOT NULL,
                body TEXT NOT NULL,
                sort_order $intz,
                created_at $ts
            ) $eng");

            $pdo->exec("CREATE TABLE gallery_categories (
                id $autoInc,
                name VARCHAR(120) NOT NULL,
                parent_id INT NULL,
                slug VARCHAR(140) NOT NULL UNIQUE,
                description VARCHAR(255) NULL,
                sort_order $intz,
                created_at $ts
            ) $eng");

            $pdo->exec("CREATE TABLE gallery_images (
                id $autoInc,
                category_id INT NOT NULL,
                title VARCHAR(180) NULL,
                filename VARCHAR(255) NOT NULL,
                alt_text VARCHAR(255) NULL,
                sort_order $intz,
                created_at $ts
            ) $eng");

            $pdo->exec("CREATE TABLE file_library (
                id $autoInc,
                title VARCHAR(180) NOT NULL,
                folder VARCHAR(120) NOT NULL DEFAULT 'general',
                filename VARCHAR(255) NOT NULL,
                original_name VARCHAR(255) NULL,
                mime VARCHAR(120) NULL,
                size_bytes INT NOT NULL DEFAULT 0,
                description VARCHAR(500) NULL,
                is_active $tinyint,
                created_at $ts
            ) $eng");

            $pdo->exec("CREATE TABLE messages (
                id $autoInc,
                name VARCHAR(120) NOT NULL,
                email VARCHAR(180) NOT NULL,
                subject VARCHAR(180) NOT NULL,
                message TEXT NOT NULL,
                is_read $tinyint,
                created_at $ts
            ) $eng");

            self::seed($config);

            $pdo->commit();
        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    /**
     * Incremental schema migrations. Each guarded by columnExists/tableExists
     * so it's safe to call on every request.
     */
    private static function runIncrementalMigrations(): void
    {
        $pdo = Database::pdo();
        $isMysql = Database::isMysql();

        // --- v2: extended project metadata ---
        if (!self::columnExists("projects", "main_category")) {
            $pdo->exec("ALTER TABLE projects ADD COLUMN main_category VARCHAR(40) NOT NULL DEFAULT 'graphic'");
            $pdo->exec("ALTER TABLE projects ADD COLUMN sub_category VARCHAR(80) NOT NULL DEFAULT ''");
            $pdo->exec("ALTER TABLE projects ADD COLUMN media_kind VARCHAR(20) NOT NULL DEFAULT 'gallery'");
            $pdo->exec("ALTER TABLE projects ADD COLUMN video_url VARCHAR(500) NULL");
            $pdo->exec("ALTER TABLE projects ADD COLUMN software TEXT NULL");
            $pdo->exec("ALTER TABLE projects ADD COLUMN skills_used TEXT NULL");

            // Backfill: map legacy "category" to new main/sub/media_kind.
            $rows = $pdo->query("SELECT id, category FROM projects")->fetchAll();
            $upd = $pdo->prepare("UPDATE projects SET main_category=:m, sub_category=:s, media_kind=:k WHERE id=:id");
            foreach ($rows as $r) {
                $cat = (string)$r["category"];
                $isVideo = stripos($cat, "video") !== false || stripos($cat, "motion") !== false;
                $upd->execute([
                    ":m"  => $isVideo ? "video" : "graphic",
                    ":s"  => $isVideo ? "Marketing Videos" : "Posters",
                    ":k"  => $isVideo ? "video" : "gallery",
                    ":id" => (int)$r["id"],
                ]);
            }
        }

        // --- v3: project image gallery (multiple images per graphic project) ---
        if (!self::tableExists("project_images")) {
            $autoInc = $isMysql ? "INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY"
                                : "INTEGER PRIMARY KEY AUTOINCREMENT";
            $ts   = "DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP";
            $intz = $isMysql ? "INT NOT NULL DEFAULT 0" : "INTEGER NOT NULL DEFAULT 0";
            $eng  = $isMysql ? "ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci" : "";
            $pdo->exec("CREATE TABLE project_images (
                id $autoInc,
                project_id INT NOT NULL,
                filename VARCHAR(255) NOT NULL,
                alt_text VARCHAR(255) NULL,
                sort_order $intz,
                created_at $ts
            ) $eng");
        }

        // --- v4: trusted clients ---
        if (!self::tableExists("clients")) {
            $autoInc = $isMysql ? "INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY"
                                : "INTEGER PRIMARY KEY AUTOINCREMENT";
            $ts      = "DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP";
            $intz    = $isMysql ? "INT NOT NULL DEFAULT 0"  : "INTEGER NOT NULL DEFAULT 0";
            $tinyint = $isMysql ? "TINYINT(1) NOT NULL DEFAULT 1" : "INTEGER NOT NULL DEFAULT 1";
            $eng     = $isMysql ? "ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci" : "";
            $pdo->exec("CREATE TABLE clients (
                id $autoInc,
                name VARCHAR(180) NOT NULL,
                logo VARCHAR(500) NULL,
                link_url VARCHAR(500) NULL,
                sort_order $intz,
                is_visible $tinyint,
                created_at $ts
            ) $eng");

            // Seed a starter set of demo clients (logos hot-linked from Clearbit Logo API).
            $seed = [
                ["Spotify",    "https://logo.clearbit.com/spotify.com",     "https://spotify.com",    10],
                ["Netflix",    "https://logo.clearbit.com/netflix.com",     "https://netflix.com",    20],
                ["Adobe",      "https://logo.clearbit.com/adobe.com",       "https://adobe.com",      30],
                ["Airbnb",     "https://logo.clearbit.com/airbnb.com",      "https://airbnb.com",     40],
                ["Stripe",     "https://logo.clearbit.com/stripe.com",      "https://stripe.com",     50],
                ["Notion",     "https://logo.clearbit.com/notion.so",       "https://notion.so",      60],
                ["Figma",      "https://logo.clearbit.com/figma.com",       "https://figma.com",      70],
                ["Shopify",    "https://logo.clearbit.com/shopify.com",     "https://shopify.com",    80],
            ];
            $stmt = $pdo->prepare(
                "INSERT INTO clients (name, logo, link_url, sort_order, is_visible) VALUES (?,?,?,?,1)"
            );
            foreach ($seed as $row) { $stmt->execute($row); }
        }

        // --- v5: rich demo dataset for every Project sub-category (one-shot) ---
        self::seedDemoProjectsOnce();

        // --- v6: extended admin user profile (full name, avatar, status, role) ---
        if (!self::columnExists("admin_users", "full_name")) {
            $pdo->exec("ALTER TABLE admin_users ADD COLUMN full_name VARCHAR(180) NOT NULL DEFAULT ''");
            // Backfill: use username as a reasonable starting full name.
            $pdo->exec("UPDATE admin_users SET full_name = username WHERE full_name = ''");
        }
        if (!self::columnExists("admin_users", "profile_pic")) {
            $pdo->exec("ALTER TABLE admin_users ADD COLUMN profile_pic VARCHAR(255) NULL");
        }
        if (!self::columnExists("admin_users", "is_active")) {
            $tinyint = $isMysql ? "TINYINT(1) NOT NULL DEFAULT 1" : "INTEGER NOT NULL DEFAULT 1";
            $pdo->exec("ALTER TABLE admin_users ADD COLUMN is_active $tinyint");
        }
        if (!self::columnExists("admin_users", "role")) {
            $pdo->exec("ALTER TABLE admin_users ADD COLUMN role VARCHAR(20) NOT NULL DEFAULT 'sub'");
            // Promote the first/oldest admin to the main role so user management
            // is accessible right after the upgrade.
            $pdo->exec("UPDATE admin_users SET role = 'main' WHERE id = (SELECT id FROM admin_users ORDER BY id ASC LIMIT 1)");
        }

        // --- v7: section toggles ---
        if (!self::tableExists("site_sections")) {
            $autoInc = $isMysql ? "INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY"
                                : "INTEGER PRIMARY KEY AUTOINCREMENT";
            $ts      = "DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP";
            $tinyint = $isMysql ? "TINYINT(1) NOT NULL DEFAULT 1" : "INTEGER NOT NULL DEFAULT 1";
            $intz    = $isMysql ? "INT NOT NULL DEFAULT 0" : "INTEGER NOT NULL DEFAULT 0";
            $eng     = $isMysql ? "ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci" : "";
            $pdo->exec("CREATE TABLE site_sections (
                id $autoInc,
                `key` VARCHAR(40) NOT NULL UNIQUE,
                label VARCHAR(80) NOT NULL,
                icon VARCHAR(40) NULL,
                is_visible $tinyint,
                sort_order $intz,
                updated_at $ts
            ) $eng");

            $sections = [
                ["hero",      "Hero",            "fa-star",            10],
                ["about",     "About",           "fa-user-pen",        20],
                ["skills",    "Skills",          "fa-layer-group",     30],
                ["projects",  "Projects",        "fa-briefcase",       40],
                ["education", "Education",       "fa-graduation-cap",  50],
                ["reviews",   "Reviews",         "fa-star-half-stroke",60],
                ["clients",   "Trusted Clients", "fa-handshake",       70],
                ["contact",   "Contact",         "fa-envelope",        80],
            ];
            $stmt = $pdo->prepare(
                "INSERT INTO site_sections (`key`, label, icon, is_visible, sort_order)
                 VALUES (?, ?, ?, 1, ?)"
            );
            foreach ($sections as $s) { $stmt->execute($s); }
        }

        // --- v8: header / footer menu items ---
        if (!self::tableExists("menu_items")) {
            $autoInc = $isMysql ? "INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY"
                                : "INTEGER PRIMARY KEY AUTOINCREMENT";
            $ts      = "DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP";
            $tinyint = $isMysql ? "TINYINT(1) NOT NULL DEFAULT 1" : "INTEGER NOT NULL DEFAULT 1";
            $intz    = $isMysql ? "INT NOT NULL DEFAULT 0" : "INTEGER NOT NULL DEFAULT 0";
            $eng     = $isMysql ? "ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci" : "";
            $pdo->exec("CREATE TABLE menu_items (
                id $autoInc,
                location VARCHAR(20) NOT NULL,
                label VARCHAR(80) NOT NULL,
                href VARCHAR(255) NOT NULL,
                is_visible $tinyint,
                sort_order $intz,
                updated_at $ts
            ) $eng");

            $items = [
                ["header", "Home",      "#hero",      1, 10],
                ["header", "About",     "#about",     1, 20],
                ["header", "Skills",    "#skills",    1, 30],
                ["header", "Work",      "#projects",  1, 40],
                ["header", "Education", "#education", 1, 50],
                ["header", "Reviews",   "#reviews",   1, 60],
                ["header", "Contact",   "#contact",   1, 70],

                ["footer", "About",     "#about",     1, 10],
                ["footer", "Skills",    "#skills",    1, 20],
                ["footer", "Work",      "#projects",  1, 30],
                ["footer", "Education", "#education", 1, 40],
                ["footer", "Contact",   "#contact",   1, 50],
            ];
            $stmt = $pdo->prepare(
                "INSERT INTO menu_items (location, label, href, is_visible, sort_order)
                 VALUES (?,?,?,?,?)"
            );
            foreach ($items as $i) { $stmt->execute($i); }
        }

        // --- v9: visitor analytics (unique per IP per day) ---
        if (!self::tableExists("visitor_log")) {
            $autoInc = $isMysql ? "INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY"
                                : "INTEGER PRIMARY KEY AUTOINCREMENT";
            $ts      = "DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP";
            $eng     = $isMysql ? "ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci" : "";
            $pdo->exec("CREATE TABLE visitor_log (
                id $autoInc,
                ip_hash VARCHAR(64) NOT NULL,
                day_key VARCHAR(10) NOT NULL,
                user_agent VARCHAR(500) NULL,
                page VARCHAR(255) NULL,
                visited_at $ts
            ) $eng");
            // One unique-visitor row per (ip_hash, day_key).
            $pdo->exec("CREATE UNIQUE INDEX ux_visitor_ip_day ON visitor_log (ip_hash, day_key)");
            $pdo->exec("CREATE INDEX ix_visitor_visited_at ON visitor_log (visited_at)");
        }

        // --- v10: backfill missing video URLs on legacy video projects (one-shot) ---
        // Legacy seed (v1) inserted 8 demo projects with no video_url. v2 marked the
        // ones titled with "Video" as media_kind=video, but they remain unplayable
        // because video_url is empty — leading to "No video URL set" in the player.
        // This migration fills any video project that still has an empty video_url
        // with a curated public YouTube placeholder so the modal player works out of the box.
        $marker = $pdo->query("SELECT value FROM settings WHERE `key`='video_url_backfill_v10' LIMIT 1")->fetchColumn();
        if (!$marker) {
            $fallbacks = [
                "https://www.youtube.com/watch?v=ScMzIvxBSi4",
                "https://www.youtube.com/watch?v=aqz-KE-bpKQ",
                "https://www.youtube.com/watch?v=tgbNymZ7vqY",
                "https://www.youtube.com/watch?v=L_jWHffIx5E",
                "https://www.youtube.com/watch?v=fNFzfwLM72c",
                "https://www.youtube.com/watch?v=kJQP7kiw5Fk",
            ];
            $rows = $pdo->query(
                "SELECT id FROM projects
                 WHERE media_kind='video' AND (video_url IS NULL OR video_url='')
                 ORDER BY id ASC"
            )->fetchAll(\PDO::FETCH_COLUMN);
            $upd = $pdo->prepare("UPDATE projects SET video_url=:u WHERE id=:id");
            foreach ($rows as $i => $pid) {
                $upd->execute([
                    ":u"  => $fallbacks[$i % count($fallbacks)],
                    ":id" => (int)$pid,
                ]);
            }
            $pdo->prepare("INSERT INTO settings (`key`, value) VALUES ('video_url_backfill_v10', '1')")->execute();
        }

        // --- v11: refresh hero to point at the bundled premium avatar (one-shot) ---
        $marker11 = $pdo->query("SELECT value FROM settings WHERE `key`='hero_avatar_refresh_v11' LIMIT 1")->fetchColumn();
        if (!$marker11) {
            // Clearing the avatar field makes hero.php fall back to /assets/images/hero-avatar.png,
            // which is the new high-quality 3D portrait shipped with the project.
            $pdo->exec("UPDATE hero_content SET avatar=''");
            $pdo->prepare("INSERT INTO settings (`key`, value) VALUES ('hero_avatar_refresh_v11', '1')")->execute();
        }

        // --- v12: extend hero_content for the revamped premium hero layout ---
        // The new hero added: eyebrow line, lede paragraph, secondary CTA, three
        // editable stat tiles, a scroll cue label, and a toggle for the background
        // orbs. Each column added one-at-a-time so SQLite + MySQL both stay happy.
        $heroCols = [
            "eyebrow"            => "VARCHAR(80) NULL",
            "lede"               => "TEXT NULL",
            "cta2_label"         => "VARCHAR(80) NULL",
            "cta2_link"          => "VARCHAR(255) NULL",
            "stats_enabled"      => "INTEGER NOT NULL DEFAULT 1",
            "stat1_value"        => "VARCHAR(40) NULL",
            "stat1_label"        => "VARCHAR(80) NULL",
            "stat2_value"        => "VARCHAR(40) NULL",
            "stat2_label"        => "VARCHAR(80) NULL",
            "stat3_value"        => "VARCHAR(40) NULL",
            "stat3_label"        => "VARCHAR(80) NULL",
            "scroll_cue_enabled" => "INTEGER NOT NULL DEFAULT 1",
            "scroll_cue_label"   => "VARCHAR(40) NULL",
            "show_orbs"          => "INTEGER NOT NULL DEFAULT 1",
        ];
        foreach ($heroCols as $col => $def) {
            if (!self::columnExists("hero_content", $col)) {
                $pdo->exec("ALTER TABLE hero_content ADD COLUMN $col $def");
            }
        }
        // Backfill defaults that match the values the public hero currently renders,
        // so existing installs see the same content after the migration.
        $marker12 = $pdo->query("SELECT value FROM settings WHERE `key`='hero_extend_v12' LIMIT 1")->fetchColumn();
        if (!$marker12) {
            $pdo->exec("UPDATE hero_content SET
                eyebrow            = COALESCE(NULLIF(eyebrow,''),            'Hi, I''m'),
                lede               = COALESCE(NULLIF(lede,''),               'Crafting bold visual stories — from cinematic edits to scroll-stopping graphic design. Premium quality, on-brief, on-time.'),
                cta2_label         = COALESCE(NULLIF(cta2_label,''),         'Download CV'),
                cta2_link          = COALESCE(NULLIF(cta2_link,''),          '/cv.php'),
                stat1_value        = COALESCE(NULLIF(stat1_value,''),        '6+'),
                stat1_label        = COALESCE(NULLIF(stat1_label,''),        'Years Experience'),
                stat2_value        = COALESCE(NULLIF(stat2_value,''),        '20+'),
                stat2_label        = COALESCE(NULLIF(stat2_label,''),        'Projects Delivered'),
                stat3_value        = COALESCE(NULLIF(stat3_value,''),        '8+'),
                stat3_label        = COALESCE(NULLIF(stat3_label,''),        'Happy Clients'),
                scroll_cue_label   = COALESCE(NULLIF(scroll_cue_label,''),   'Scroll')
            ");
            $pdo->prepare("INSERT INTO settings (`key`, value) VALUES ('hero_extend_v12', '1')")->execute();
        }

        // --- v13: editable UI text catalogue ---
        // Move every previously-hardcoded section title, eyebrow, button label
        // and contact-form label into the settings table so the admin can edit
        // them from /admin/uitext.php. Existing installs are seeded with the
        // current visible defaults so the public site looks identical.
        $marker13 = $pdo->query("SELECT value FROM settings WHERE `key`='ui_text_catalogue_v13' LIMIT 1")->fetchColumn();
        if (!$marker13) {
            $uiDefaults = self::uiTextDefaults();
            $insert = $pdo->prepare(
                "INSERT INTO settings (`key`, value) VALUES (:k, :v)"
            );
            $select = $pdo->prepare("SELECT 1 FROM settings WHERE `key` = :k LIMIT 1");
            foreach ($uiDefaults as $k => $v) {
                $select->execute([":k" => $k]);
                if (!$select->fetchColumn()) {
                    $insert->execute([":k" => $k, ":v" => $v]);
                }
            }
            $pdo->prepare("INSERT INTO settings (`key`, value) VALUES ('ui_text_catalogue_v13', '1')")->execute();
        }

        // --- v14: visitor_log gains a per-browser opaque token column ---
        // The original tracker only deduped on (ip_hash, day_key), which over-
        // counted real humans behind reverse proxies whose source IP rotates.
        // We add a `visitor_token` column (set from a sliding HttpOnly cookie)
        // and switch the unique index to (visitor_token, day_key). Legacy rows
        // are backfilled with their ip_hash so historical analytics stay valid.
        if (self::tableExists("visitor_log") && !self::columnExists("visitor_log", "visitor_token")) {
            $pdo->exec("ALTER TABLE visitor_log ADD COLUMN visitor_token VARCHAR(64) NULL");
            $pdo->exec("UPDATE visitor_log SET visitor_token = ip_hash WHERE visitor_token IS NULL OR visitor_token = ''");
            // Drop the old (ip_hash, day_key) unique index, add the new one.
            try {
                if ($isMysql) {
                    $pdo->exec("ALTER TABLE visitor_log DROP INDEX ux_visitor_ip_day");
                } else {
                    $pdo->exec("DROP INDEX IF EXISTS ux_visitor_ip_day");
                }
            } catch (\Throwable $e) { /* ignore — index may not exist yet */ }
            try {
                $pdo->exec("CREATE UNIQUE INDEX ux_visitor_token_day ON visitor_log (visitor_token, day_key)");
            } catch (\Throwable $e) { /* ignore — duplicate index race */ }
        }

        // --- v15: self-hosted project videos with poster thumbnails ---
        // Adds two columns so a project can ship its own .mp4/.webm file plus
        // an explicit poster image, instead of being limited to YouTube/Vimeo
        // iframe embeds. The legacy `video_url` column is preserved so YouTube
        // remains supported as a graceful fallback when no local file exists.
        if (!self::columnExists("projects", "video_file")) {
            $pdo->exec("ALTER TABLE projects ADD COLUMN video_file VARCHAR(255) NULL");
        }
        if (!self::columnExists("projects", "video_poster")) {
            $pdo->exec("ALTER TABLE projects ADD COLUMN video_poster VARCHAR(255) NULL");
        }

        // --- v16: gallery media types + seed all existing site assets ---
        // Adds media_type and video_path to gallery_images so the gallery can
        // showcase images, logos, videos, SVG icons and site assets — not just
        // uploaded photos. Also creates richer starter categories and populates
        // them with every bundled file that ships with the portfolio template.
        if (!self::columnExists("gallery_images", "media_type")) {
            $pdo->exec("ALTER TABLE gallery_images ADD COLUMN media_type VARCHAR(20) NOT NULL DEFAULT 'image'");
        }
        if (!self::columnExists("gallery_images", "video_path")) {
            $pdo->exec("ALTER TABLE gallery_images ADD COLUMN video_path VARCHAR(500) NULL");
        }

        $marker = $pdo->query("SELECT value FROM settings WHERE `key`='gallery_seed_v16' LIMIT 1")->fetchColumn();
        if (!$marker) {
            // Ensure we have all the desired categories (idempotent by name)
            $catMap = [];
            foreach ($pdo->query("SELECT id, name FROM gallery_categories")->fetchAll(\PDO::FETCH_ASSOC) as $r) {
                $catMap[strtolower($r["name"])] = (int)$r["id"];
            }

            $wantedCats = [
                // [name, slug, parent_name_or_null, sort]
                ["Images",      "images",      null,          5],
                ["Logos",       "logos",       null,         10],
                ["Videos",      "videos",      null,         15],
                ["Icons & SVGs","icons-svgs",  null,         20],
                ["Brand Assets","brand-assets",null,         25],
                ["Photography", "photography", null,         30],
                // sub-cats
                ["Posters",     "posters",     "brand-assets",10],
                ["Project Screenshots","project-screenshots","images",10],
                ["Site Assets", "site-assets", "images",     20],
            ];

            $ins = $pdo->prepare("INSERT INTO gallery_categories (name, slug, parent_id, sort_order) VALUES (?,?,?,?)");
            $upd = $pdo->prepare("UPDATE gallery_categories SET sort_order=? WHERE id=?");
            foreach ($wantedCats as [$name, $slug, $parentName, $sort]) {
                $key = strtolower($name);
                if (!isset($catMap[$key])) {
                    $parentId = $parentName ? ($catMap[strtolower($parentName)] ?? null) : null;
                    $ins->execute([$name, $slug, $parentId, $sort]);
                    $catMap[$key] = (int)$pdo->lastInsertId();
                } else {
                    $upd->execute([$sort, $catMap[$key]]);
                }
            }

            // Seed gallery_images with every bundled asset file
            // Project screenshots → "project screenshots" cat
            $projCat    = $catMap["project screenshots"] ?? $catMap["images"] ?? null;
            $siteCat    = $catMap["site assets"]         ?? $catMap["images"] ?? null;
            $logoCat    = $catMap["logos"]               ?? null;
            $iconCat    = $catMap["icons & svgs"]        ?? null;

            $imgIns = $pdo->prepare(
                "INSERT INTO gallery_images (category_id, filename, title, alt_text, sort_order, media_type)
                 VALUES (?,?,?,?,?,?)"
            );

            if ($projCat) {
                $projFiles = [
                    ["project-1.png","Project Screenshot 1","Graphic design project 1",10],
                    ["project-2.png","Project Screenshot 2","Graphic design project 2",20],
                    ["project-3.png","Project Screenshot 3","Graphic design project 3",30],
                    ["project-4.png","Project Screenshot 4","Graphic design project 4",40],
                    ["project-5.png","Project Screenshot 5","Graphic design project 5",50],
                    ["project-6.png","Project Screenshot 6","Graphic design project 6",60],
                    ["project-7.png","Project Screenshot 7","Graphic design project 7",70],
                    ["project-8.png","Project Screenshot 8","Graphic design project 8",80],
                ];
                foreach ($projFiles as [$fn, $title, $alt, $sort]) {
                    $imgIns->execute([$projCat, $fn, $title, $alt, $sort, "image"]);
                }
            }

            if ($siteCat) {
                $siteFiles = [
                    ["avatar.png",         "Profile Avatar",        "Portfolio owner avatar",        10],
                    ["hero-avatar.png",     "Hero Avatar",           "Hero section avatar",           20],
                    ["hero-avatar-3d.png",  "Hero Avatar 3D",        "3D stylised hero avatar",       30],
                ];
                foreach ($siteFiles as [$fn, $title, $alt, $sort]) {
                    $imgIns->execute([$siteCat, $fn, $title, $alt, $sort, "image"]);
                }
            }

            if ($iconCat) {
                $iconFiles = [
                    ["favicon.svg","Site Favicon / Logo SVG","Portfolio favicon and logo SVG",10],
                ];
                foreach ($iconFiles as [$fn, $title, $alt, $sort]) {
                    $imgIns->execute([$iconCat, $fn, $title, $alt, $sort, "icon"]);
                }
            }

            $pdo->prepare("INSERT INTO settings (`key`, value) VALUES ('gallery_seed_v16', '1')")->execute();
        }

        // --- v17: link gallery_images to projects + sync video projects into gallery ---
        // Adds a nullable project_id FK column so gallery entries can be
        // auto-created/updated/deleted alongside their parent project row.
        // Then back-fills every existing video project into the "Videos" gallery.
        if (!self::columnExists("gallery_images", "project_id")) {
            $pdo->exec("ALTER TABLE gallery_images ADD COLUMN project_id INT NULL");
        }

        $marker17 = $pdo->query("SELECT value FROM settings WHERE `key`='gallery_video_sync_v17' LIMIT 1")->fetchColumn();
        if (!$marker17) {
            // Locate the "Videos" gallery category (created in v16).
            $videoCatId = (int)$pdo->query(
                "SELECT id FROM gallery_categories WHERE name='Videos' LIMIT 1"
            )->fetchColumn();

            if ($videoCatId) {
                $videoProjects = $pdo->query(
                    "SELECT id, title, video_url, video_file FROM projects
                      WHERE media_kind='video'
                        AND (video_url IS NOT NULL AND video_url != '')
                      ORDER BY sort_order, id"
                )->fetchAll(\PDO::FETCH_ASSOC);

                $checkExisting = $pdo->prepare(
                    "SELECT id FROM gallery_images WHERE project_id=:pid AND category_id=:cid LIMIT 1"
                );
                $vidIns = $pdo->prepare(
                    "INSERT INTO gallery_images
                       (category_id, filename, title, alt_text, sort_order, media_type, video_path, project_id)
                     VALUES (?,?,?,?,?,?,?,?)"
                );

                foreach ($videoProjects as $i => $vp) {
                    $checkExisting->execute([":pid" => $vp["id"], ":cid" => $videoCatId]);
                    if ($checkExisting->fetchColumn()) continue; // already synced
                    $fn    = !empty($vp["video_file"]) ? (string)$vp["video_file"] : "";
                    $vpath = !empty($vp["video_file"])
                        ? "/uploads/videos/" . $vp["video_file"]
                        : (string)($vp["video_url"] ?? "");
                    $vidIns->execute([
                        $videoCatId,
                        $fn,
                        (string)$vp["title"],
                        "Auto-synced video project",
                        ($i + 1) * 10,
                        "video",
                        $vpath,
                        (int)$vp["id"],
                    ]);
                }
            }

            $pdo->prepare("INSERT INTO settings (`key`, value) VALUES ('gallery_video_sync_v17', '1')")->execute();
        }
    }

    /**
     * Default values for the editable UI text catalogue (v13).
     * Keys are the canonical setting names referenced by section files via
     * Settings::get(). Each value mirrors the previously-hardcoded label so
     * existing sites look identical after the migration.
     */
    public static function uiTextDefaults(): array
    {
        return [
            // ── Header / footer chrome ──
            "header_hire_label"   => "Hire Me",
            "header_hire_link"    => "#contact",
            "footer_copyright"    => "All rights reserved.",

            // ── About section ──
            "about_title"         => "About Me",
            "about_card_title"    => "Core Expertise",

            // ── Skills section ──
            "skills_title"        => "Skills & Toolkit",
            "skills_creative"     => "Creative Skills",
            "skills_software"     => "Software Toolkit",

            // ── Projects section ──
            "projects_eyebrow"    => "Portfolio",
            "projects_title"      => "Selected Work",
            "projects_subtitle"   => "Curated projects across motion and graphic design — tap any card to dive in.",
            "projects_filter_all"     => "All Work",
            "projects_filter_video"   => "Video & Motion",
            "projects_filter_graphic" => "Graphic Design",
            "projects_empty_label"    => "No projects in this category yet — try another tab.",
            "projects_loadmore_label" => "Load More",

            // ── Education section ──
            "education_title"     => "Education",

            // ── Reviews section ──
            "reviews_title"       => "Client Reviews",
            "reviews_subtitle"    => "What people say about working with me.",

            // ── Trusted Clients section ──
            "clients_title"       => "Trusted Clients",
            "clients_subtitle"    => "Brands and creators I've had the privilege to work with.",

            // ── Contact section ──
            "contact_title"       => "Let's work together.",
            "contact_subtitle"    => "Have a project in mind? Looking for a versatile creative to join your team? Drop me a message and let's craft something amazing.",
            "contact_label_name"     => "Name",
            "contact_label_email"    => "Email",
            "contact_label_subject"  => "Subject",
            "contact_label_message"  => "Message",
            "contact_placeholder_name"    => "John Doe",
            "contact_placeholder_email"   => "john@example.com",
            "contact_placeholder_subject" => "Project Inquiry",
            "contact_placeholder_message" => "Tell me about your project...",
            "contact_submit_label"   => "Send Message",
        ];
    }

    /**
     * Insert one or two showcase projects into every sub-category that's empty.
     * Idempotent: each (main, sub) pair only seeded if there are no existing projects there.
     * Runs at most once per sub-category — safe to call on every boot.
     */
    private static function seedDemoProjectsOnce(): void
    {
        $pdo = Database::pdo();
        $flag = "demo_dataset_v2_seeded";
        try {
            $val = $pdo->prepare("SELECT value FROM settings WHERE `key` = :k");
            $val->execute([":k" => $flag]);
            if ($val->fetchColumn()) return; // already done
        } catch (\Throwable $e) {
            return; // settings table not ready
        }

        // Working public YouTube IDs (royalty-free / official content).
        $youtube = [
            "ScMzIvxBSi4", "aqz-KE-bpKQ", "tgbNymZ7vqY", "kJQP7kiw5Fk",
            "9bZkp7q19f0", "fNFzfwLM72c", "L_jWHffIx5E", "OPf0YbXqDm0",
            "RgKAFK5djSk", "JGwWNGJdvx8", "hT_nvWreIhg", "60ItHLz5WEA",
        ];
        // Bundled placeholder images that ship with the project (always available
        // even when the sandbox can't reach external image hosts).
        $bundled  = ["project-1.png","project-2.png","project-3.png","project-4.png",
                     "project-5.png","project-6.png","project-7.png","project-8.png"];
        $bundledI = 0;
        $img = static function (string $seed) use ($bundled, &$bundledI): string {
            return $bundled[($bundledI++) % count($bundled)];
        };

        // [main, sub, kind, items...]
        $demos = [
            // ── Video & Motion ──
            ["video", "Product Ads", "video", [
                ["Glow Skincare Launch Ad",  $youtube[0]],
                ["Aurora Headphones — Hero Spot", $youtube[1]],
            ]],
            ["video", "Educational", "video", [
                ["How Lenses Bend Light",    $youtube[2]],
            ]],
            ["video", "Business Promotion", "video", [
                ["NorthStar Agency Reel",    $youtube[3]],
            ]],
            ["video", "Wedding/Pre-wedding", "video", [
                ["Sara & Arman — Cinematic Pre-Wedding", $youtube[4]],
            ]],
            ["video", "Documentary", "video", [
                ["Voices of the Old Town",   $youtube[5]],
            ]],
            ["video", "Explainer Videos", "video", [
                ["How Our SaaS Works in 60s", $youtube[6]],
            ]],
            ["video", "Podcast", "video", [
                ["Founders Talk · Episode 12", $youtube[7]],
            ]],
            ["video", "Marketing Videos", "video", [
                ["Festival Promo 2026",      $youtube[8]],
            ]],

            // ── Graphic Design ──
            ["graphic", "Photo-cards (FB/Web)", "gallery", [
                ["K-Pop Photocard Collection", "graphic-photocards-1", ["graphic-photocards-2","graphic-photocards-3"]],
            ]],
            ["graphic", "Logos", "gallery", [
                ["Aurora Brand Identity",      "graphic-logos-1",      ["graphic-logos-2","graphic-logos-3"]],
            ]],
            ["graphic", "Banners", "gallery", [
                ["Summer Sale Banner Pack",    "graphic-banners-1",    ["graphic-banners-2"]],
            ]],
            ["graphic", "Posters", "gallery", [
                ["Neon Energy Drink Poster",   "graphic-posters-1",    ["graphic-posters-2"]],
            ]],
            ["graphic", "Thumbnails", "gallery", [
                ["YouTube Thumbnail Series",   "graphic-thumbs-1",     ["graphic-thumbs-2","graphic-thumbs-3"]],
            ]],
        ];

        $insertProject = $pdo->prepare(
            "INSERT INTO projects
              (title, category, image, main_category, sub_category, media_kind,
               video_url, software, skills_used, description, sort_order, is_published)
             VALUES (:t, :c, :i, :mc, :sc, :mk, :vu, :sw, :sk, :de, :s, 1)"
        );
        $insertImg = $pdo->prepare(
            "INSERT INTO project_images (project_id, filename, sort_order) VALUES (:p, :f, :s)"
        );
        $exists = $pdo->prepare(
            "SELECT COUNT(*) FROM projects WHERE main_category = :mc AND sub_category = :sc"
        );

        $sortBase = 1000;
        foreach ($demos as $group) {
            [$main, $sub, $kind, $items] = $group;
            $exists->execute([":mc" => $main, ":sc" => $sub]);
            if ((int)$exists->fetchColumn() > 0) continue; // already populated

            foreach ($items as $i => $item) {
                $title = $item[0];
                $cover = $img(preg_replace('/[^a-z0-9]/i', '-', strtolower($title)));

                $insertProject->execute([
                    ":t"  => $title,
                    ":c"  => $main === "video" ? "Video Editing" : "Graphic Design",
                    ":i"  => $cover,
                    ":mc" => $main,
                    ":sc" => $sub,
                    ":mk" => $kind,
                    ":vu" => $kind === "video" ? "https://www.youtube.com/watch?v={$item[1]}" : "",
                    ":sw" => $main === "video" ? "Premiere Pro,After Effects,DaVinci Resolve" : "Photoshop,Illustrator,Figma",
                    ":sk" => $main === "video" ? "Color grading · Sound mix · Motion" : "Layout · Typography · Composition",
                    ":de" => "Demo project for the {$sub} sub-category.",
                    ":s"  => $sortBase++,
                ]);
                $newId = (int)$pdo->lastInsertId();

                // Extra gallery images for graphic items
                if ($kind === "gallery" && !empty($item[2])) {
                    foreach ($item[2] as $j => $seed) {
                        $insertImg->execute([
                            ":p" => $newId,
                            ":f" => $img($seed),
                            ":s" => ($j + 1) * 10,
                        ]);
                    }
                }
            }
        }

        // Mark as done so we never run again.
        $pdo->prepare("INSERT INTO settings (`key`, value) VALUES (:k, '1')")
            ->execute([":k" => $flag]);
    }

    private static function columnExists(string $table, string $column): bool
    {
        $pdo = Database::pdo();
        try {
            if (Database::isMysql()) {
                $stmt = $pdo->prepare(
                    "SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
                     WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :t AND COLUMN_NAME = :c"
                );
                $stmt->execute([":t" => $table, ":c" => $column]);
                return (int)$stmt->fetchColumn() > 0;
            }
            // SQLite
            $rows = $pdo->query("PRAGMA table_info(" . $table . ")")->fetchAll();
            foreach ($rows as $r) {
                if (strcasecmp((string)$r["name"], $column) === 0) return true;
            }
            return false;
        } catch (\Throwable $e) {
            return false;
        }
    }

    private static function tableExists(string $table): bool
    {
        $pdo = Database::pdo();
        try {
            if (Database::isMysql()) {
                $stmt = $pdo->prepare(
                    "SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES
                     WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :t"
                );
                $stmt->execute([":t" => $table]);
                return (int)$stmt->fetchColumn() > 0;
            }
            $stmt = $pdo->prepare("SELECT name FROM sqlite_master WHERE type='table' AND name = :t");
            $stmt->execute([":t" => $table]);
            return (bool)$stmt->fetchColumn();
        } catch (\Throwable $e) {
            return false;
        }
    }

    private static function seed(array $config): void
    {
        $pdo = Database::pdo();

        // ---- Default admin ----
        $hash = password_hash($config["admin"]["default_password"], PASSWORD_BCRYPT);
        $pdo->prepare("INSERT INTO admin_users (username, email, password_hash) VALUES (:u,:e,:p)")
            ->execute([
                ":u" => $config["admin"]["default_username"],
                ":e" => $config["admin"]["default_email"],
                ":p" => $hash,
            ]);

        // ---- Site settings ----
        $defaults = [
            "site_name"        => "Anik Sen",
            "tagline"          => "Crafting Visual Stories Since 2020.",
            "email"            => "hello@aniksen.com",
            "location"         => "Bangladesh",
            "logo"             => "",
            "favicon"          => "",
            "social_facebook"  => "https://facebook.com/",
            "social_linkedin"  => "https://linkedin.com/",
            "social_behance"   => "https://behance.net/",
            "active_cv"        => "",
            "footer_about"     => "Visual storyteller crafting brands and films.",
        ];
        $stmt = $pdo->prepare("INSERT INTO settings (`key`, value) VALUES (:k,:v)");
        foreach ($defaults as $k => $v) {
            $stmt->execute([":k" => $k, ":v" => $v]);
        }

        // ---- Hero ----
        $pdo->prepare("INSERT INTO hero_content
            (badge_text, name, phrases, cta_label, cta_link, avatar, chip_title, chip_sub)
            VALUES (:b,:n,:p,:cl,:cu,:av,:ct,:cs)")->execute([
            ":b"  => "Available for freelance work",
            ":n"  => "Anik Sen",
            ":p"  => json_encode([
                "a Graphic Design Specialist",
                "a Video Editing Expert",
                "a Content Creator",
            ]),
            ":cl" => "View Work",
            ":cu" => "#projects",
            ":av" => "",
            ":ct" => "Graphic Designer",
            ":cs" => "& Video Editor",
        ]);

        // ---- About ----
        $bio = implode("\n\n", [
            "Hello! I'm Anik Sen, a passionate creative professional based in Bangladesh. Since 2020, I've been on a mission to transform ideas into captivating visual experiences that leave a lasting impact.",
            "My journey started with a fascination for colors and composition, leading me deep into the world of graphic design. I specialize in crafting ad promotions, designing educational graphics, and building social media branding materials like distinct photocards and custom stickers.",
            "But static imagery was only half the story. I expanded my toolkit into motion, diving into video editing to bring narratives to life. From cinematic wedding video editing to emotional documentary work, I love finding the rhythm and pacing that makes a story resonate.",
        ]);
        $pdo->prepare("INSERT INTO about_content (bio, profile_image) VALUES (:b, :p)")
            ->execute([":b" => $bio, ":p" => ""]);

        // ---- Expertise ----
        $expertise = [
            ["image",  "Ad Promotions",      "Eye-catching visuals designed to convert.", 10],
            ["palette","Branding Materials", "Photocards, stickers, and brand assets.",   20],
            ["video",  "Video Editing",      "Weddings, documentaries, and promos.",      30],
        ];
        $stmt = $pdo->prepare("INSERT INTO expertise_items (icon, title, description, sort_order) VALUES (?,?,?,?)");
        foreach ($expertise as $e) { $stmt->execute($e); }

        // ---- Projects (legacy seed; new fields backfilled by incremental migration) ----
        $projects = [
            ["Neon Energy Drink Ad", "Graphic Design", "project-1.png", 10],
            ["K-Pop Photocard Set",  "Graphic Design", "project-2.png", 20],
            ["Golden Hour Wedding",  "Video Editing",  "project-3.png", 30],
            ["Street Art Stickers",  "Graphic Design", "project-4.png", 40],
            ["Tech Product Launch",  "Video Editing",  "project-5.png", 50],
            ["Space Infographic",    "Graphic Design", "project-6.png", 60],
            ["Urban Documentary",    "Video Editing",  "project-7.png", 70],
            ["Festival Promo",       "Video Editing",  "project-8.png", 80],
        ];
        $stmt = $pdo->prepare(
            "INSERT INTO projects (title, category, image, sort_order, is_published) VALUES (?,?,?,?,1)"
        );
        foreach ($projects as $p) { $stmt->execute($p); }

        // ---- Skills (creative + software) ----
        $creative = ["Branding","Product Marketing","Ad Graphics","Wedding Video Editing","Documentaries"];
        $stmt = $pdo->prepare(
            "INSERT INTO skills (name, kind, sort_order) VALUES (?, 'creative', ?)"
        );
        foreach ($creative as $i => $c) { $stmt->execute([$c, ($i + 1) * 10]); }

        $software = [
            ["Adobe Illustrator","Vector Design","Ai","#FF9A00","#330000",10],
            ["Premiere Pro","Video Editing","Pr","#EA77FF","#00005B",20],
            ["After Effects","Motion Graphics","Ae","#D291FF","#00005B",30],
            ["ChatGPT","AI Assistant","GP","#10A37F","#0B2A21",40],
            ["Gemini","AI Assistant","Ge","#8E75B2","#1A0F2C",50],
        ];
        $stmt = $pdo->prepare(
            "INSERT INTO skills (name, kind, tag, letters, color, bg, sort_order) VALUES (?, 'software', ?, ?, ?, ?, ?)"
        );
        foreach ($software as $s) { $stmt->execute($s); }

        // ---- Education ----
        $edu = [
            ["Completed", "HSC in Business Studies", "GPA 5.00",            10],
            ["Present",   "BBA in Accounting",       "Currently Pursuing",  20],
        ];
        $stmt = $pdo->prepare("INSERT INTO education (year, degree, status, sort_order) VALUES (?,?,?,?)");
        foreach ($edu as $e) { $stmt->execute($e); }

        // ---- Reviews ----
        $reviews = [
            ["Sarah Ahmed", "Marketing Director", "Anik's design work elevated our recent ad campaign significantly. The photocard designs were flawless and exactly what we envisioned.", 10],
            ["Rahim & Fariha", "Clients", "He edited our wedding video and captured every emotion perfectly. Cinematic, beautiful pacing, and excellent color grading.", 20],
            ["Tariq Hasan", "Brand Owner", "I needed custom stickers for my brand, and the creativity Anik brought to the table was unmatched. Highly recommended!", 30],
            ["Nadia Islam", "Filmmaker", "A versatile creative who understands both design and motion. The documentary promo he cut for us was gripping.", 40],
        ];
        $stmt = $pdo->prepare("INSERT INTO reviews (author, role, body, sort_order) VALUES (?,?,?,?)");
        foreach ($reviews as $r) { $stmt->execute($r); }

        // ---- Gallery starter categories ----
        $cats = [
            ["Brand Assets","brand-assets",null,10],
            ["Photography","photography",null,20],
            ["Logos","logos",1,10],
            ["Posters","posters",1,20],
        ];
        $stmt = $pdo->prepare("INSERT INTO gallery_categories (name, slug, parent_id, sort_order) VALUES (?,?,?,?)");
        foreach ($cats as $c) { $stmt->execute($c); }
    }
}
