<?php
declare(strict_types=1);

require __DIR__ . "/../bootstrap.php";
require __DIR__ . "/partials/layout.php";

use App\About;
use App\Csrf;
use App\Upload;

$config = $GLOBALS["APP_CONFIG"];

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    Csrf::require();
    $action = $_POST["action"] ?? "";
    try {
        if ($action === "save_bio") {
            $img = null;
            $current = About::get();
            if (!empty($_FILES["profile_image"]["name"])) {
                $img = Upload::image($_FILES["profile_image"], $config["paths"]["image_dir"]);
                Upload::delete($config["paths"]["image_dir"], $current["profile_image"] ?? "");
            }
            About::update((string)$_POST["bio"], $img);
            flash_set("success", "Bio saved.");
        } elseif ($action === "exp_create") {
            About::createExpertise(
                (string)$_POST["icon"], (string)$_POST["title"],
                (string)$_POST["description"], (int)($_POST["sort_order"] ?? 0)
            );
            flash_set("success", "Expertise added.");
        } elseif ($action === "exp_update") {
            About::updateExpertise(
                (int)$_POST["id"],
                (string)$_POST["icon"], (string)$_POST["title"],
                (string)$_POST["description"], (int)($_POST["sort_order"] ?? 0)
            );
            flash_set("success", "Expertise updated.");
        } elseif ($action === "exp_delete") {
            About::deleteExpertise((int)$_POST["id"]);
            flash_set("success", "Expertise removed.");
        }
    } catch (Throwable $e) {
        flash_set("error", $e->getMessage());
    }
    header("Location: /admin/about.php"); exit;
}

$row = About::get();
$exp = About::expertise();
$icons = ["image","palette","video","camera","pen","star"];
admin_layout_start("About / Bio", "about");
?>
<?= flash_render() ?>

<form method="POST" enctype="multipart/form-data" class="glass rounded-2xl p-6 mb-6 grid lg:grid-cols-3 gap-5">
    <input type="hidden" name="_csrf" value="<?= htmlspecialchars(Csrf::token(), ENT_QUOTES) ?>">
    <input type="hidden" name="action" value="save_bio">
    <div class="lg:col-span-2">
        <label class="label">About / Bio paragraphs</label>
        <textarea name="bio" rows="10" class="textarea"><?= htmlspecialchars($row["bio"] ?? "") ?></textarea>
        <div class="text-xs text-white/40 mt-1">Separate paragraphs with a blank line.</div>
    </div>
    <div>
        <label class="label">Profile image</label>
        <?php if (!empty($row["profile_image"])): ?>
            <div class="rounded-lg p-2 bg-black/30 border border-white/10 mb-2 flex justify-center">
                <img src="/uploads/images/<?= htmlspecialchars($row["profile_image"]) ?>" class="max-h-40 rounded">
            </div>
        <?php endif; ?>
        <input type="file" name="profile_image" accept="image/*" class="input mb-3">
        <button class="btn btn-primary w-full justify-center"><i class="fa-solid fa-save"></i> Save Bio</button>
    </div>
</form>

<div class="glass rounded-2xl p-6">
    <div class="flex items-center justify-between mb-4">
        <h2 class="text-lg font-semibold"><i class="fa-solid fa-stars text-amber-400 mr-1"></i> Core Expertise</h2>
    </div>

    <form method="POST" class="grid sm:grid-cols-5 gap-3 mb-5 items-end">
        <input type="hidden" name="_csrf" value="<?= htmlspecialchars(Csrf::token(), ENT_QUOTES) ?>">
        <input type="hidden" name="action" value="exp_create">
        <div><label class="label">Icon</label>
            <select name="icon" class="select">
                <?php foreach ($icons as $i): ?><option value="<?= $i ?>"><?= $i ?></option><?php endforeach; ?>
            </select>
        </div>
        <div><label class="label">Title</label><input class="input" name="title" required></div>
        <div class="sm:col-span-2"><label class="label">Description</label><input class="input" name="description" required></div>
        <div><label class="label">Sort</label><input class="input" type="number" name="sort_order" value="0"></div>
        <div class="sm:col-span-5"><button class="btn btn-primary"><i class="fa-solid fa-plus"></i> Add expertise</button></div>
    </form>

    <table class="data">
        <thead><tr><th>Icon</th><th>Title</th><th>Description</th><th class="w-24">Sort</th><th class="w-32"></th></tr></thead>
        <tbody>
        <?php foreach ($exp as $e): ?>
            <tr>
                <form method="POST">
                    <input type="hidden" name="_csrf" value="<?= htmlspecialchars(Csrf::token(), ENT_QUOTES) ?>">
                    <input type="hidden" name="action" value="exp_update">
                    <input type="hidden" name="id" value="<?= (int)$e["id"] ?>">
                    <td>
                        <select name="icon" class="select">
                            <?php foreach ($icons as $i): ?>
                                <option value="<?= $i ?>" <?= $e["icon"] === $i ? "selected" : "" ?>><?= $i ?></option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                    <td><input class="input" name="title" value="<?= htmlspecialchars($e["title"]) ?>"></td>
                    <td><input class="input" name="description" value="<?= htmlspecialchars($e["description"]) ?>"></td>
                    <td><input class="input" type="number" name="sort_order" value="<?= (int)$e["sort_order"] ?>"></td>
                    <td class="text-right">
                        <button class="btn btn-ghost text-xs"><i class="fa-solid fa-save"></i></button>
                </form>
                        <form method="POST" class="inline" onsubmit="return confirm('Delete this item?')">
                            <input type="hidden" name="_csrf" value="<?= htmlspecialchars(Csrf::token(), ENT_QUOTES) ?>">
                            <input type="hidden" name="action" value="exp_delete">
                            <input type="hidden" name="id" value="<?= (int)$e["id"] ?>">
                            <button class="btn btn-danger text-xs"><i class="fa-solid fa-trash"></i></button>
                        </form>
                    </td>
            </tr>
        <?php endforeach; ?>
        <?php if (!$exp): ?><tr><td colspan="5" class="text-center text-white/40 py-5">No expertise items yet.</td></tr><?php endif; ?>
        </tbody>
    </table>
</div>
<?php admin_layout_end(); ?>
