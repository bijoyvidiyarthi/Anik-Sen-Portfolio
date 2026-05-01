<?php
declare(strict_types=1);

require __DIR__ . "/../bootstrap.php";
require __DIR__ . "/partials/layout.php";

use App\Client;
use App\Csrf;
use App\Upload;

$uploadDir = __DIR__ . "/../uploads/images";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    Csrf::require();
    $action = $_POST["action"] ?? "";
    try {
        if ($action === "create") {
            $logo = isset($_FILES["logo"]) && ($_FILES["logo"]["error"] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_OK
                ? Upload::image($_FILES["logo"], $uploadDir)
                : (string)($_POST["logo_url"] ?? "");
            Client::create($_POST, $logo);
        } elseif ($action === "update") {
            $id = (int)$_POST["id"];
            $logo = null;
            if (isset($_FILES["logo"]) && ($_FILES["logo"]["error"] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_OK) {
                $existing = Client::find($id);
                if ($existing && $existing["logo"] && strpos((string)$existing["logo"], "://") === false) {
                    Upload::delete($uploadDir, (string)$existing["logo"]);
                }
                $logo = Upload::image($_FILES["logo"], $uploadDir);
            } elseif (!empty($_POST["logo_url"])) {
                $logo = (string)$_POST["logo_url"];
            }
            Client::update($id, $_POST, $logo);
        } elseif ($action === "toggle") {
            Client::toggleVisibility((int)$_POST["id"]);
        } elseif ($action === "delete") {
            $logo = Client::delete((int)$_POST["id"]);
            if ($logo && strpos($logo, "://") === false) {
                Upload::delete($uploadDir, $logo);
            }
        }
        flash_set("success", "Saved.");
    } catch (Throwable $e) {
        flash_set("error", $e->getMessage());
    }
    header("Location: /admin/clients.php"); exit;
}

$rows = Client::all();
admin_layout_start("Trusted Clients", "clients");

$resolveLogo = static function (string $logo): string {
    if ($logo === "") return "";
    if (strpos($logo, "://") !== false) return $logo;
    return "/uploads/images/" . htmlspecialchars($logo);
};
?>
<?= flash_render() ?>

<div class="grid lg:grid-cols-3 gap-5">
    <div class="lg:col-span-2 space-y-3">
        <?php if (!$rows): ?>
            <div class="glass rounded-2xl p-8 text-center text-white/50">
                <i class="fa-solid fa-handshake text-4xl mb-3 opacity-50"></i>
                <p>No clients yet — add your first one on the right.</p>
            </div>
        <?php endif; ?>

        <?php foreach ($rows as $r): ?>
            <div class="glass rounded-2xl p-4">
                <form method="POST" enctype="multipart/form-data" class="grid sm:grid-cols-12 gap-3 items-center">
                    <input type="hidden" name="_csrf" value="<?= htmlspecialchars(Csrf::token(), ENT_QUOTES) ?>">
                    <input type="hidden" name="action" value="update">
                    <input type="hidden" name="id" value="<?= (int)$r["id"] ?>">

                    <div class="sm:col-span-2 flex items-center justify-center">
                        <div class="w-20 h-20 rounded-xl bg-white/10 border border-white/10 flex items-center justify-center overflow-hidden">
                            <?php if ($r["logo"]): ?>
                                <img src="<?= $resolveLogo((string)$r["logo"]) ?>" alt="<?= htmlspecialchars($r["name"]) ?>" class="max-w-full max-h-full object-contain">
                            <?php else: ?>
                                <i class="fa-regular fa-image text-3xl text-white/30"></i>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="sm:col-span-4 space-y-2">
                        <div><label class="label">Client name</label>
                            <input class="input" name="name" value="<?= htmlspecialchars($r["name"]) ?>" required></div>
                        <div><label class="label">Website (optional)</label>
                            <input class="input" name="link_url" value="<?= htmlspecialchars((string)$r["link_url"]) ?>" placeholder="https://"></div>
                    </div>

                    <div class="sm:col-span-3 space-y-2">
                        <div><label class="label">Replace logo (file)</label>
                            <input class="input" type="file" name="logo" accept="image/*"></div>
                        <div><label class="label">…or paste image URL</label>
                            <input class="input text-xs" name="logo_url" placeholder="https://logo.clearbit.com/…"></div>
                    </div>

                    <div class="sm:col-span-2 space-y-2">
                        <div><label class="label">Sort</label>
                            <input class="input" type="number" name="sort_order" value="<?= (int)$r["sort_order"] ?>"></div>
                        <label class="flex items-center gap-2 mt-1 cursor-pointer">
                            <input type="checkbox" name="is_visible" value="1" <?= $r["is_visible"] ? "checked" : "" ?>>
                            <span class="text-xs text-white/70">Visible on site</span>
                        </label>
                    </div>

                    <div class="sm:col-span-1 flex flex-col items-stretch gap-2">
                        <button class="btn btn-primary text-xs" title="Save"><i class="fa-solid fa-save"></i></button>
                    </div>
                </form>

                <div class="flex flex-wrap items-center justify-between gap-2 mt-3 pt-3 border-t border-white/5">
                    <div class="flex items-center gap-2">
                        <span class="badge <?= $r["is_visible"] ? 'badge-success' : 'badge-warn' ?>">
                            <i class="fa-solid <?= $r["is_visible"] ? 'fa-eye' : 'fa-eye-slash' ?>"></i>
                            <?= $r["is_visible"] ? "Visible" : "Hidden" ?>
                        </span>
                        <?php if ($r["link_url"]): ?>
                            <a href="<?= htmlspecialchars((string)$r["link_url"]) ?>" target="_blank" class="text-xs text-white/60 hover:text-white">
                                <i class="fa-solid fa-up-right-from-square mr-1"></i><?= htmlspecialchars((string)$r["link_url"]) ?>
                            </a>
                        <?php endif; ?>
                    </div>
                    <div class="flex items-center gap-2">
                        <form method="POST" class="inline">
                            <input type="hidden" name="_csrf" value="<?= htmlspecialchars(Csrf::token(), ENT_QUOTES) ?>">
                            <input type="hidden" name="action" value="toggle">
                            <input type="hidden" name="id" value="<?= (int)$r["id"] ?>">
                            <button class="btn btn-ghost text-xs" title="Toggle visibility">
                                <i class="fa-solid <?= $r["is_visible"] ? 'fa-eye-slash' : 'fa-eye' ?>"></i>
                                <?= $r["is_visible"] ? "Hide" : "Show" ?>
                            </button>
                        </form>
                        <form method="POST" class="inline" data-confirm-form>
                            <input type="hidden" name="_csrf" value="<?= htmlspecialchars(Csrf::token(), ENT_QUOTES) ?>">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="id" value="<?= (int)$r["id"] ?>">
                            <button class="btn btn-danger text-xs" data-confirm="Delete <?= htmlspecialchars($r["name"]) ?>?">
                                <i class="fa-solid fa-trash"></i> Delete
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <div class="glass rounded-2xl p-5 lg:sticky lg:top-24 lg:self-start">
        <h2 class="text-lg font-semibold mb-3"><i class="fa-solid fa-plus mr-1"></i> Add client</h2>
        <form method="POST" enctype="multipart/form-data" class="space-y-3">
            <input type="hidden" name="_csrf" value="<?= htmlspecialchars(Csrf::token(), ENT_QUOTES) ?>">
            <input type="hidden" name="action" value="create">
            <div><label class="label">Client name</label><input class="input" name="name" required placeholder="Acme Corp"></div>
            <div><label class="label">Website (optional)</label><input class="input" name="link_url" placeholder="https://acme.com"></div>
            <div>
                <label class="label">Logo upload</label>
                <input class="input" type="file" name="logo" accept="image/*">
                <p class="text-[11px] text-white/40 mt-1">Or paste a hosted URL below.</p>
            </div>
            <div><label class="label">Logo URL (alternative)</label><input class="input text-xs" name="logo_url" placeholder="https://logo.clearbit.com/acme.com"></div>
            <div><label class="label">Sort order</label><input class="input" type="number" name="sort_order" value="100"></div>
            <label class="flex items-center gap-2 cursor-pointer">
                <input type="checkbox" name="is_visible" value="1" checked>
                <span class="text-sm">Visible on site</span>
            </label>
            <button class="btn btn-primary w-full justify-center"><i class="fa-solid fa-plus"></i> Add Client</button>
        </form>
    </div>
</div>
<?php admin_layout_end(); ?>
