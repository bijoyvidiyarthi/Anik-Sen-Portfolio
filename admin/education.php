<?php
declare(strict_types=1);

require __DIR__ . "/../bootstrap.php";
require __DIR__ . "/partials/layout.php";

use App\Education;
use App\Csrf;

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    Csrf::require();
    $action = $_POST["action"] ?? "";
    try {
        if ($action === "create")     Education::create($_POST);
        elseif ($action === "update") Education::update((int)$_POST["id"], $_POST);
        elseif ($action === "delete") Education::delete((int)$_POST["id"]);
        flash_set("success", "Saved.");
    } catch (Throwable $e) { flash_set("error", $e->getMessage()); }
    header("Location: /admin/education.php"); exit;
}

$rows = Education::all();
admin_layout_start("Education", "education");
?>
<?= flash_render() ?>

<div class="grid lg:grid-cols-3 gap-5">
    <div class="lg:col-span-2 glass rounded-2xl p-5">
        <h2 class="text-lg font-semibold mb-3">Education entries</h2>
        <table class="data">
            <thead><tr><th>Year</th><th>Degree</th><th>Status</th><th class="w-20">Sort</th><th class="w-28"></th></tr></thead>
            <tbody>
            <?php foreach ($rows as $e): ?>
                <tr>
                    <form method="POST">
                        <input type="hidden" name="_csrf" value="<?= htmlspecialchars(Csrf::token(), ENT_QUOTES) ?>">
                        <input type="hidden" name="action" value="update">
                        <input type="hidden" name="id" value="<?= (int)$e["id"] ?>">
                        <td><input class="input" name="year" value="<?= htmlspecialchars($e["year"]) ?>"></td>
                        <td><input class="input" name="degree" value="<?= htmlspecialchars($e["degree"]) ?>"></td>
                        <td><input class="input" name="status" value="<?= htmlspecialchars($e["status"]) ?>"></td>
                        <td><input class="input" type="number" name="sort_order" value="<?= (int)$e["sort_order"] ?>"></td>
                        <td class="text-right"><button class="btn btn-ghost text-xs"><i class="fa-solid fa-save"></i></button>
                    </form>
                        <form method="POST" class="inline" onsubmit="return confirm('Delete?')">
                            <input type="hidden" name="_csrf" value="<?= htmlspecialchars(Csrf::token(), ENT_QUOTES) ?>">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="id" value="<?= (int)$e["id"] ?>">
                            <button class="btn btn-danger text-xs"><i class="fa-solid fa-trash"></i></button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (!$rows): ?><tr><td colspan="5" class="text-center text-white/40 py-5">No entries.</td></tr><?php endif; ?>
            </tbody>
        </table>
    </div>

    <div class="glass rounded-2xl p-5">
        <h2 class="text-lg font-semibold mb-3"><i class="fa-solid fa-plus mr-1"></i> Add entry</h2>
        <form method="POST" class="space-y-3">
            <input type="hidden" name="_csrf" value="<?= htmlspecialchars(Csrf::token(), ENT_QUOTES) ?>">
            <input type="hidden" name="action" value="create">
            <div><label class="label">Year / period</label><input class="input" name="year" required placeholder="2024 - Present"></div>
            <div><label class="label">Degree / program</label><input class="input" name="degree" required></div>
            <div><label class="label">Status</label><input class="input" name="status" required placeholder="Currently Pursuing"></div>
            <div><label class="label">Sort order</label><input class="input" type="number" name="sort_order" value="100"></div>
            <button class="btn btn-primary w-full justify-center"><i class="fa-solid fa-plus"></i> Add</button>
        </form>
    </div>
</div>
<?php admin_layout_end(); ?>
