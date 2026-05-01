<?php
/**
 * Section & Menu management — toggle frontend sections and individual
 * header/footer menu items on or off.
 */
declare(strict_types=1);

require __DIR__ . "/../bootstrap.php";
require __DIR__ . "/partials/layout.php";

use App\Csrf;
use App\SiteSection;
use App\MenuItem;

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    Csrf::require();
    $action = $_POST["action"] ?? "";
    $tab    = $_POST["tab"] ?? "sections";
    try {
        switch ($action) {
            case "toggle_section":
                SiteSection::setVisible(
                    (string) $_POST["key"],
                    !empty($_POST["is_visible"])
                );
                flash_set("success", "Section visibility updated.");
                break;

            case "toggle_menu":
                MenuItem::setVisible(
                    (int) $_POST["id"],
                    !empty($_POST["is_visible"])
                );
                flash_set("success", "Menu item visibility updated.");
                break;

            case "create_menu":
                $label = trim((string)($_POST["label"] ?? ""));
                $href  = trim((string)($_POST["href"]  ?? ""));
                if ($label === "" || $href === "") {
                    throw new RuntimeException("Label and link are required.");
                }
                MenuItem::create([
                    "location"   => $_POST["location"] ?? "header",
                    "label"      => $label,
                    "href"       => $href,
                    "is_visible" => 1,
                    "sort_order" => (int)($_POST["sort_order"] ?? 999),
                ]);
                flash_set("success", "Menu item “{$label}” added.");
                break;

            case "update_menu":
                $id = (int) $_POST["id"];
                $existing = MenuItem::find($id);
                if (!$existing) throw new RuntimeException("Menu item not found.");
                $label = trim((string)($_POST["label"] ?? ""));
                $href  = trim((string)($_POST["href"]  ?? ""));
                if ($label === "" || $href === "") {
                    throw new RuntimeException("Label and link are required.");
                }
                MenuItem::update($id, [
                    "label"      => $label,
                    "href"       => $href,
                    "sort_order" => (int)($_POST["sort_order"] ?? 999),
                ]);
                flash_set("success", "Menu item updated.");
                break;

            case "delete_menu":
                MenuItem::delete((int) $_POST["id"]);
                flash_set("success", "Menu item removed.");
                break;

            default:
                throw new RuntimeException("Unknown action.");
        }
    } catch (Throwable $e) {
        flash_set("error", $e->getMessage());
    }
    header("Location: /admin/sections.php?tab=" . urlencode($tab));
    exit;
}

$tab = $_GET["tab"] ?? "sections";
if (!in_array($tab, ["sections", "header", "footer"], true)) $tab = "sections";

$sections   = SiteSection::all();
$headerNav  = MenuItem::all("header");
$footerNav  = MenuItem::all("footer");
$editId     = isset($_GET["edit"]) ? (int) $_GET["edit"] : 0;
$editing    = $editId ? MenuItem::find($editId) : null;

admin_layout_start("Sections & Menus", "sections");
?>
<?= flash_render() ?>

<div class="flex items-center gap-2 mb-5">
    <a href="?tab=sections" class="btn <?= $tab === 'sections' ? 'btn-primary' : 'btn-ghost' ?>">
        <i class="fa-solid fa-layer-group"></i> Sections
    </a>
    <a href="?tab=header" class="btn <?= $tab === 'header' ? 'btn-primary' : 'btn-ghost' ?>">
        <i class="fa-solid fa-bars"></i> Header Menu
    </a>
    <a href="?tab=footer" class="btn <?= $tab === 'footer' ? 'btn-primary' : 'btn-ghost' ?>">
        <i class="fa-solid fa-shoe-prints"></i> Footer Menu
    </a>
</div>

<?php if ($tab === "sections"): ?>
    <div class="glass rounded-2xl p-6">
        <div class="flex items-center justify-between mb-4">
            <div>
                <h2 class="text-lg font-semibold">Frontend sections</h2>
                <p class="text-xs text-white/50 mt-1">Toggle individual sections on or off. Hidden sections are skipped entirely from the public page.</p>
            </div>
        </div>

        <ul class="divide-y divide-white/5">
            <?php foreach ($sections as $s): ?>
                <li class="py-3 flex items-center gap-4">
                    <div class="w-10 h-10 rounded-xl bg-white/5 flex items-center justify-center text-white/70">
                        <i class="fa-solid <?= htmlspecialchars($s["icon"] ?: "fa-square") ?>"></i>
                    </div>
                    <div class="flex-1 min-w-0">
                        <div class="font-semibold text-white"><?= htmlspecialchars($s["label"]) ?></div>
                        <div class="text-xs text-white/40">Key: <code class="text-white/60"><?= htmlspecialchars($s["key"]) ?></code></div>
                    </div>
                    <span class="badge <?= ((int)$s["is_visible"]) ? 'badge-success' : 'badge-warn' ?>">
                        <?= ((int)$s["is_visible"]) ? 'Visible' : 'Hidden' ?>
                    </span>
                    <form method="POST" class="inline">
                        <input type="hidden" name="_csrf" value="<?= htmlspecialchars(Csrf::token(), ENT_QUOTES) ?>">
                        <input type="hidden" name="action" value="toggle_section">
                        <input type="hidden" name="tab" value="sections">
                        <input type="hidden" name="key" value="<?= htmlspecialchars($s["key"]) ?>">
                        <input type="hidden" name="is_visible" value="<?= ((int)$s["is_visible"]) ? '0' : '1' ?>">
                        <button type="submit" class="text-2xl <?= ((int)$s["is_visible"]) ? 'text-emerald-400 hover:text-emerald-300' : 'text-white/30 hover:text-white/60' ?>" title="Toggle visibility">
                            <i class="fa-solid <?= ((int)$s["is_visible"]) ? 'fa-toggle-on' : 'fa-toggle-off' ?>"></i>
                        </button>
                    </form>
                </li>
            <?php endforeach; ?>
        </ul>
    </div>

