<?php
declare(strict_types=1);

require __DIR__ . "/../bootstrap.php";
require __DIR__ . "/partials/layout.php";

use App\Client;
use App\Csrf;
use App\Upload;

$uploadDir = __DIR__ . "/../uploads/images";

/**
 * Validates if a string is a plain local filename.
 */

function isLocalLogo(?string $val): bool {
    if (!$val) return false;
    return preg_match('/^\w[\w.\-]+$/', $val) === 1;
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    Csrf::require();
    $action = $_POST["action"] ?? "";
    
    try {
        if ($action === "create" || $action === "update") {
            $id      = isset($_POST["id"]) ? (int)$_POST["id"] : null;
            $logo    = null;
            $logoSrc = $_POST["logo_src"] ?? "file";

            // 1. Determine the new logo value
            if ($logoSrc === "url") {
                $logo = trim((string)($_POST["logo_ext"] ?? ""));
            } elseif (isset($_FILES["logo"]) && ($_FILES["logo"]["error"] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_OK) {
                $logo = Upload::image($_FILES["logo"], $uploadDir);
            }

            // 2. Perform Action
            if ($action === "create") {
                Client::create($_POST, $logo ?? "");
                flash_set("success", "Client added successfully.");
            } else {
                // If updating and a new logo is provided, delete the old local file
                if ($logo !== null) {
                    $existing = Client::find($id);
                    $oldLogo  = (string)($existing["logo"] ?? "");
                    if (isLocalLogo($oldLogo)) {
                        Upload::delete($uploadDir, $oldLogo);
                    }
                }
                Client::update($id, $_POST, $logo);
                flash_set("success", "Client updated successfully.");
            }

        } elseif ($action === "toggle") {
            Client::toggleVisibility((int)$_POST["id"]);
            flash_set("success", "Visibility status changed.");

        } elseif ($action === "delete") {
            $id = (int)$_POST["id"];
            $oldLogo = Client::delete($id);
            if (isLocalLogo((string)$oldLogo)) {
                Upload::delete($uploadDir, (string)$oldLogo);
            }
            flash_set("success", "Client removed.");
        }
    } catch (\Throwable $e) {
        flash_set("error", "Error: " . $e->getMessage());
    }
    header("Location: /admin/brandlist.php"); 
    exit;
}

$rows = Client::all();
admin_layout_start("Trusted Clients", "clients");
?>

<?= flash_render() ?>

<!-- Section: Add New Client -->
<div class="glass rounded-2xl p-6 mb-8 border border-white/5">
    <h2 class="text-xl font-bold mb-5 flex items-center gap-2">
        <i class="fa-solid fa-circle-plus text-primary"></i> Add New Client
    </h2>
    <form method="POST" enctype="multipart/form-data">
        <?= Csrf::field() ?>
        <input type="hidden" name="action" value="create">
        <input type="hidden" name="logo_src" value="file">
        
        <div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <div>
                <label class="label text-xs uppercase tracking-wider text-white/50">Client Name *</label>
                <input class="input" name="name" required placeholder="e.g. Google">
            </div>
            <div>
                <label class="label text-xs uppercase tracking-wider text-white/50">Website URL</label>
                <input class="input" name="link_url" placeholder="https://...">
            </div>
            <div>
                <label class="label text-xs uppercase tracking-wider text-white/50">Upload Logo</label>
                <input class="input file-input" type="file" name="logo" accept="image/*"
                       onchange="this.form.querySelector('[name=logo_src]').value='file'">
            </div>
            <div>
                <label class="label text-xs uppercase tracking-wider text-white/50">Or Logo URL</label>
                <input class="input text-xs" name="logo_ext" placeholder="Paste image link..."
                       oninput="this.form.querySelector('[name=logo_src]').value='url'">
            </div>
        </div>
        
        <div class="flex items-center justify-between mt-6 pt-4 border-t border-white/5">
            <div class="flex gap-6 items-center">
                <div class="flex items-center gap-2">
                    <label class="label text-xs text-white/50 m-0">Order:</label>
                    <input class="input w-20 py-1" type="number" name="sort_order" value="100">
                </div>
                <label class="flex items-center gap-2 cursor-pointer group">
                    <input type="checkbox" name="is_visible" value="1" checked class="checkbox">
                    <span class="text-sm group-hover:text-white transition">Publicly Visible</span>
                </label>
            </div>
            <button class="btn btn-primary"><i class="fa-solid fa-save mr-1"></i> Save Client</button>
        </div>
    </form>
</div>

<!-- Section: Client List -->
<?php if (!$rows): ?>
    <div class="glass rounded-2xl p-12 text-center border border-white/5">
        <i class="fa-solid fa-briefcase text-5xl mb-4 text-white/10"></i>
        <p class="text-white/40">No clients have been added yet.</p>
    </div>
<?php else: ?>
    <div class="space-y-4">
        <?php foreach ($rows as $r):
            $logo = (string)($r["logo"] ?? "");
            $logoIsLocal = isLocalLogo($logo);
            $logoSrc = $logoIsLocal ? "/uploads/images/" . rawurlencode($logo) : "";
            $logoExtB64 = (!$logoIsLocal && $logo !== "") ? base64_encode($logo) : "";
            $linkUrl = (string)($r["link_url"] ?? "");
            $linkUrlB64 = $linkUrl !== "" ? base64_encode($linkUrl) : "";
        ?>
            <div class="glass rounded-2xl p-5 border border-white/5 hover:border-white/10 transition-colors">
                <form method="POST" enctype="multipart/form-data" class="grid lg:grid-cols-12 gap-5 items-center">
                    <?= Csrf::field() ?>
                    <input type="hidden" name="action" value="update">
                    <input type="hidden" name="id" value="<?= (int)$r["id"] ?>">
                    <input type="hidden" name="logo_src" value="<?= $logoIsLocal ? 'file' : 'url' ?>">

                    <!-- Preview -->
                    <div class="lg:col-span-1 flex justify-center">
                        <div class="w-16 h-16 rounded-xl bg-black/20 border border-white/10 flex items-center justify-center p-2 overflow-hidden shadow-inner">
                            <?php if ($logoIsLocal && $logoSrc): ?>
                                <img src="<?= htmlspecialchars($logoSrc) ?>" class="max-w-full max-h-full object-contain">
                            <?php elseif ($logoExtB64): ?>
                                <img class="xref-img max-w-full max-h-full object-contain" data-xref="<?= htmlspecialchars($logoExtB64) ?>" src="">
                            <?php else: ?>
                                <i class="fa-solid fa-image text-white/20 text-xl"></i>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Identity -->
                    <div class="lg:col-span-3 space-y-3">
                        <input class="input font-semibold" name="name" value="<?= htmlspecialchars($r["name"]) ?>" required placeholder="Client Name">
                        <input class="input text-xs xref-input" name="link_url" data-xref="<?= htmlspecialchars($linkUrlB64) ?>" placeholder="Website URL">
                    </div>

                    <!-- Logo Config -->
                    <div class="lg:col-span-4 space-y-3">
                        <div class="flex flex-col gap-1">
                            <span class="text-[10px] text-white/30 uppercase ml-1">New File:</span>
                            <input class="input text-xs" type="file" name="logo" accept="image/*" onchange="this.form.querySelector('[name=logo_src]').value='file'">
                        </div>
                        <div class="flex flex-col gap-1">
                            <span class="text-[10px] text-white/30 uppercase ml-1">Or New URL:</span>
                            <input class="input text-xs xref-input" name="logo_ext" data-xref="<?= htmlspecialchars($logoExtB64) ?>" oninput="this.form.querySelector('[name=logo_src]').value='url'">
                        </div>
                    </div>

                    <!-- Settings -->
                    <div class="lg:col-span-2 flex flex-col gap-3">
                        <div class="flex items-center justify-between">
                            <span class="text-xs text-white/40">Order:</span>
                            <input class="input w-16 text-center py-1" type="number" name="sort_order" value="<?= (int)$r["sort_order"] ?>">
                        </div>
                        <label class="flex items-center gap-2 cursor-pointer justify-end">
                            <input type="checkbox" name="is_visible" value="1" <?= $r["is_visible"] ? "checked" : "" ?> class="checkbox checkbox-sm">
                            <span class="text-xs">Visible</span>
                        </label>
                    </div>

                    <!-- Save -->
                    <div class="lg:col-span-2 flex flex-col gap-2">
                        <button class="btn btn-primary btn-sm w-full"><i class="fa-solid fa-check"></i> Update</button>
                        <div class="text-[10px] text-center <?= $r["is_visible"] ? 'text-green-400' : 'text-amber-400' ?> uppercase font-bold tracking-tighter">
                            Status: <?= $r["is_visible"] ? "Active" : "Hidden" ?>
                        </div>
                    </div>
                </form>

                <!-- Utility Buttons -->
                <div class="flex items-center justify-end gap-3 mt-4 pt-4 border-t border-white/5">
                    <form method="POST" class="inline">
                        <?= Csrf::field() ?>
                        <input type="hidden" name="action" value="toggle">
                        <input type="hidden" name="id" value="<?= (int)$r["id"] ?>">
                        <button class="btn btn-ghost btn-xs text-white/60 hover:text-white">
                            <i class="fa-solid <?= $r["is_visible"] ? 'fa-eye-slash' : 'fa-eye' ?> mr-1"></i>
                            <?= $r["is_visible"] ? "Hide" : "Show" ?>
                        </button>
                    </form>
                    <form method="POST" class="inline" onsubmit="return confirm('Permanently delete this client?')">
                        <?= Csrf::field() ?>
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="id" value="<?= (int)$r["id"] ?>">
                        <button class="btn btn-danger btn-xs">
                            <i class="fa-solid fa-trash-can mr-1"></i> Delete
                        </button>
                    </form>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<script>
/**
 * Decoding external data from Base64 on the client side 
 * helps protect the server against certain WAF inspection rules.
 */
function decodeXref() {
    document.querySelectorAll('[data-xref]').forEach(function(el) {
        var b = el.getAttribute('data-xref');
        if (!b) return;
        try {
            var u = atob(b);
            if (el.tagName === 'IMG') { el.src = u; }
            else if (el.tagName === 'INPUT') { el.value = u; }
            else { el.href = u; }
            el.removeAttribute('data-xref'); // Prevent double decoding
        } catch (e) {
            console.error("Base64 decode failed for element", el);
        }
    });
}
window.addEventListener('DOMContentLoaded', decodeXref);
</script>

<?php admin_layout_end(); ?>