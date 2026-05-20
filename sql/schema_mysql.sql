-- ============================================================
-- Anik Sen Portfolio CMS — MySQL schema
-- For self-hosting on a MySQL server.
-- The application also auto-bootstraps these tables for SQLite.
-- Import:
--   mysql -u root -p anik_portfolio < sql/schema_mysql.sql
-- ============================================================

CREATE DATABASE IF NOT EXISTS `4755283_portfoliodb`
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE `4755283_portfoliodb`;

CREATE TABLE IF NOT EXISTS `settings` (
    `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `key`        VARCHAR(80)  NOT NULL UNIQUE,
    `value`      TEXT         NULL,
    `updated_at` DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `admin_users` (
    `id`            INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `username`      VARCHAR(80)  NOT NULL UNIQUE,
    `email`         VARCHAR(180) NOT NULL UNIQUE,
    `password_hash` VARCHAR(255) NOT NULL,
    `last_login_at` DATETIME     NULL,
    `created_at`    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `hero_content` (
    `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `badge_text` VARCHAR(180) NULL,
    `name`       VARCHAR(120) NOT NULL,
    `phrases`    TEXT         NULL,
    `cta_label`  VARCHAR(80)  NULL,
    `cta_link`   VARCHAR(255) NULL,
    `avatar`     VARCHAR(255) NULL,
    `chip_title` VARCHAR(120) NULL,
    `chip_sub`   VARCHAR(120) NULL,
    `updated_at` DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `about_content` (
    `id`            INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `bio`           TEXT         NOT NULL,
    `profile_image` VARCHAR(255) NULL,
    `updated_at`    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `expertise_items` (
    `id`          INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `icon`        VARCHAR(40)  NOT NULL,
    `title`       VARCHAR(120) NOT NULL,
    `description` VARCHAR(255) NOT NULL,
    `sort_order`  INT          NOT NULL DEFAULT 0,
    `created_at`  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `projects` (
    `id`           INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `title`        VARCHAR(180) NOT NULL,
    `category`     VARCHAR(80)  NOT NULL,
    `image`        VARCHAR(255) NULL,
    `tech_stack`   VARCHAR(255) NULL,
    `description`  TEXT         NULL,
    `project_url`  VARCHAR(255) NULL,
    `sort_order`   INT          NOT NULL DEFAULT 0,
    `is_published` TINYINT(1)   NOT NULL DEFAULT 1,
    `created_at`   DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY `idx_projects_category` (`category`),
    KEY `idx_projects_sort`     (`sort_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `skills` (
    `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `name`       VARCHAR(120) NOT NULL,
    `kind`       VARCHAR(40)  NOT NULL DEFAULT 'creative',
    `tag`        VARCHAR(80)  NULL,
    `letters`    VARCHAR(8)   NULL,
    `color`      VARCHAR(20)  NULL,
    `bg`         VARCHAR(20)  NULL,
    `sort_order` INT          NOT NULL DEFAULT 0,
    `created_at` DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `education` (
    `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `year`       VARCHAR(40)  NOT NULL,
    `degree`     VARCHAR(180) NOT NULL,
    `status`     VARCHAR(120) NOT NULL,
    `sort_order` INT          NOT NULL DEFAULT 0,
    `created_at` DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `reviews` (
    `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `author`     VARCHAR(120) NOT NULL,
    `role`       VARCHAR(120) NOT NULL,
    `body`       TEXT         NOT NULL,
    `sort_order` INT          NOT NULL DEFAULT 0,
    `created_at` DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `gallery_categories` (
    `id`          INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `name`        VARCHAR(120) NOT NULL,
    `parent_id`   INT          NULL,
    `slug`        VARCHAR(140) NOT NULL UNIQUE,
    `description` VARCHAR(255) NULL,
    `sort_order`  INT          NOT NULL DEFAULT 0,
    `created_at`  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY `idx_gallery_cat_parent` (`parent_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `gallery_images` (
    `id`          INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `category_id` INT          NOT NULL,
    `title`       VARCHAR(180) NULL,
    `filename`    VARCHAR(255) NOT NULL,
    `alt_text`    VARCHAR(255) NULL,
    `sort_order`  INT          NOT NULL DEFAULT 0,
    `created_at`  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY `idx_gallery_img_cat` (`category_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `file_library` (
    `id`            INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `title`         VARCHAR(180) NOT NULL,
    `folder`        VARCHAR(120) NOT NULL DEFAULT 'general',
    `filename`      VARCHAR(255) NOT NULL,
    `original_name` VARCHAR(255) NULL,
    `mime`          VARCHAR(120) NULL,
    `size_bytes`    INT          NOT NULL DEFAULT 0,
    `description`   VARCHAR(500) NULL,
    `is_active`     TINYINT(1)   NOT NULL DEFAULT 1,
    `created_at`    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY `idx_file_folder` (`folder`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `messages` (
    `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `name`       VARCHAR(120) NOT NULL,
    `email`      VARCHAR(180) NOT NULL,
    `subject`    VARCHAR(180) NOT NULL,
    `message`    TEXT         NOT NULL,
    `is_read`    TINYINT(1)   NOT NULL DEFAULT 0,
    `created_at` DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY `idx_messages_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