<?php else:
    $location = $tab; // 'header' or 'footer'
    $items    = $location === "header" ? $headerNav : $footerNav;
?>
    <div class="grid lg:grid-cols-3 gap-5">
        <div class="lg:col-span-2 glass rounded-2xl p-6">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-lg font-semibold capitalize"><?= htmlspecialchars($location) ?> menu items</h2>
                <span class="text-xs text-white/50"><?= count($items) ?> items</span>
            </div>

            <ul class="divide-y divide-white/5">
                <?php foreach ($items as $m): ?>
                    <li class="py-3 flex items-center gap-3">
                        <div class="w-8 h-8 rounded-lg bg-white/5 flex items-center justify-center text-white/50 text-xs">
                            <?= (int) $m["sort_order"] ?>
                        </div>
                        <div class="flex-1 min-w-0">
                            <div class="font-semibold text-white truncate"><?= htmlspecialchars($m["label"]) ?></div>
                            <div class="text-xs text-white/40 truncate"><?= htmlspecialchars($m["href"]) ?></div>
                        </div>
                        <a href="?tab=<?= htmlspecialchars($location) ?>&edit=<?= (int)$m["id"] ?>" class="text-white/50 hover:text-white text-sm" title="Edit">
                            <i class="fa-solid fa-pen"></i>
                        </a>
                        <form method="POST" class="inline" onsubmit="return confirm('Delete “<?= htmlspecialchars(addslashes($m["label"])) ?>”?')">
                            <input type="hidden" name="_csrf" value="<?= htmlspecialchars(Csrf::token(), ENT_QUOTES) ?>">
                            <input type="hidden" name="action" value="delete_menu">
                            <input type="hidden" name="tab" value="<?= htmlspecialchars($location) ?>">
                            <input type="hidden" name="id" value="<?= (int)$m["id"] ?>">
                            <button class="text-white/50 hover:text-red-400 text-sm" title="Delete">
                                <i class="fa-solid fa-trash"></i>
                            </button>
                        </form>
                        <form method="POST" class="inline">
                            <input type="hidden" name="_csrf" value="<?= htmlspecialchars(Csrf::token(), ENT_QUOTES) ?>">
                            <input type="hidden" name="action" value="toggle_menu">
                            <input type="hidden" name="tab" value="<?= htmlspecialchars($location) ?>">
                            <input type="hidden" name="id" value="<?= (int)$m["id"] ?>">
                            <input type="hidden" name="is_visible" value="<?= ((int)$m["is_visible"]) ? '0' : '1' ?>">
                            <button type="submit" class="text-2xl <?= ((int)$m["is_visible"]) ? 'text-emerald-400 hover:text-emerald-300' : 'text-white/30 hover:text-white/60' ?>" title="Toggle visibility">
                                <i class="fa-solid <?= ((int)$m["is_visible"]) ? 'fa-toggle-on' : 'fa-toggle-off' ?>"></i>
                            </button>
                        </form>
                    </li>
                <?php endforeach; ?>
                <?php if (!$items): ?>
                    <li class="py-8 text-center text-white/40 text-sm">No menu items yet.</li>
                <?php endif; ?>
            </ul>
        </div>

        <div class="glass rounded-2xl p-6">
            <h2 class="text-lg font-semibold mb-3">
                <?= $editing ? "Edit menu item" : "Add menu item" ?>
            </h2>
            <form method="POST" class="space-y-3">
                <input type="hidden" name="_csrf" value="<?= htmlspecialchars(Csrf::token(), ENT_QUOTES) ?>">
                <input type="hidden" name="action" value="<?= $editing ? 'update_menu' : 'create_menu' ?>">
                <input type="hidden" name="tab" value="<?= htmlspecialchars($location) ?>">
                <input type="hidden" name="location" value="<?= htmlspecialchars($location) ?>">
                <?php if ($editing): ?>
                    <input type="hidden" name="id" value="<?= (int)$editing["id"] ?>">
                <?php endif; ?>

                <div>
                    <label class="label">Label</label>
                    <input class="input" name="label" required value="<?= htmlspecialchars($editing["label"] ?? "") ?>" placeholder="e.g. Reviews">
                </div>
                <div>
                    <label class="label">Link</label>
                    <input class="input" name="href" required value="<?= htmlspecialchars($editing["href"] ?? "#") ?>" placeholder="#reviews or /page.php">
                </div>
                <div>
                    <label class="label">Sort order</label>
                    <input class="input" type="number" name="sort_order" value="<?= htmlspecialchars((string)($editing["sort_order"] ?? 999)) ?>">
                </div>

                <div class="flex gap-2 pt-2">
                    <button class="btn btn-primary flex-1 justify-center">
                        <i class="fa-solid fa-save"></i> <?= $editing ? 'Save changes' : 'Add item' ?>
                    </button>
                    <?php if ($editing): ?>
                        <a href="?tab=<?= htmlspecialchars($location) ?>" class="btn btn-ghost">Cancel</a>
                    <?php endif; ?>
                </div>
            </form>
        </div>
    </div>
<?php endif; ?>

<?php admin_layout_end(); ?>
