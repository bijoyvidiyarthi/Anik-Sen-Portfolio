<?php
declare(strict_types=1);

require __DIR__ . "/../bootstrap.php";
require __DIR__ . "/partials/layout.php";

use App\Hero;
use App\Csrf;
use App\Upload;
use App\FileLibrary;
use App\Database;

$config = $GLOBALS["APP_CONFIG"];

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    Csrf::require();
    try {
        $current = Hero::get();

        // ---------- Avatar ----------
        $avatar = null;
        if (!empty($_FILES["avatar"]["name"])) {
            $avatar = Upload::image($_FILES["avatar"], $config["paths"]["image_dir"]);
            Upload::delete($config["paths"]["image_dir"], $current["avatar"] ?? "");
        } elseif (!empty($_POST["clear_avatar"])) {
            Upload::delete($config["paths"]["image_dir"], $current["avatar"] ?? "");
            $avatar = "";
        }

        // ---------- CV (PDF only) ----------
        if (!empty($_FILES["cv_file"]["name"])) {
            $orig    = (string) $_FILES["cv_file"]["name"];
            $stored  = Upload::pdf($_FILES["cv_file"], $config["paths"]["doc_dir"]);
            $size    = (int) ($_FILES["cv_file"]["size"] ?? 0);
            $title   = trim((string)($_POST["cv_title"] ?? "")) ?: pathinfo($orig, PATHINFO_FILENAME);

            // Mark every existing CV inactive, then store + activate the new one.
            Database::pdo()->exec("UPDATE file_library SET is_active = 0 WHERE folder = 'cv'");
            FileLibrary::create([
                "title"         => $title,
                "folder"        => "cv",
                "filename"      => $stored,
                "original_name" => $orig,
                "mime"          => "application/pdf",
                "size_bytes"    => $size,
                "description"   => "Uploaded from Hero settings",
                "is_active"     => 1,
            ]);
        } elseif (!empty($_POST["clear_cv"])) {
            $active = FileLibrary::activeCv();
            if ($active) {
                FileLibrary::delete((int) $active["id"], $config["paths"]["doc_dir"]);
            }
        }

        Hero::update($_POST, $avatar);
        flash_set("success", "Hero section updated.");
    } catch (Throwable $e) {
        flash_set("error", $e->getMessage());
    }
    header("Location: /admin/hero.php");
    exit;
}

$hero        = Hero::get();
$activeCv    = FileLibrary::activeCv();
$phrasesText = implode("\n", $hero["phrases_array"] ?? []);

$statsOn   = !isset($hero["stats_enabled"])      || (int)$hero["stats_enabled"]      === 1;
$cueOn     = !isset($hero["scroll_cue_enabled"]) || (int)$hero["scroll_cue_enabled"] === 1;
$orbsOn    = !isset($hero["show_orbs"])          || (int)$hero["show_orbs"]          === 1;

