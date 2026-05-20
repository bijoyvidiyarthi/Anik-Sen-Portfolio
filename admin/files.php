<?php
declare(strict_types=1);

require __DIR__ . "/../bootstrap.php";
require __DIR__ . "/partials/layout.php";

use App\FileLibrary;
use App\Csrf;
use App\Upload;
use App\Settings;

$config = $GLOBALS["APP_CONFIG"];

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    Csrf::require();
    $action = $_POST["action"] ?? "";
    try {
        if ($action === "upload") {
            if (empty($_FILES["file"]["name"])) throw new RuntimeException("No file selected.");
            $stored = Upload::any($_FILES["file"], $config["paths"]["doc_dir"]);
            FileLibrary::create([
                "title"         => trim((string)($_POST["title"] ?? $_FILES["file"]["name"])),
                "folder"        => trim((string)($_POST["folder"] ?? "general")) ?: "general",
                "filename"      => $stored,
                "original_name" => (string) $_FILES["file"]["name"],
                "mime"          => (string) ($_FILES["file"]["type"] ?? ""),
                "size_bytes"    => (int) ($_FILES["file"]["size"] ?? 0),
                "description"   => (string) ($_POST["description"] ?? ""),
                "is_active"     => !empty($_POST["is_active"]),
            ]);
            flash_set("success", "File uploaded.");
        } elseif ($action === "update") {
            FileLibrary::update((int)$_POST["id"], $_POST);
            flash_set("success", "File updated.");
        } elseif ($action === "delete") {
            FileLibrary::delete((int)$_POST["id"], $config["paths"]["doc_dir"]);
            flash_set("success", "File deleted.");
        } elseif ($action === "set_active_cv") {
            // Mark this file as active CV; un-mark others in the cv folder.
            \App\Database::pdo()->exec("UPDATE file_library SET is_active = 0 WHERE folder = 'cv'");
            \App\Database::pdo()->prepare("UPDATE file_library SET folder = 'cv', is_active = 1 WHERE id = :id")
                ->execute([":id" => (int)$_POST["id"]]);
            flash_set("success", "Active CV updated. The Download CV button now serves this file.");
        }
    } catch (Throwable $e) {
        flash_set("error", $e->getMessage());
    }
    header("Location: /admin/files.php" . (isset($_GET["folder"]) ? "?folder=" . urlencode((string)$_GET["folder"]) : "")); exit;
}

$folderFilter = isset($_GET["folder"]) ? (string)$_GET["folder"] : "";
$search = isset($_GET["q"]) ? (string)$_GET["q"] : "";
$folders = FileLibrary::folders();
$files = FileLibrary::all($folderFilter !== "" ? $folderFilter : null, $search);

admin_layout_start("File Library", "files");
?>
<?= flash_render() ?>

