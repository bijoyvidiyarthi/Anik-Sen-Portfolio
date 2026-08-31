<?php
declare(strict_types=1);

require __DIR__ . "/../bootstrap.php";
require __DIR__ . "/partials/layout.php";

use App\Settings;
use App\Csrf;
use App\Upload;

$config = $GLOBALS["APP_CONFIG"];

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    Csrf::require();
    try {
        $kv = [
            "site_name"                    => trim((string)($_POST["site_name"] ?? "")),
            "tagline"                      => trim((string)($_POST["tagline"] ?? "")),
            "email"                        => trim((string)($_POST["email"] ?? "")),
            "location"                     => trim((string)($_POST["location"] ?? "")),
            "social_facebook"              => trim((string)($_POST["social_facebook"] ?? "")),
            "social_linkedin"              => trim((string)($_POST["social_linkedin"] ?? "")),
            "social_behance"               => trim((string)($_POST["social_behance"] ?? "")),
            "footer_about"                 => trim((string)($_POST["footer_about"] ?? "")),
            "theme_primary"                => trim((string)($_POST["theme_primary"] ?? "")),
            "theme_secondary"              => trim((string)($_POST["theme_secondary"] ?? "")),
            "theme_accent"                 => trim((string)($_POST["theme_accent"] ?? "")),
            "theme_background"             => trim((string)($_POST["theme_background"] ?? "")),
            "theme_preset"                 => trim((string)($_POST["theme_preset"] ?? "")),
            "theme_animation"              => trim((string)($_POST["theme_animation"] ?? "")),
            "theme_3d_enabled"             => trim((string)($_POST["theme_3d_enabled"] ?? "")),
            "theme_3d_depth"               => trim((string)($_POST["theme_3d_depth"] ?? "")),
            "image_hover_enabled"          => trim((string)($_POST["image_hover_enabled"] ?? "")),
            "image_hover_type"             => trim((string)($_POST["image_hover_type"] ?? "")),
            "image_hover_direction"        => trim((string)($_POST["image_hover_direction"] ?? "")),
            "image_hover_clip_style"       => trim((string)($_POST["image_hover_clip_style"] ?? "")),
            "image_hover_duration"         => trim((string)($_POST["image_hover_duration"] ?? "")),
            "image_hover_easing"           => trim((string)($_POST["image_hover_easing"] ?? "")),
            "image_hover_scale"            => trim((string)($_POST["image_hover_scale"] ?? "")),
            "image_hover_parallax"         => trim((string)($_POST["image_hover_parallax"] ?? "")),
            "image_hover_tilt"             => trim((string)($_POST["image_hover_tilt"] ?? "")),
            "image_hover_mouse_strength"   => trim((string)($_POST["image_hover_mouse_strength"] ?? "")),
            "image_hover_edge"             => trim((string)($_POST["image_hover_edge"] ?? "")),
            "image_hover_edge_blur"        => trim((string)($_POST["image_hover_edge_blur"] ?? "")),
            "image_hover_edge_glow"        => trim((string)($_POST["image_hover_edge_glow"] ?? "")),
            "image_hover_reverse"          => trim((string)($_POST["image_hover_reverse"] ?? "")),
            "image_hover_mobile_behavior"  => trim((string)($_POST["image_hover_mobile_behavior"] ?? "")),
        ];

        if (!empty($_FILES["logo"]["name"])) {
            $name = Upload::image($_FILES["logo"], $config["paths"]["image_dir"]);
            Upload::delete($config["paths"]["image_dir"], Settings::get("logo"));
            $kv["logo"] = $name;
        }
        if (!empty($_FILES["favicon"]["name"])) {
            $name = Upload::image($_FILES["favicon"], $config["paths"]["image_dir"]);
            Upload::delete($config["paths"]["image_dir"], Settings::get("favicon"));
            $kv["favicon"] = $name;
        }

        if (!empty($_POST["clear_logo"]))    { Upload::delete($config["paths"]["image_dir"], Settings::get("logo")); $kv["logo"] = ""; }
        if (!empty($_POST["clear_favicon"])) { Upload::delete($config["paths"]["image_dir"], Settings::get("favicon")); $kv["favicon"] = ""; }

        Settings::setMany($kv);
        flash_set("success", "Site settings saved.");
    } catch (Throwable $e) {
        flash_set("error", $e->getMessage());
    }
    header("Location: /admin/settings.php"); exit;
}

