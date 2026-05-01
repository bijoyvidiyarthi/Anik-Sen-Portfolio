<?php
declare(strict_types=1);

require __DIR__ . "/../bootstrap.php";
require __DIR__ . "/partials/layout.php";

use App\Review;
use App\Csrf;

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    Csrf::require();
    $action = $_POST["action"] ?? "";
    try {
        if ($action === "create")     Review::create($_POST);
        elseif ($action === "update") Review::update((int)$_POST["id"], $_POST);
        elseif ($action === "delete") Review::delete((int)$_POST["id"]);
        flash_set("success", "Saved.");
    } catch (Throwable $e) { flash_set("error", $e->getMessage()); }
    header("Location: /admin/reviews.php"); exit;
}

$rows = Review::all();
admin_layout_start("Client Reviews", "reviews");
?>
<?= flash_render() ?>

<div class="grid lg:grid-cols-3 gap-5">
    <div class="lg:col-span-2 space-y-3">
        <?php foreach ($rows as $r): ?>
            <form method="POST" class="glass rounded-2xl p-4 grid sm:grid-cols-5 gap-3 items-start">
                <input type="hidden" name="_csrf" value="<?= htmlspecialchars(Csrf::token(), ENT_QUOTES) ?>">
                <input type="hidden" name="action" value="update">
                <input type="hidden" name="id" value="<?= (int)$r["id"] ?>">
                <div class="sm:col-span-2 space-y-2">
                    <input class="input" name="author" value="<?= htmlspecialchars($r["author"]) ?>" placeholder="Author">
                    <input class="input" name="role" value="<?= htmlspecialchars($r["role"]) ?>" placeholder="Role">
                    <input class="input" type="number" name="sort_order" value="<?= (int)$r["sort_order"] ?>" placeholder="Sort">
                </div>
                <div class="sm:col-span-3 space-y-2">
                    <textarea class="textarea" name="body" rows="4"><?= htmlspecialchars($r["body"]) ?></textarea>
                    <div class="flex gap-2">
                        <button class="btn btn-ghost text-xs"><i class="fa-solid fa-save"></i> Save</button>
                </div>
                </div>
            </form>
            <form method="POST" onsubmit="return confirm('Delete this review?')" class="-mt-3 mb-3 text-right">
                <input type="hidden" name="_csrf" value="<?= htmlspecialchars(Csrf::token(), ENT_QUOTES) ?>">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="id" value="<?= (int)$r["id"] ?>">
                <button class="text-xs text-rose-300 hover:text-rose-200"><i class="fa-solid fa-trash"></i> Delete</button>
            </form>
        <?php endforeach; ?>
        <?php if (!$rows): ?><div class="glass rounded-2xl p-6 text-center text-white/50">No reviews yet.</div><?php endif; ?>
    </div>

    <div class="glass rounded-2xl p-5 lg:sticky lg:top-24 lg:self-start">
        <h2 class="text-lg font-semibold mb-3"><i class="fa-solid fa-plus mr-1"></i> Add review</h2>
        <form method="POST" class="space-y-3">
            <input type="hidden" name="_csrf" value="<?= htmlspecialchars(Csrf::token(), ENT_QUOTES) ?>">
            <input type="hidden" name="action" value="create">
            <div><label class="label">Author</label><input class="input" name="author" required></div>
            <div><label class="label">Role</label><input class="input" name="role" required></div>
            <div><label class="label">Review text</label><textarea class="textarea" name="body" rows="4" required></textarea></div>
            <div><label class="label">Sort</label><input class="input" type="number" name="sort_order" value="100"></div>
            <button class="btn btn-primary w-full justify-center"><i class="fa-solid fa-plus"></i> Add</button>
        </form>
    </div>
</div>
<?php admin_layout_end(); ?>
