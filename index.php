<?php
/**
 * Anik Sen — Portfolio (DB-driven entry point).
 */

declare(strict_types=1);

require __DIR__ . "/bootstrap.php";

use App\Settings;
use App\Hero;
use App\About;
use App\Project;
use App\Skill;
use App\Education;
use App\Review;
use App\Client;
use App\SiteSection;
use App\Visitor;

// Privacy-aware unique-visitor tracking (deduped per IP per day, stored salted+hashed).
Visitor::track($_SERVER["REQUEST_URI"] ?? "/");

$settings  = Settings::all();
$hero      = Hero::get();
$about     = About::get();
$expertise = About::expertise();
$projects  = Project::all(true);
$creative  = Skill::all("creative");
$software  = Skill::all("software");
$education = Education::all();
$reviews   = Review::all();
$clients   = Client::all(true);

$site = [
    "name"     => $settings["site_name"]     ?? "Anik Sen",
    "tagline"  => $settings["tagline"]       ?? "",
    "email"    => $settings["email"]         ?? "",
    "location" => $settings["location"]      ?? "",
    "logo"     => $settings["logo"]          ?? "",
    "favicon"  => $settings["favicon"]       ?? "",
    "social"   => [
        "facebook" => $settings["social_facebook"] ?? "",
        "linkedin" => $settings["social_linkedin"] ?? "",
        "behance"  => $settings["social_behance"]  ?? "",
    ],
    "asset_base" => "/assets",
];

include __DIR__ . "/includes/header.php";

// Section toggles — admin can hide any of these from /admin/sections.php.
foreach (["hero", "about", "skills", "projects", "education", "reviews", "clients", "contact"] as $section) {
    if (SiteSection::isVisible($section)) {
        include __DIR__ . "/sections/{$section}.php";
    }
}

include __DIR__ . "/includes/footer.php";