$s = Settings::all();

admin_layout_start("Site Settings", "settings");
?>
<?= flash_render() ?>
<form method="POST" enctype="multipart/form-data" class="grid lg:grid-cols-3 gap-5">
    <input type="hidden" name="_csrf" value="<?= htmlspecialchars(Csrf::token(), ENT_QUOTES) ?>">

    <div class="lg:col-span-2 glass rounded-2xl p-6 space-y-4">
        <h2 class="text-lg font-semibold mb-2">General</h2>
        <div class="grid sm:grid-cols-2 gap-4">
            <div><label class="label">Site name</label><input class="input" name="site_name" value="<?= htmlspecialchars($s["site_name"] ?? "") ?>"></div>
            <div><label class="label">Tagline</label><input class="input" name="tagline" value="<?= htmlspecialchars($s["tagline"] ?? "") ?>"></div>
            <div><label class="label">Contact email</label><input class="input" type="email" name="email" value="<?= htmlspecialchars($s["email"] ?? "") ?>"></div>
            <div><label class="label">Location</label><input class="input" name="location" value="<?= htmlspecialchars($s["location"] ?? "") ?>"></div>
        </div>

        <h2 class="text-lg font-semibold mt-4">Footer</h2>
        <div><label class="label">Short footer description</label>
            <textarea class="textarea" name="footer_about" rows="2"><?= htmlspecialchars($s["footer_about"] ?? "") ?></textarea>
        </div>

        <h2 class="text-lg font-semibold mt-4">Social Links</h2>
        <div class="grid sm:grid-cols-3 gap-4">
            <div><label class="label">Facebook URL</label><input class="input" name="social_facebook" value="<?= htmlspecialchars($s["social_facebook"] ?? "") ?>"></div>
            <div><label class="label">LinkedIn URL</label><input class="input" name="social_linkedin" value="<?= htmlspecialchars($s["social_linkedin"] ?? "") ?>"></div>
            <div><label class="label">Behance URL</label><input class="input" name="social_behance" value="<?= htmlspecialchars($s["social_behance"] ?? "") ?>"></div>
        </div>

        <h2 class="text-lg font-semibold mt-5">Theme & Motion</h2>
        <div class="mb-4">
            <label class="label">Preset themes</label>
            <div class="theme-preset-grid">
                <?php
                $presetThemes = [
                    "neon" => ["label" => "Neon Vivid", "primary" => "#8b5cf6", "secondary" => "#22d3ee", "accent" => "#f472b6", "background" => "#0a0b14"],
                    "ocean" => ["label" => "Ocean Blue", "primary" => "#2563eb", "secondary" => "#38bdf8", "accent" => "#34d399", "background" => "#071521"],
                    "sunset" => ["label" => "Sunset Glow", "primary" => "#f97316", "secondary" => "#fb7185", "accent" => "#facc15", "background" => "#1a0f18"],
                    "forest" => ["label" => "Forest Tech", "primary" => "#22c55e", "secondary" => "#14b8a6", "accent" => "#a3e635", "background" => "#0a1717"],
                ];
                $activePreset = (string)($s["theme_preset"] ?? "neon");
                foreach ($presetThemes as $key => $preset):
                    $isActive = $activePreset === $key || ($activePreset === "" && $key === "neon");
                ?>
                    <button type="button"
                            class="theme-preset <?= $isActive ? "is-active" : "" ?>"
                            data-preset="<?= htmlspecialchars($key) ?>"
                            data-primary="<?= htmlspecialchars($preset["primary"]) ?>"
                            data-secondary="<?= htmlspecialchars($preset["secondary"]) ?>"
                            data-accent="<?= htmlspecialchars($preset["accent"]) ?>"
                            data-background="<?= htmlspecialchars($preset["background"]) ?>"
                            aria-label="Apply <?= htmlspecialchars($preset["label"]) ?> theme">
                        <span class="theme-swatch" style="background:linear-gradient(135deg, <?= htmlspecialchars($preset["primary"]) ?>, <?= htmlspecialchars($preset["secondary"]) ?>, <?= htmlspecialchars($preset["accent"]) ?>);"></span>
                        <span><?= htmlspecialchars($preset["label"]) ?></span>
                    </button>
                <?php endforeach; ?>
            </div>
        </div>
        <div class="grid sm:grid-cols-2 gap-4">
            <div><label class="label">Primary color</label><input id="themePrimary" type="color" class="input h-12 p-1" name="theme_primary" value="<?= htmlspecialchars($s["theme_primary"] ?? "#8b5cf6") ?>"></div>
            <div><label class="label">Secondary color</label><input id="themeSecondary" type="color" class="input h-12 p-1" name="theme_secondary" value="<?= htmlspecialchars($s["theme_secondary"] ?? "#22d3ee") ?>"></div>
            <div><label class="label">Accent color</label><input id="themeAccent" type="color" class="input h-12 p-1" name="theme_accent" value="<?= htmlspecialchars($s["theme_accent"] ?? "#f472b6") ?>"></div>
            <div><label class="label">Background color</label><input id="themeBackground" type="color" class="input h-12 p-1" name="theme_background" value="<?= htmlspecialchars($s["theme_background"] ?? "#0a0b14") ?>"></div>
            <div>
                <label class="label">Animation style</label>
                <select class="input" name="theme_animation">
                    <option value="subtle" <?= (($s["theme_animation"] ?? "dynamic") === "subtle") ? "selected" : "" ?>>Subtle</option>
                    <option value="dynamic" <?= (($s["theme_animation"] ?? "dynamic") === "dynamic") ? "selected" : "" ?>>Dynamic</option>
                    <option value="bold" <?= (($s["theme_animation"] ?? "dynamic") === "bold") ? "selected" : "" ?>>Bold</option>
                </select>
            </div>
            <div>
                <label class="label">3D motion</label>
                <div class="mt-2 flex items-center gap-2 rounded-xl border border-white/10 bg-black/20 px-3 py-2">
                    <input type="checkbox" name="theme_3d_enabled" value="1" <?= (($s["theme_3d_enabled"] ?? "1") === "1" || ($s["theme_3d_enabled"] ?? "1") === "true") ? "checked" : "" ?>>
                    <span class="text-sm text-white/80">Enable 3D hover effects</span>
                </div>
            </div>
            <div class="sm:col-span-2">
                <label class="label">3D intensity</label>
                <input type="range" min="8" max="28" step="1" class="input h-11 w-full accent-violet-500" name="theme_3d_depth" value="<?= htmlspecialchars((string)($s["theme_3d_depth"] ?? "18")) ?>">
                <div class="text-xs text-white/50 mt-1">Strong tilt: <?= htmlspecialchars((string)($s["theme_3d_depth"] ?? "18")) ?>°</div>
            </div>
        </div>
        <input type="hidden" name="theme_preset" id="themePreset" value="<?= htmlspecialchars((string)($s["theme_preset"] ?? "neon")) ?>">
    </div>

    <div class="glass rounded-2xl p-6 space-y-5">
        <h2 class="text-lg font-semibold mb-2">Appearance → Image Hover Effects</h2>
        <div class="grid sm:grid-cols-2 gap-4">
            <div>
                <label class="label">Enable image hover</label>
                <div class="mt-2 flex items-center gap-2 rounded-xl border border-white/10 bg-black/20 px-3 py-2">
                    <input type="checkbox" name="image_hover_enabled" value="1" <?= (($s["image_hover_enabled"] ?? "1") === "1" || ($s["image_hover_enabled"] ?? "1") === "true") ? "checked" : "" ?>>
                    <span class="text-sm text-white/80">Use cinematic image swap effects</span>
                </div>
            </div>
            <div>
                <label class="label">Hover transition type</label>
                <select class="input" name="image_hover_type">
                    <option value="clip" <?= (($s["image_hover_type"] ?? "clip") === "clip") ? "selected" : "" ?>>Clip reveal</option>
                    <option value="mask" <?= (($s["image_hover_type"] ?? "clip") === "mask") ? "selected" : "" ?>>Mask reveal</option>
                </select>
            </div>
            <div>
                <label class="label">Reveal direction</label>
                <select class="input" name="image_hover_direction">
                    <option value="lr" <?= (($s["image_hover_direction"] ?? "lr") === "lr") ? "selected" : "" ?>>Left → Right</option>
                    <option value="rl" <?= (($s["image_hover_direction"] ?? "lr") === "rl") ? "selected" : "" ?>>Right → Left</option>
                    <option value="tb" <?= (($s["image_hover_direction"] ?? "lr") === "tb") ? "selected" : "" ?>>Top → Bottom</option>
                    <option value="bt" <?= (($s["image_hover_direction"] ?? "lr") === "bt") ? "selected" : "" ?>>Bottom → Top</option>
                    <option value="center" <?= (($s["image_hover_direction"] ?? "lr") === "center") ? "selected" : "" ?>>Center → Outside</option>
                    <option value="radial" <?= (($s["image_hover_direction"] ?? "lr") === "radial") ? "selected" : "" ?>>Circular</option>
                    <option value="mouse" <?= (($s["image_hover_direction"] ?? "lr") === "mouse") ? "selected" : "" ?>>Mouse follow</option>
                </select>
            </div>
            <div>
                <label class="label">Clip style</label>
                <select class="input" name="image_hover_clip_style">
                    <option value="inset" <?= (($s["image_hover_clip_style"] ?? "inset") === "inset") ? "selected" : "" ?>>Inset</option>
                    <option value="polygon" <?= (($s["image_hover_clip_style"] ?? "inset") === "polygon") ? "selected" : "" ?>>Polygon</option>
                    <option value="circle" <?= (($s["image_hover_clip_style"] ?? "inset") === "circle") ? "selected" : "" ?>>Circle</option>
                </select>
            </div>
            <div>
                <label class="label">Animation duration</label>
                <input type="range" min="0.6" max="1.2" step="0.1" class="input h-11 w-full accent-violet-500" name="image_hover_duration" value="<?= htmlspecialchars((string)($s["image_hover_duration"] ?? "0.9")) ?>">
                <div class="text-xs text-white/50 mt-1"><?= htmlspecialchars((string)($s["image_hover_duration"] ?? "0.9")) ?>s</div>
            </div>
            <div>
                <label class="label">Easing</label>
                <select class="input" name="image_hover_easing">
                    <option value="cubic-bezier(0.22, 1, 0.36, 1)" <?= (($s["image_hover_easing"] ?? "cubic-bezier(0.22, 1, 0.36, 1)") === "cubic-bezier(0.22, 1, 0.36, 1)") ? "selected" : "" ?>>Cubic Bezier</option>
                    <option value="ease-out" <?= (($s["image_hover_easing"] ?? "cubic-bezier(0.22, 1, 0.36, 1)") === "ease-out") ? "selected" : "" ?>>Ease Out</option>
                    <option value="ease-in-out" <?= (($s["image_hover_easing"] ?? "cubic-bezier(0.22, 1, 0.36, 1)") === "ease-in-out") ? "selected" : "" ?>>Ease In Out</option>
                </select>
            </div>
            <div>
                <label class="label">Image scale</label>
                <input type="range" min="1.00" max="1.10" step="0.01" class="input h-11 w-full accent-violet-500" name="image_hover_scale" value="<?= htmlspecialchars((string)($s["image_hover_scale"] ?? "1.05")) ?>">
                <div class="text-xs text-white/50 mt-1"><?= htmlspecialchars((string)($s["image_hover_scale"] ?? "1.05")) ?>x</div>
            </div>
            <div>
                <label class="label">Parallax strength</label>
                <input type="range" min="0" max="25" step="1" class="input h-11 w-full accent-violet-500" name="image_hover_parallax" value="<?= htmlspecialchars((string)($s["image_hover_parallax"] ?? "12")) ?>">
                <div class="text-xs text-white/50 mt-1"><?= htmlspecialchars((string)($s["image_hover_parallax"] ?? "12")) ?>px</div>
            </div>
            <div>
                <label class="label">3D tilt strength</label>
                <input type="range" min="0" max="16" step="1" class="input h-11 w-full accent-violet-500" name="image_hover_tilt" value="<?= htmlspecialchars((string)($s["image_hover_tilt"] ?? "8")) ?>">
                <div class="text-xs text-white/50 mt-1"><?= htmlspecialchars((string)($s["image_hover_tilt"] ?? "8")) ?>°</div>
            </div>
            <div>
                <label class="label">Mouse-follow strength</label>
                <input type="range" min="0" max="24" step="1" class="input h-11 w-full accent-violet-500" name="image_hover_mouse_strength" value="<?= htmlspecialchars((string)($s["image_hover_mouse_strength"] ?? "12")) ?>">
                <div class="text-xs text-white/50 mt-1"><?= htmlspecialchars((string)($s["image_hover_mouse_strength"] ?? "12")) ?>px</div>
            </div>
            <div>
                <label class="label">Reveal edge</label>
                <div class="mt-2 flex items-center gap-2 rounded-xl border border-white/10 bg-black/20 px-3 py-2">
                    <input type="checkbox" name="image_hover_edge" value="1" <?= (($s["image_hover_edge"] ?? "1") === "1" || ($s["image_hover_edge"] ?? "1") === "true") ? "checked" : "" ?>>
                    <span class="text-sm text-white/80">Enable edge highlight</span>
                </div>
            </div>
            <div>
                <label class="label">Edge blur</label>
                <input type="range" min="0" max="28" step="1" class="input h-11 w-full accent-violet-500" name="image_hover_edge_blur" value="<?= htmlspecialchars((string)($s["image_hover_edge_blur"] ?? "12")) ?>">
                <div class="text-xs text-white/50 mt-1"><?= htmlspecialchars((string)($s["image_hover_edge_blur"] ?? "12")) ?>px</div>
            </div>
            <div>
                <label class="label">Edge glow</label>
                <input type="range" min="0" max="1" step="0.05" class="input h-11 w-full accent-violet-500" name="image_hover_edge_glow" value="<?= htmlspecialchars((string)($s["image_hover_edge_glow"] ?? "0.7")) ?>">
                <div class="text-xs text-white/50 mt-1"><?= htmlspecialchars((string)($s["image_hover_edge_glow"] ?? "0.7")) ?></div>
            </div>
            <div>
                <label class="label">Reverse animation</label>
                <div class="mt-2 flex items-center gap-2 rounded-xl border border-white/10 bg-black/20 px-3 py-2">
                    <input type="checkbox" name="image_hover_reverse" value="1" <?= (($s["image_hover_reverse"] ?? "1") === "1" || ($s["image_hover_reverse"] ?? "1") === "true") ? "checked" : "" ?>>
                    <span class="text-sm text-white/80">Reverse on mouse leave</span>
                </div>
            </div>
            <div>
                <label class="label">Mobile behavior</label>
                <select class="input" name="image_hover_mobile_behavior">
                    <option value="tap" <?= (($s["image_hover_mobile_behavior"] ?? "tap") === "tap") ? "selected" : "" ?>>Tap to reveal</option>
                    <option value="auto" <?= (($s["image_hover_mobile_behavior"] ?? "tap") === "auto") ? "selected" : "" ?>>Auto animation</option>
                    <option value="disabled" <?= (($s["image_hover_mobile_behavior"] ?? "tap") === "disabled") ? "selected" : "" ?>>Disabled</option>
                </select>
            </div>
        </div>
    </div>

    <div class="glass rounded-2xl p-6 space-y-5">
        <div>
            <h2 class="text-lg font-semibold mb-2">Theme Preview</h2>
            <div class="theme-preview-panel" id="themePreviewPanel">
                <div class="theme-preview-header">
                    <span class="theme-preview-dot" style="background: var(--theme-primary, #8b5cf6);"></span>
                    <span class="theme-preview-dot" style="background: var(--theme-secondary, #22d3ee);"></span>
                    <span class="theme-preview-dot" style="background: var(--theme-accent, #f472b6);"></span>
                </div>
                <div class="theme-preview-card">
                    <div class="theme-preview-badge" id="themePreviewBadge">Developer</div>
                    <h3>Portfolio Preview</h3>
                    <p>Modern motion, premium gradients, and sharper visual hierarchy.</p>
                    <div class="theme-preview-actions">
                        <span class="theme-preview-button">Hire Me</span>
                        <span class="theme-preview-button secondary">View Work</span>
                    </div>
                </div>
            </div>
        </div>

        <div>
            <h2 class="text-lg font-semibold mb-2">Logo</h2>
            <?php if (!empty($s["logo"])): ?>
                <div class="mb-2 p-3 rounded-lg bg-black/30 border border-white/10 flex items-center gap-3">
                    <img src="/uploads/images/<?= htmlspecialchars($s["logo"]) ?>" alt="" class="h-10 w-auto bg-white/10 p-1 rounded">
                    <label class="text-xs flex items-center gap-1 ml-auto"><input type="checkbox" name="clear_logo" value="1"> Remove</label>
                </div>
            <?php endif; ?>
            <input type="file" name="logo" accept="image/*" class="input">
        </div>

        <div>
            <h2 class="text-lg font-semibold mb-2">Favicon</h2>
            <?php if (!empty($s["favicon"])): ?>
                <div class="mb-2 p-3 rounded-lg bg-black/30 border border-white/10 flex items-center gap-3">
                    <img src="/uploads/images/<?= htmlspecialchars($s["favicon"]) ?>" alt="" class="h-8 w-8 bg-white/10 p-1 rounded">
                    <label class="text-xs flex items-center gap-1 ml-auto"><input type="checkbox" name="clear_favicon" value="1"> Remove</label>
                </div>
            <?php endif; ?>
            <input type="file" name="favicon" accept="image/*" class="input">
        </div>

        <button type="submit" class="btn btn-primary w-full justify-center">
            <i class="fa-solid fa-save"></i> Save Settings
        </button>
    </div>
