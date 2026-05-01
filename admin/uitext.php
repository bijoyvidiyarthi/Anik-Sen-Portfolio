<?php
declare(strict_types=1);

require __DIR__ . "/../bootstrap.php";
require __DIR__ . "/partials/layout.php";

use App\Settings;
use App\Csrf;
use App\Migrator;

$defaults = Migrator::uiTextDefaults();

$groups = [
    "Header & Footer" => [
        "header_hire_label"   => ["Hire button label", "input"],
        "header_hire_link"    => ["Hire button link (anchor or URL)", "input"],
        "footer_copyright"    => ["Footer copyright suffix", "input"],
    ],
    "About Section" => [
        "about_title"         => ["Section title", "input"],
        "about_card_title"    => ["Side card title", "input"],
    ],
    "Skills Section" => [
        "skills_title"        => ["Section title", "input"],
        "skills_creative"     => ["Creative skills heading", "input"],
        "skills_software"     => ["Software toolkit heading", "input"],
    ],
    "Projects Section" => [
        "projects_eyebrow"        => ["Eyebrow text", "input"],
        "projects_title"          => ["Section title", "input"],
        "projects_subtitle"       => ["Section subtitle", "textarea"],
        "projects_filter_all"     => ["Filter: All Work", "input"],
        "projects_filter_video"   => ["Filter: Video & Motion", "input"],
        "projects_filter_graphic" => ["Filter: Graphic Design", "input"],
        "projects_empty_label"    => ["Empty-state message", "textarea"],
        "projects_loadmore_label" => ["Load-more button label", "input"],
    ],
    "Education Section" => [
        "education_title"     => ["Section title", "input"],
    ],
    "Reviews Section" => [
        "reviews_title"       => ["Section title", "input"],
        "reviews_subtitle"    => ["Section subtitle", "input"],
    ],
    "Trusted Clients Section" => [
        "clients_title"       => ["Section title", "input"],
        "clients_subtitle"    => ["Section subtitle", "input"],
    ],
    "Contact Section" => [
        "contact_title"               => ["Title (use | or <br> for line break)", "input"],
        "contact_subtitle"            => ["Subtitle paragraph", "textarea"],
        "contact_label_name"          => ["Field label: Name", "input"],
        "contact_label_email"         => ["Field label: Email", "input"],
        "contact_label_subject"       => ["Field label: Subject", "input"],
        "contact_label_message"       => ["Field label: Message", "input"],
        "contact_placeholder_name"    => ["Placeholder: Name", "input"],
        "contact_placeholder_email"   => ["Placeholder: Email", "input"],
        "contact_placeholder_subject" => ["Placeholder: Subject", "input"],
        "contact_placeholder_message" => ["Placeholder: Message", "input"],
        "contact_submit_label"        => ["Submit button label", "input"],
    ],
];

$allKeys = [];
foreach ($groups as $fields) {
    foreach ($fields as $k => $_meta) $allKeys[] = $k;
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    Csrf::require();
    try {
        if (!empty($_POST["reset_all"])) {
            Settings::setMany($defaults);
            flash_set("success", "All UI text reset to defaults.");
        } else {
            $kv = [];
            foreach ($allKeys as $k) {
                if (array_key_exists($k, $_POST)) {
                    $val = trim((string) $_POST[$k]);
                    if ($val === "" && isset($defaults[$k])) {
                        $val = (string) $defaults[$k];
                    }
                    $kv[$k] = $val;
                }
            }
            Settings::setMany($kv);
            flash_set("success", "UI text saved.");
        }
    } catch (Throwable $e) {
        flash_set("error", $e->getMessage());
    }
    header("Location: /admin/uitext.php"); exit;
}

$current = Settings::all();

admin_layout_start("UI Text & Labels", "uitext");
?>
<?= flash_render() ?>

<div class="glass rounded-2xl p-5 mb-5 flex items-start gap-3">
    <i class="fa-solid fa-circle-info text-indigo-300 text-lg mt-0.5"></i>
    <div class="text-sm text-white/75 leading-relaxed">
        Edit every visible label, heading and placeholder used across the public website.
        Leave a field empty to restore its built-in default. Use the
        <span class="px-1.5 py-0.5 rounded bg-white/10 font-mono text-xs">|</span>
        character (or <span class="px-1.5 py-0.5 rounded bg-white/10 font-mono text-xs">&lt;br&gt;</span>)
        in the Contact title to force a line break.
    </div>
</div>

<form method="POST" class="space-y-5">
    <input type="hidden" name="_csrf" value="<?= htmlspecialchars(Csrf::token(), ENT_QUOTES) ?>">

    <?php foreach ($groups as $groupTitle => $fields): ?>
        <section class="glass rounded-2xl p-6">
            <h2 class="text-lg font-semibold mb-4 flex items-center gap-2">
                <i class="fa-solid fa-pen-to-square text-indigo-300"></i>
                <?= htmlspecialchars($groupTitle) ?>
            </h2>
            <div class="grid sm:grid-cols-2 gap-4">
                <?php foreach ($fields as $key => [$label, $type]): ?>
                    <?php
                        $value = $current[$key] ?? ($defaults[$key] ?? "");
                        $placeholder = (string) ($defaults[$key] ?? "");
                        $colSpan = $type === "textarea" ? "sm:col-span-2" : "";
                    ?>
                    <div class="<?= $colSpan ?>">
                        <label class="label flex items-center justify-between">
                            <span><?= htmlspecialchars($label) ?></span>
                            <span class="text-[10px] font-mono text-white/40"><?= htmlspecialchars($key) ?></span>
                        </label>
                        <?php if ($type === "textarea"): ?>
                            <textarea class="textarea" name="<?= htmlspecialchars($key) ?>" rows="2"
                                      placeholder="<?= htmlspecialchars($placeholder) ?>"><?= htmlspecialchars((string) $value) ?></textarea>
                        <?php else: ?>
                            <input class="input" type="text" name="<?= htmlspecialchars($key) ?>"
                                   value="<?= htmlspecialchars((string) $value) ?>"
                                   placeholder="<?= htmlspecialchars($placeholder) ?>">
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>
    <?php endforeach; ?>

    <div class="flex flex-wrap items-center gap-3 sticky bottom-3 z-10">
        <button type="submit" class="btn btn-primary">
            <i class="fa-solid fa-save"></i> Save Changes
        </button>
        <button type="submit" name="reset_all" value="1" class="btn btn-ghost"
                onclick="return confirm('Reset every UI text field to its built-in default?');">
            <i class="fa-solid fa-rotate-left"></i> Reset all to defaults
        </button>
    </div>
</form>

<?php admin_layout_end(); ?>