admin_layout_start("Hero Section", "hero");
?>
<?= flash_render() ?>
<form method="POST" enctype="multipart/form-data" class="grid lg:grid-cols-3 gap-5">
    <input type="hidden" name="_csrf" value="<?= htmlspecialchars(Csrf::token(), ENT_QUOTES) ?>">

    <div class="lg:col-span-2 space-y-5">

        <!-- ============== Headline & copy ============== -->
        <div class="glass rounded-2xl p-6 space-y-4">
            <h2 class="text-lg font-semibold flex items-center gap-2">
                <i class="fa-solid fa-pen-nib text-indigo-300"></i> Headline &amp; Copy
            </h2>

            <div class="grid sm:grid-cols-2 gap-4">
                <div>
                    <label class="label">Badge text</label>
                    <input class="input" name="badge_text" value="<?= htmlspecialchars($hero["badge_text"] ?? "") ?>" placeholder="Available for freelance work">
                    <div class="text-[11px] text-white/40 mt-1">Small pill above the title. Leave blank to hide.</div>
                </div>
                <div>
                    <label class="label">Eyebrow line</label>
                    <input class="input" name="eyebrow" value="<?= htmlspecialchars($hero["eyebrow"] ?? "") ?>" placeholder="Hi, I’m">
                    <div class="text-[11px] text-white/40 mt-1">The little greeting above your name.</div>
                </div>
            </div>

            <div>
                <label class="label">Display name</label>
                <input class="input" name="name" value="<?= htmlspecialchars($hero["name"] ?? "") ?>">
            </div>

            <div>
                <label class="label">Typing phrases (one per line)</label>
                <textarea class="textarea" name="phrases_text" rows="4"><?= htmlspecialchars($phrasesText) ?></textarea>
                <div class="text-xs text-white/40 mt-1">Each line becomes one rotating phrase in the hero.</div>
            </div>

            <div>
                <label class="label">Lede paragraph</label>
                <textarea class="textarea" name="lede" rows="3" placeholder="One or two sentences that sell who you are…"><?= htmlspecialchars($hero["lede"] ?? "") ?></textarea>
                <div class="text-[11px] text-white/40 mt-1">Sits right under the typing line. Line breaks are preserved. Leave blank to hide.</div>
            </div>
        </div>

        <!-- ============== Calls to action ============== -->
        <div class="glass rounded-2xl p-6 space-y-4">
            <h2 class="text-lg font-semibold flex items-center gap-2">
                <i class="fa-solid fa-bullseye text-emerald-300"></i> Call-to-Action Buttons
            </h2>
            <div class="grid sm:grid-cols-2 gap-4">
                <div><label class="label">Primary CTA label</label><input class="input" name="cta_label" value="<?= htmlspecialchars($hero["cta_label"] ?? "") ?>" placeholder="View Work"></div>
                <div><label class="label">Primary CTA link</label><input class="input" name="cta_link" value="<?= htmlspecialchars($hero["cta_link"] ?? "") ?>" placeholder="#projects"></div>
            </div>
            <div class="grid sm:grid-cols-2 gap-4">
                <div><label class="label">Secondary CTA label</label><input class="input" name="cta2_label" value="<?= htmlspecialchars($hero["cta2_label"] ?? "") ?>" placeholder="Download CV"></div>
                <div><label class="label">Secondary CTA link</label><input class="input" name="cta2_link" value="<?= htmlspecialchars($hero["cta2_link"] ?? "") ?>" placeholder="/cv.php"></div>
            </div>
            <div class="text-[11px] text-white/40">Tip: leave a label blank to hide the corresponding button. <code>/cv.php</code> automatically becomes a download link.</div>
        </div>

        <!-- ============== Stats strip ============== -->
        <div class="glass rounded-2xl p-6 space-y-4">
            <div class="flex items-center justify-between">
                <h2 class="text-lg font-semibold flex items-center gap-2">
                    <i class="fa-solid fa-chart-simple text-amber-300"></i> Stats Strip
                </h2>
                <label class="inline-flex items-center gap-2 text-xs text-white/70">
                    <input type="checkbox" name="stats_enabled" value="1" <?= $statsOn ? "checked" : "" ?>>
                    Show on site
                </label>
            </div>

            <div class="grid sm:grid-cols-3 gap-4">
                <?php for ($i = 1; $i <= 3; $i++): ?>
                <div class="rounded-xl bg-black/20 border border-white/10 p-3 space-y-2">
                    <div class="text-[11px] uppercase tracking-wider text-white/40">Tile <?= $i ?></div>
                    <div>
                        <label class="label">Value</label>
                        <input class="input" name="stat<?= $i ?>_value" value="<?= htmlspecialchars($hero["stat{$i}_value"] ?? "") ?>" placeholder="<?= ["6+","20+","8+"][$i-1] ?>">
                    </div>
                    <div>
                        <label class="label">Label</label>
                        <input class="input" name="stat<?= $i ?>_label" value="<?= htmlspecialchars($hero["stat{$i}_label"] ?? "") ?>" placeholder="<?= ["Years Experience","Projects Delivered","Happy Clients"][$i-1] ?>">
                    </div>
                </div>
                <?php endfor; ?>
            </div>
            <div class="text-[11px] text-white/40">Two-word labels (like "Years Experience") wrap nicely on two lines automatically. Leave both fields blank to hide a tile.</div>
        </div>

        <!-- ============== Floating chip ============== -->
        <div class="glass rounded-2xl p-6 space-y-4">
            <h2 class="text-lg font-semibold flex items-center gap-2">
                <i class="fa-solid fa-id-badge text-cyan-300"></i> Floating Chip (next to avatar)
            </h2>
            <div class="grid sm:grid-cols-2 gap-4">
                <div><label class="label">Chip title</label><input class="input" name="chip_title" value="<?= htmlspecialchars($hero["chip_title"] ?? "") ?>" placeholder="Graphic Designer"></div>
                <div><label class="label">Chip subtitle</label><input class="input" name="chip_sub" value="<?= htmlspecialchars($hero["chip_sub"] ?? "") ?>" placeholder="& Video Editor"></div>
            </div>
            <div class="text-[11px] text-white/40">Leave both blank to hide the chip entirely.</div>
        </div>

        <!-- ============== Background & cue ============== -->
        <div class="glass rounded-2xl p-6 space-y-4">
            <h2 class="text-lg font-semibold flex items-center gap-2">
                <i class="fa-solid fa-wand-magic-sparkles text-fuchsia-300"></i> Background &amp; Scroll Cue
            </h2>
            <div class="grid sm:grid-cols-2 gap-4">
                <label class="flex items-start gap-3 rounded-xl bg-black/20 border border-white/10 p-3 cursor-pointer">
                    <input type="checkbox" name="show_orbs" value="1" <?= $orbsOn ? "checked" : "" ?> class="mt-1">
                    <span>
                        <span class="block text-sm font-medium text-white">Show drifting background orbs</span>
                        <span class="block text-[11px] text-white/40">Three blurred gradient blobs that float behind the hero.</span>
                    </span>
                </label>
                <label class="flex items-start gap-3 rounded-xl bg-black/20 border border-white/10 p-3 cursor-pointer">
                    <input type="checkbox" name="scroll_cue_enabled" value="1" <?= $cueOn ? "checked" : "" ?> class="mt-1">
                    <span>
                        <span class="block text-sm font-medium text-white">Show scroll-down cue</span>
                        <span class="block text-[11px] text-white/40">Animated mouse icon at the bottom of the hero.</span>
                    </span>
                </label>
            </div>
            <div>
                <label class="label">Scroll cue label</label>
                <input class="input" name="scroll_cue_label" value="<?= htmlspecialchars($hero["scroll_cue_label"] ?? "") ?>" placeholder="Scroll">
            </div>
        </div>

    </div>

    <div class="space-y-5">
        <!-- Avatar card -->
        <div class="glass rounded-2xl p-6 space-y-4">
            <h2 class="text-lg font-semibold flex items-center gap-2"><i class="fa-solid fa-image text-pink-300"></i> Hero avatar</h2>
            <?php
                $previewSrc = !empty($hero["avatar"])
                    ? "/uploads/images/" . htmlspecialchars($hero["avatar"])
                    : "/assets/images/hero-avatar.png";
            ?>
            <div class="rounded-xl overflow-hidden bg-black/30 border border-white/10 p-2 flex items-center justify-center">
                <img src="<?= $previewSrc ?>" alt="" class="max-h-40 object-contain">
            </div>
            <?php if (!empty($hero["avatar"])): ?>
                <label class="flex items-center gap-2 text-xs text-white/70"><input type="checkbox" name="clear_avatar" value="1"> Use the bundled default avatar</label>
            <?php else: ?>
                <div class="text-[11px] text-white/40">Currently using the bundled premium avatar. Upload below to override.</div>
            <?php endif; ?>
            <input type="file" name="avatar" accept="image/*" class="input">
        </div>

        <!-- CV / Resume card -->
        <div class="glass rounded-2xl p-6 space-y-3">
            <h2 class="text-lg font-semibold flex items-center gap-2"><i class="fa-solid fa-file-pdf text-rose-300"></i> CV / Resume (PDF)</h2>

            <?php if ($activeCv): ?>
                <div class="rounded-xl bg-black/30 border border-white/10 p-3 flex items-start gap-3">
                    <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-rose-500 to-pink-500 flex items-center justify-center shrink-0">
                        <i class="fa-solid fa-file-pdf text-white"></i>
                    </div>
                    <div class="min-w-0 flex-1">
                        <div class="text-sm font-semibold text-white truncate"><?= htmlspecialchars($activeCv["title"]) ?></div>
                        <div class="text-[11px] text-white/50 truncate"><?= htmlspecialchars($activeCv["original_name"]) ?></div>
                        <div class="text-[11px] text-white/40 mt-0.5">
                            <?= Upload::humanSize((int) $activeCv["size_bytes"]) ?>
                            &middot;
                            <a href="/cv.php" target="_blank" rel="noopener" class="text-indigo-300 hover:text-indigo-200">
                                <i class="fa-solid fa-arrow-down"></i> Download
                            </a>
                        </div>
                    </div>
                </div>
                <label class="flex items-center gap-2 text-xs text-white/70"><input type="checkbox" name="clear_cv" value="1"> Remove the active CV</label>
            <?php else: ?>
                <div class="text-xs text-white/50 rounded-xl border border-dashed border-white/15 p-3">
                    No CV uploaded yet. The Download CV button on the homepage will fall back to a placeholder PDF until you add one.
                </div>
            <?php endif; ?>

            <div>
                <label class="label">Display title (optional)</label>
                <input class="input" name="cv_title" placeholder="e.g. Anik Sen — 2026 CV">
            </div>
            <div>
                <label class="label">Upload new CV (.pdf, max 32MB)</label>
                <input type="file" name="cv_file" accept="application/pdf,.pdf" class="input">
                <div class="text-[11px] text-white/40 mt-1">Strict server-side check: file extension <em>and</em> magic bytes must match a real PDF.</div>
            </div>
        </div>

        <button class="btn btn-primary w-full justify-center"><i class="fa-solid fa-save"></i> Save Hero</button>
    </div>
</form>
<?php admin_layout_end(); ?>
