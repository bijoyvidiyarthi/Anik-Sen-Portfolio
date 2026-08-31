<?php
declare(strict_types=1);

namespace App;

/**
 * Catalog of developer tools / technologies used on projects.
 * Each entry: [label, letters, color (foreground), bg].
 * Stored on a project as comma-separated keys, rendered as chips.
 */
class Software
{
    private const CATALOG = [
        "php"          => ["PHP",                 "Ph", "#8892BF", "#1D2033"],
        "laravel"      => ["Laravel",             "Lv", "#FF2D20", "#2D0C09"],
        "javascript"   => ["JavaScript",          "Js", "#F7DF1E", "#2B2600"],
        "typescript"   => ["TypeScript",          "Ts", "#3178C6", "#0E1F34"],
        "react"        => ["React",               "Re", "#61DAFB", "#10242D"],
        "nextjs"       => ["Next.js",             "Nx", "#111111", "#F5F5F5"],
        "node"         => ["Node.js",             "Nd", "#68A063", "#101C11"],
        "mysql"        => ["MySQL",               "My", "#00758F", "#062C3C"],
        "postgres"     => ["PostgreSQL",          "Pg", "#336791", "#0B1F2A"],
        "mongodb"      => ["MongoDB",             "Mg", "#47A248", "#0F2A0F"],
        "docker"       => ["Docker",              "Dr", "#2496ED", "#0D2A3D"],
        "git"          => ["Git",                 "Gt", "#F05032", "#2F140D"],
        "github"       => ["GitHub",              "Gh", "#181717", "#F4F4F4"],
        "tailwind"     => ["Tailwind CSS",        "Tw", "#38BDF8", "#0C2033"],
        "redis"        => ["Redis",               "Rd", "#DC382D", "#2A0E0A"],
        "aws"          => ["AWS",                 "Aw", "#FF9900", "#2C1A00"],
        "restapi"      => ["REST API",            "Api", "#8B5CF6", "#211331"],
        "graphql"      => ["GraphQL",             "Gq", "#E10098", "#2F071F"],
    ];

    public static function catalog(): array
    {
        return self::CATALOG;
    }

    public static function get(string $key): ?array
    {
        return self::CATALOG[$key] ?? null;
    }

    public static function parse(?string $stored): array
    {
        if (!$stored) return [];
        $parts = array_filter(array_map("trim", explode(",", $stored)));
        $out = [];
        foreach ($parts as $key) {
            if (isset(self::CATALOG[$key])) {
                $out[$key] = self::CATALOG[$key];
            }
        }
        return $out;
    }
}