</form>

<style>
    .theme-preset-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
        gap: .75rem;
        margin-top: .5rem;
    }
    .theme-preset {
        display: flex;
        align-items: center;
        gap: .6rem;
        width: 100%;
        padding: .7rem .8rem;
        background: rgba(255,255,255,0.02);
        border: 1px solid rgba(255,255,255,0.08);
        border-radius: 0.9rem;
        color: rgba(255,255,255,0.8);
        text-align: left;
        transition: border-color .2s ease, transform .2s ease, background .2s ease;
    }
    .theme-preset.is-active {
        background: rgba(139,92,246,0.12);
        border-color: rgba(139,92,246,0.5);
        box-shadow: 0 0 0 1px rgba(139,92,246,0.18);
    }
    .theme-preset:hover {
        transform: translateY(-1px);
        border-color: rgba(255,255,255,0.18);
    }
    .theme-swatch {
        width: 1.7rem;
        height: 1.7rem;
        border-radius: .6rem;
        display: inline-block;
        border: 1px solid rgba(255,255,255,0.2);
        flex-shrink: 0;
    }
    .theme-preview-panel {
        padding: 1rem;
        background: linear-gradient(135deg, rgba(255,255,255,0.04), rgba(255,255,255,0.01));
        border: 1px solid rgba(255,255,255,0.08);
        border-radius: 1rem;
    }
    .theme-preview-header {
        display: flex;
        gap: .45rem;
        margin-bottom: 1rem;
    }
    .theme-preview-dot {
        width: .7rem;
        height: .7rem;
        border-radius: 999px;
        display: inline-block;
    }
    .theme-preview-card {
        position: relative;
        padding: 1rem;
        border-radius: 1rem;
        background: linear-gradient(135deg, rgba(17,24,39,.95), rgba(9,12,20,.88));
        border: 1px solid rgba(255,255,255,0.08);
        box-shadow: 0 20px 40px -22px rgba(0,0,0,.7);
    }
    .theme-preview-badge {
        display: inline-block;
        padding: .3rem .55rem;
        border-radius: 999px;
        font-size: .7rem;
        font-weight: 700;
        letter-spacing: 0.05em;
        text-transform: uppercase;
        background: rgba(255,255,255,0.06);
        color: #fff;
    }
    .theme-preview-card h3 {
        margin: .8rem 0 .4rem;
        font-size: 1.2rem;
        font-weight: 700;
    }
    .theme-preview-card p {
        margin: 0;
        color: rgba(255,255,255,0.7);
        font-size: .82rem;
        line-height: 1.5;
    }
    .theme-preview-actions {
        display: flex;
        gap: .5rem;
        margin-top: 1rem;
    }
    .theme-preview-button {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        padding: .55rem .8rem;
        border-radius: 999px;
        border: 1px solid rgba(255,255,255,0.12);
        background: linear-gradient(135deg, var(--theme-primary, #8b5cf6), var(--theme-secondary, #22d3ee));
        color: #fff;
        font-weight: 600;
        font-size: .7rem;
    }
    .theme-preview-button.secondary {
        background: rgba(255,255,255,0.04);
        color: rgba(255,255,255,0.9);
        border-color: rgba(255,255,255,0.12);
    }
</style>

<script>
    (function () {
        const presetButtons = document.querySelectorAll('.theme-preset');
        const primaryInput = document.getElementById('themePrimary');
        const secondaryInput = document.getElementById('themeSecondary');
        const accentInput = document.getElementById('themeAccent');
        const backgroundInput = document.getElementById('themeBackground');
        const themePresetInput = document.getElementById('themePreset');
        const previewPanel = document.getElementById('themePreviewPanel');

        function updatePreview() {
            const primary = primaryInput.value;
            const secondary = secondaryInput.value;
            const accent = accentInput.value;
            const background = backgroundInput.value;
            if (previewPanel) {
                previewPanel.style.setProperty('--theme-primary', primary);
                previewPanel.style.setProperty('--theme-secondary', secondary);
                previewPanel.style.setProperty('--theme-accent', accent);
                previewPanel.style.setProperty('--theme-background', background);
            }
            const bgRGB = background.replace('#', '');
            const luminosity = [
                parseInt(bgRGB.substr(0,2),16),
                parseInt(bgRGB.substr(2,2),16),
                parseInt(bgRGB.substr(4,2),16)
            ].reduce((sum, value) => sum + value, 0) / 3;
            const textColor = luminosity > 160 ? '#0b1020' : '#f8fafc';
            if (previewPanel) {
                previewPanel.style.background = 'linear-gradient(135deg, ' + background + ', rgba(15, 17, 30, 0.75))';
                previewPanel.querySelector('.theme-preview-card').style.color = textColor;
            }
        }

        function setPreset(key, values) {
            if (!key || !values) return;
            themePresetInput.value = key;
            primaryInput.value = values.primary;
            secondaryInput.value = values.secondary;
            accentInput.value = values.accent;
            backgroundInput.value = values.background;
            presetButtons.forEach((btn) => {
                btn.classList.toggle('is-active', btn.dataset.preset === key);
            });
            updatePreview();
        }

        presetButtons.forEach((button) => {
            button.addEventListener('click', () => {
                setPreset(button.dataset.preset, {
                    primary: button.dataset.primary,
                    secondary: button.dataset.secondary,
                    accent: button.dataset.accent,
                    background: button.dataset.background,
                });
            });
        });

        [primaryInput, secondaryInput, accentInput, backgroundInput].forEach((input) => {
            input.addEventListener('input', () => {
                themePresetInput.value = 'custom';
                presetButtons.forEach((btn) => btn.classList.remove('is-active'));
                updatePreview();
            });
        });

        updatePreview();
    })();
</script>

<?php admin_layout_end(); ?>
