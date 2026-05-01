<?php
declare(strict_types=1);

require __DIR__ . "/../bootstrap.php";
require __DIR__ . "/partials/layout.php";

use App\Skill;
use App\Csrf;

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    Csrf::require();
    $action = $_POST["action"] ?? "";
    try {
        if ($action === "create")      Skill::create($_POST);
        elseif ($action === "update")  Skill::update((int)$_POST["id"], $_POST);
        elseif ($action === "delete")  Skill::delete((int)$_POST["id"]);
        flash_set("success", "Saved.");
    } catch (Throwable $e) { flash_set("error", $e->getMessage()); }
    header("Location: /admin/skills.php" . (isset($_POST["kind"]) ? "?tab=" . $_POST["kind"] : "")); exit;
}

$tab = $_GET["tab"] ?? "creative";
$creative = Skill::all("creative");
$software = Skill::all("software");

admin_layout_start("Skills", "skills");
?>
<?= flash_render() ?>

<div class="flex gap-2 mb-4">
    <a href="?tab=creative" class="btn <?= $tab === 'creative' ? 'btn-primary' : 'btn-ghost' ?>"><i class="fa-solid fa-palette"></i> Creative Skills</a>
    <a href="?tab=software" class="btn <?= $tab === 'software' ? 'btn-primary' : 'btn-ghost' ?>"><i class="fa-solid fa-toolbox"></i> Software Toolkit</a>
</div>

<?php if ($tab === "creative"): ?>
    <div class="grid lg:grid-cols-3 gap-5">
        <div class="lg:col-span-2 glass rounded-2xl p-5">
            <h2 class="text-lg font-semibold mb-3">Creative Skill Tags</h2>
            <table class="data">
                <thead><tr><th>Name</th><th class="w-24">Sort</th><th class="w-32"></th></tr></thead>
                <tbody>
                <?php foreach ($creative as $s): ?>
                    <tr>
                        <form method="POST">
                            <input type="hidden" name="_csrf" value="<?= htmlspecialchars(Csrf::token(), ENT_QUOTES) ?>">
                            <input type="hidden" name="action" value="update">
                            <input type="hidden" name="id" value="<?= (int)$s["id"] ?>">
                            <input type="hidden" name="kind" value="creative">
                            <td><input class="input" name="name" value="<?= htmlspecialchars($s["name"]) ?>"></td>
                            <td><input class="input" type="number" name="sort_order" value="<?= (int)$s["sort_order"] ?>"></td>
                            <td class="text-right">
                                <button class="btn btn-ghost text-xs"><i class="fa-solid fa-save"></i></button>
                        </form>
                                <form method="POST" class="inline" onsubmit="return confirm('Delete?')">
                                    <input type="hidden" name="_csrf" value="<?= htmlspecialchars(Csrf::token(), ENT_QUOTES) ?>">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id" value="<?= (int)$s["id"] ?>">
                                    <input type="hidden" name="kind" value="creative">
                                    <button class="btn btn-danger text-xs"><i class="fa-solid fa-trash"></i></button>
                                </form>
                            </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <div class="glass rounded-2xl p-5">
            <h2 class="text-lg font-semibold mb-3"><i class="fa-solid fa-plus mr-1"></i> Add skill tag</h2>
            <form method="POST" class="space-y-3">
                <input type="hidden" name="_csrf" value="<?= htmlspecialchars(Csrf::token(), ENT_QUOTES) ?>">
                <input type="hidden" name="action" value="create">
                <input type="hidden" name="kind" value="creative">
                <div><label class="label">Skill name</label><input class="input" name="name" required></div>
                <div><label class="label">Sort order</label><input class="input" type="number" name="sort_order" value="0"></div>
                <button class="btn btn-primary w-full justify-center"><i class="fa-solid fa-plus"></i> Add</button>
            </form>
        </div>
    </div>
<?php else: ?>
    <div class="grid lg:grid-cols-3 gap-5">
        <div class="lg:col-span-2 glass rounded-2xl p-5">
            <h2 class="text-lg font-semibold mb-3">Software Toolkit Tiles</h2>
            <div class="grid sm:grid-cols-2 gap-3">
                <?php foreach ($software as $s): ?>
                    <form method="POST" class="rounded-xl p-3 border border-white/10 bg-black/20 space-y-2">
                        <input type="hidden" name="_csrf" value="<?= htmlspecialchars(Csrf::token(), ENT_QUOTES) ?>">
                        <input type="hidden" name="action" value="update">
                        <input type="hidden" name="id" value="<?= (int)$s["id"] ?>">
                        <input type="hidden" name="kind" value="software">
                        <div class="flex items-center gap-3">
                            <div class="w-12 h-12 rounded-lg flex items-center justify-center font-bold text-lg shadow"
                                style="background:<?= htmlspecialchars($s["bg"] ?: '#222') ?>;color:<?= htmlspecialchars($s["color"] ?: '#fff') ?>">
                                <?= htmlspecialchars($s["letters"] ?: substr($s["name"], 0, 2)) ?>
                            </div>
                            <input class="input" name="name" value="<?= htmlspecialchars($s["name"]) ?>">
                        </div>
                        <input class="input" name="tag" value="<?= htmlspecialchars($s["tag"]) ?>" placeholder="Discipline / tag">
                        <div class="grid grid-cols-3 gap-2">
                            <input class="input" name="letters" value="<?= htmlspecialchars($s["letters"]) ?>" placeholder="Ai" maxlength="3">
                            <input class="input" name="color" value="<?= htmlspecialchars($s["color"]) ?>" placeholder="#FF9A00">
                            <input class="input" name="bg" value="<?= htmlspecialchars($s["bg"]) ?>" placeholder="#330000">
                        </div>
                        <div class="grid grid-cols-2 gap-2">
                            <input class="input" type="number" name="sort_order" value="<?= (int)$s["sort_order"] ?>" placeholder="Sort">
                            <button class="btn btn-ghost text-sm"><i class="fa-solid fa-save"></i> Save</button>
                        </div>
                        <button formaction="/admin/skills.php" class="text-xs text-rose-300 hover:text-rose-200 mt-1" onclick="if(!confirm('Delete?'))return false;this.form.action.value='delete';">Delete</button>
                    </form>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="glass rounded-2xl p-5 lg:sticky lg:top-24 lg:self-start">
            <h2 class="text-lg font-semibold mb-3"><i class="fa-solid fa-plus mr-1"></i> Add tool</h2>
            <form method="POST" class="space-y-3">
                <input type="hidden" name="_csrf" value="<?= htmlspecialchars(Csrf::token(), ENT_QUOTES) ?>">
                <input type="hidden" name="action" value="create">
                <input type="hidden" name="kind" value="software">
                <div><label class="label">Name</label><input class="input" name="name" required placeholder="Adobe Photoshop"></div>
                <div><label class="label">Tag</label><input class="input" name="tag" placeholder="Photo Editing"></div>
                <div class="grid grid-cols-3 gap-2">
                    <div><label class="label">Letters</label><input class="input" name="letters" placeholder="Ps" maxlength="3"></div>
                    <div><label class="label">Color</label><input class="input" name="color" placeholder="#31A8FF"></div>
                    <div><label class="label">Bg</label><input class="input" name="bg" placeholder="#001E36"></div>
                </div>
                <div><label class="label">Sort</label><input class="input" type="number" name="sort_order" value="100"></div>
                <button class="btn btn-primary w-full justify-center"><i class="fa-solid fa-plus"></i> Add tool</button>
            </form>
        </div>
    </div>
<?php endif; ?>
<?php admin_layout_end(); ?>
