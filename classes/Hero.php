<?php
declare(strict_types=1);

namespace App;

class Hero
{
    public static function get(): array
    {
        $row = Database::pdo()->query("SELECT * FROM hero_content ORDER BY id LIMIT 1")->fetch() ?: [];
        $row["phrases_array"] = $row && !empty($row["phrases"])
            ? (json_decode((string) $row["phrases"], true) ?: [])
            : [];
        return $row;
    }

    public static function update(array $data, ?string $avatar = null): void
    {
        $hero = self::get();
        $sql = "UPDATE hero_content SET
                    badge_text         = :b,
                    name               = :n,
                    eyebrow            = :ey,
                    lede               = :ld,
                    phrases            = :p,
                    cta_label          = :cl,
                    cta_link           = :cu,
                    cta2_label         = :cl2,
                    cta2_link          = :cu2,
                    chip_title         = :ct,
                    chip_sub           = :cs,
                    stats_enabled      = :se,
                    stat1_value        = :s1v, stat1_label = :s1l,
                    stat2_value        = :s2v, stat2_label = :s2l,
                    stat3_value        = :s3v, stat3_label = :s3l,
                    scroll_cue_enabled = :sce,
                    scroll_cue_label   = :scl,
                    show_orbs          = :so"
            . ($avatar !== null ? ", avatar = :av" : "")
            . " WHERE id = :id";

        $params = [
            ":b"   => $data["badge_text"]       ?? "",
            ":n"   => $data["name"]             ?? "",
            ":ey"  => $data["eyebrow"]          ?? "",
            ":ld"  => $data["lede"]             ?? "",
            ":p"   => json_encode(array_values(array_filter(array_map("trim", explode("\n", (string)($data["phrases_text"] ?? "")))))),
            ":cl"  => $data["cta_label"]        ?? "",
            ":cu"  => $data["cta_link"]         ?? "",
            ":cl2" => $data["cta2_label"]       ?? "",
            ":cu2" => $data["cta2_link"]        ?? "",
            ":ct"  => $data["chip_title"]       ?? "",
            ":cs"  => $data["chip_sub"]         ?? "",
            ":se"  => !empty($data["stats_enabled"]) ? 1 : 0,
            ":s1v" => $data["stat1_value"]      ?? "",
            ":s1l" => $data["stat1_label"]      ?? "",
            ":s2v" => $data["stat2_value"]      ?? "",
            ":s2l" => $data["stat2_label"]      ?? "",
            ":s3v" => $data["stat3_value"]      ?? "",
            ":s3l" => $data["stat3_label"]      ?? "",
            ":sce" => !empty($data["scroll_cue_enabled"]) ? 1 : 0,
            ":scl" => $data["scroll_cue_label"] ?? "",
            ":so"  => !empty($data["show_orbs"]) ? 1 : 0,
            ":id"  => (int) ($hero["id"] ?? 0),
        ];
        if ($avatar !== null) {
            $params[":av"] = $avatar;
        }
        Database::pdo()->prepare($sql)->execute($params);
    }
}