<div class="grid lg:grid-cols-4 gap-5">
    <div class="glass rounded-2xl p-5">
        <h2 class="text-lg font-semibold mb-3"><i class="fa-solid fa-folder-tree mr-1"></i> Folders</h2>
        <ul class="space-y-1 mb-4">
            <li><a href="/admin/files.php" class="nav-link <?= $folderFilter === '' ? 'active' : '' ?>">
                <i class="fa-solid fa-layer-group"></i> All files
                <span class="ml-auto text-xs text-white/50"><?= count(FileLibrary::all()) ?></span>
            </a></li>
            <?php foreach ($folders as $f): ?>
                <li><a href="?folder=<?= urlencode($f["folder"]) ?>" class="nav-link <?= $folderFilter === $f["folder"] ? 'active' : '' ?>">
                    <i class="fa-regular fa-folder"></i>
                    <span class="flex-1 truncate"><?= htmlspecialchars($f["folder"]) ?></span>
                    <span class="text-xs text-white/50"><?= (int)$f["n"] ?></span>
                </a></li>
            <?php endforeach; ?>
        </ul>

        <form method="POST" enctype="multipart/form-data" class="space-y-2 border-t border-white/5 pt-3">
            <input type="hidden" name="_csrf" value="<?= htmlspecialchars(Csrf::token(), ENT_QUOTES) ?>">
            <input type="hidden" name="action" value="upload">
            <div class="text-xs font-semibold text-white/70">+ Upload file</div>
            <input class="input text-sm" name="title" placeholder="Title (optional)">
            <input class="input text-sm" name="folder" placeholder="Folder (cv, general, briefs...)" value="<?= htmlspecialchars($folderFilter ?: 'general') ?>">
            <input class="input text-sm" name="description" placeholder="Description (optional)">
            <input class="input text-sm" type="file" name="file" required>
            <label class="flex items-center gap-2 text-xs"><input type="checkbox" name="is_active" value="1" checked> Active</label>
            <button class="btn btn-primary w-full justify-center text-sm"><i class="fa-solid fa-upload"></i> Upload</button>
        </form>
    </div>

    <div class="lg:col-span-3 glass rounded-2xl p-5">
        <form class="flex gap-2 mb-4" method="GET">
            <?php if ($folderFilter): ?><input type="hidden" name="folder" value="<?= htmlspecialchars($folderFilter) ?>"><?php endif; ?>
            <input class="input flex-1" name="q" placeholder="Search files..." value="<?= htmlspecialchars($search) ?>">
            <button class="btn btn-ghost"><i class="fa-solid fa-magnifying-glass"></i></button>
        </form>

        <?php $activeCv = FileLibrary::activeCv(); ?>
        <?php if ($activeCv): ?>
            <div class="alert alert-info mb-3">
                <i class="fa-solid fa-file-circle-check mr-1"></i>
                Active CV served at <code>/cv.php</code>: <strong><?= htmlspecialchars($activeCv["title"]) ?></strong>
            </div>
        <?php endif; ?>

        <table class="data">
            <thead><tr><th>Title</th><th>Folder</th><th>Size</th><th>Uploaded</th><th>Status</th><th></th></tr></thead>
            <tbody>
            <?php foreach ($files as $f): ?>
                <tr>
                    <td>
                        <div class="font-medium text-white text-sm"><?= htmlspecialchars($f["title"]) ?></div>
                        <div class="text-xs text-white/50 truncate max-w-md"><?= htmlspecialchars($f["original_name"]) ?></div>
                    </td>
                    <td><span class="badge badge-info"><?= htmlspecialchars($f["folder"]) ?></span></td>
                    <td class="text-xs text-white/70"><?= Upload::humanSize((int)$f["size_bytes"]) ?></td>
                    <td class="text-xs text-white/60"><?= htmlspecialchars(date("M j, Y", strtotime((string)$f["created_at"]))) ?></td>
                    <td>
                        <?php if ($f["is_active"]): ?>
                            <span class="badge badge-success"><i class="fa-solid fa-check"></i> Active</span>
                        <?php else: ?>
                            <span class="badge badge-warn">Inactive</span>
                        <?php endif; ?>
                    </td>
                    <td class="text-right">
                        <div class="flex gap-1 justify-end">
                            <a class="btn btn-ghost text-xs" href="/uploads/docs/<?= htmlspecialchars($f["filename"]) ?>" target="_blank" title="Download"><i class="fa-solid fa-download"></i></a>
                            <button onclick="document.getElementById('edit-<?= (int)$f['id'] ?>').classList.toggle('hidden')" class="btn btn-ghost text-xs"><i class="fa-solid fa-pen"></i></button>
                            <form method="POST" class="inline">
                                <input type="hidden" name="_csrf" value="<?= htmlspecialchars(Csrf::token(), ENT_QUOTES) ?>">
                                <input type="hidden" name="action" value="set_active_cv">
                                <input type="hidden" name="id" value="<?= (int)$f["id"] ?>">
                                <button class="btn btn-ghost text-xs" title="Use as active CV"><i class="fa-solid fa-star"></i></button>
                            </form>
                            <form method="POST" class="inline" onsubmit="return confirm('Delete this file?')">
                                <input type="hidden" name="_csrf" value="<?= htmlspecialchars(Csrf::token(), ENT_QUOTES) ?>">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?= (int)$f["id"] ?>">
                                <button class="btn btn-danger text-xs"><i class="fa-solid fa-trash"></i></button>
                            </form>
                        </div>
                    </td>
                </tr>
                <tr id="edit-<?= (int)$f['id'] ?>" class="hidden">
                    <td colspan="6">
                        <form method="POST" class="grid sm:grid-cols-5 gap-2 p-2 rounded-lg bg-black/30">
                            <input type="hidden" name="_csrf" value="<?= htmlspecialchars(Csrf::token(), ENT_QUOTES) ?>">
                            <input type="hidden" name="action" value="update">
                            <input type="hidden" name="id" value="<?= (int)$f["id"] ?>">
                            <input class="input text-xs" name="title" value="<?= htmlspecialchars($f["title"]) ?>">
                            <input class="input text-xs" name="folder" value="<?= htmlspecialchars($f["folder"]) ?>">
                            <input class="input text-xs sm:col-span-2" name="description" value="<?= htmlspecialchars($f["description"] ?? '') ?>" placeholder="Description">
                            <div class="flex gap-1 items-center">
                                <label class="flex items-center gap-1 text-xs"><input type="checkbox" name="is_active" value="1" <?= $f["is_active"] ? 'checked' : '' ?>> Active</label>
                                <button class="btn btn-primary text-xs"><i class="fa-solid fa-save"></i></button>
                            </div>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (!$files): ?><tr><td colspan="6" class="text-center text-white/40 py-8">No files yet.</td></tr><?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?php admin_layout_end(); ?>
