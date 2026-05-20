<?php
declare(strict_types=1);

namespace App;

/**
 * Catalog of creative software / tools used on projects.
 * Each entry: [label, letters, color (foreground), bg].
 * Stored on a project as comma-separated keys, rendered as chips.
 */
class Software
{
    private const CATALOG = [
        "premiere"     => ["Premiere Pro",       "Pr", "#EA77FF", "#00005B"],
        "aftereffects" => ["After Effects",      "Ae", "#D291FF", "#1A0033"],
        "photoshop"    => ["Photoshop",          "Ps", "#31A8FF", "#001E36"],
        "illustrator"  => ["Illustrator",        "Ai", "#FF9A00", "#330000"],
        "lightroom"    => ["Lightroom",          "Lr", "#31A8FF", "#001E36"],
        "audition"     => ["Audition",           "Au", "#00E1A0", "#00375B"],
        "indesign"     => ["InDesign",           "Id", "#FF3366", "#49021F"],
        "davinci"      => ["DaVinci Resolve",    "Dv", "#FF6B6B", "#1A1A1A"],
        "finalcut"     => ["Final Cut Pro",      "Fc", "#000000", "#F5F5F5"],
        "figma"        => ["Figma",              "Fg", "#A259FF", "#1E1E1E"],
        "canva"        => ["Canva",              "Cv", "#00C4CC", "#003F4D"],
        "blender"      => ["Blender",            "Bl", "#F5792A", "#1B1B1B"],
        "capcut"       => ["CapCut",             "Cc", "#FFFFFF", "#000000"],
        "gemini"       => ["Gemini AI",          "Ge", "#8E75B2", "#1A0F2C"],
        "chatgpt"      => ["ChatGPT",            "GP", "#10A37F", "#0B2A21"],
        "midjourney"   => ["Midjourney",         "Mj", "#FFFFFF", "#000000"],
        "runway"       => ["Runway",             "Rw", "#00FF88", "#0F0F0F"],
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
