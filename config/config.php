<?php
/**
 * Central application config.
 * Override any value via environment variables on your host.
 */

declare(strict_types=1);

$root = dirname(__DIR__);

return [
    "app" => [
        "name"      => "Anik Sen Portfolio",
        "base_url"  => getenv("APP_BASE_URL") ?: "",
        "debug"     => filter_var(getenv("APP_DEBUG") ?: "false", FILTER_VALIDATE_BOOL),
        "timezone"  => getenv("APP_TIMEZONE") ?: "Asia/Dhaka",
    ],

    "db" => [
        // sqlite (default, zero-config) | mysql
        "driver"   => getenv("DB_DRIVER") ?: "sqlite",
        "sqlite"   => [
            "path" => getenv("DB_SQLITE_PATH") ?: ($root . "/data/portfolio.sqlite"),
        ],
        "mysql"    => [
            "host"     => getenv("DB_HOST")     ?: "fdb1034.awardspace.net",
            "port"     => getenv("DB_PORT")     ?: "3306",
            "name"     => getenv("DB_NAME")     ?: "4755283_portfoliodb",
            "user"     => getenv("DB_USER")     ?: "4755283_portfoliodb",
            "password" => getenv("DB_PASSWORD") ?: "anik@2626#",
            "charset"  => "utf8mb4",
        ],
    ],

    "paths" => [
        "root"          => $root,
        "public"        => $root,
        "uploads"       => $root . "/uploads",
        "uploads_url"   => "/uploads",
        "image_dir"     => $root . "/uploads/images",
        "image_url"     => "/uploads/images",
        "doc_dir"       => $root . "/uploads/docs",
        "doc_url"       => "/uploads/docs",
        "video_dir"     => $root . "/uploads/videos",
        "video_url"     => "/uploads/videos",
        "asset_url"     => "/assets",
    ],

    "admin" => [
        // Default admin seeded on first boot. CHANGE THE PASSWORD AFTER FIRST LOGIN.
        "default_username" => getenv("ADMIN_USER") ?: "admin",
        "default_password" => getenv("ADMIN_PASS") ?: "admin1234",
        "default_email"    => getenv("ADMIN_EMAIL") ?: "admin@aniksen.local",
    ],

    "session" => [
        "name"     => "ANIK_SID",
        "lifetime" => 60 * 60 * 4, // 4 hours
    ],
];
