<?php
declare(strict_types=1);

require __DIR__ . "/../bootstrap.php";
require __DIR__ . "/partials/layout.php";

use App\Certification;
use App\Csrf;
use App\Upload;

$uploadDir = __DIR__ . "/../uploads/images";

function collectSkillsValues(mixed $values): array {
    $items = [];
    if (is_array($values)) {
        foreach ($values as $value) {
            $txt = trim((string) $value);
            if ($txt !== '') {
                $items[] = $txt;
            }
        }
    } elseif (is_string($values)) {
        foreach (preg_split('/\r\n|\n|,/', $values) as $value) {
            $txt = trim((string) $value);
            if ($txt !== '') {
                $items[] = $txt;
            }
        }
    }
    return array_values(array_unique($items));
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    Csrf::require();
    $action = $_POST["action"] ?? "";
    try {
        if ($action === "delete") {
            $id = (int) ($_POST["id"] ?? 0);
            if ($id > 0) {
                $existing = Certification::find($id);
                $image = (string) ($existing["certificate_image"] ?? "");
                if ($image !== '' && preg_match('/^[\\w.\\-]+$/', $image) === 1) {
                    Upload::delete($uploadDir, $image);
                }
                Certification::delete($id);
            }
            flash_set("success", "Certification deleted.");
        } else {
            $payload = [
                "certificate_name" => trim((string) ($_POST["certificate_name"] ?? $_POST["title"] ?? "")),
                "issuing_organization" => trim((string) ($_POST["issuing_organization"] ?? $_POST["issuer"] ?? "")),
                "issue_date" => trim((string) ($_POST["issue_date"] ?? $_POST["year"] ?? "")),
                "expiration_date" => trim((string) ($_POST["expiration_date"] ?? "")),
                "credential_id" => trim((string) ($_POST["credential_id"] ?? "")),
                "credential_url" => trim((string) ($_POST["credential_url"] ?? "")),
                "verification_url" => trim((string) ($_POST["verification_url"] ?? "")),
                "description" => trim((string) ($_POST["description"] ?? "")),
                "skills" => collectSkillsValues($_POST["skills"] ?? []),
                "sort_order" => (int) ($_POST["sort_order"] ?? 0),
                "is_published" => !empty($_POST["is_published"]) ? 1 : 0,
            ];

            $payload["certificate_image"] = "";
            $payload["organization_logo"] = trim((string) ($_POST["organization_logo"] ?? ""));
            $existingImage = trim((string) ($_POST["existing_certificate_image"] ?? ""));
            if (!empty($_POST["remove_certificate_image"])) {
                $payload["certificate_image"] = "";
            } elseif (($file = $_FILES["certificate_image"] ?? null) && (($file["error"] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_OK)) {
                $payload["certificate_image"] = Upload::image($file, $uploadDir);
            } elseif ($existingImage !== "") {
                $payload["certificate_image"] = $existingImage;
            }

            if ($action === "create") {
                Certification::create($payload);
                flash_set("success", "Certification created.");
            } elseif ($action === "update") {
                $id = (int) ($_POST["id"] ?? 0);
                if ($id > 0) {
                    if (!empty($_POST["remove_certificate_image"])) {
                        $previous = Certification::find($id);
                        $oldImage = (string) ($previous["certificate_image"] ?? "");
                        if ($oldImage !== '' && preg_match('/^[\\w.\\-]+$/', $oldImage) === 1) {
                            Upload::delete($uploadDir, $oldImage);
                        }
                    }
                    Certification::update($id, $payload);
                }
                flash_set("success", "Certification updated.");
            }
        }
    } catch (Throwable $e) {
        flash_set("error", $e->getMessage());
    }
    header("Location: /admin/certifications.php");
    exit;
}

$rows = Certification::all();
admin_layout_start("Certifications", "certifications");
?>
<?= flash_render() ?>

<div class="space-y-6">
    <div class="glass rounded-2xl p-5">
        <h2 class="text-lg font-semibold mb-4"><i class="fa-solid fa-plus mr-1"></i> Add certification</h2>
        <form method="POST" enctype="multipart/form-data" class="space-y-4">
            <?= Csrf::field() ?>
            <input type="hidden" name="action" value="create">
            <div class="grid md:grid-cols-2 gap-4">
                <div>
                    <label class="label">Certificate Name *</label>
                    <input class="input" name="certificate_name" required placeholder="Microsoft Azure Fundamentals">
                </div>
                <div>
                    <label class="label">Issuing Organization *</label>
                    <input class="input" name="issuing_organization" required placeholder="Microsoft">
                </div>
                <div>
                    <label class="label">Issue Date *</label>
                    <input class="input" type="date" name="issue_date" required>
                </div>
                <div>
                    <label class="label">Expiration Date</label>
                    <input class="input" type="date" name="expiration_date">
                </div>
                <div>
                    <label class="label">Credential ID</label>
                    <input class="input" name="credential_id" placeholder="AZ-900-2026">
                </div>
                <div>
                    <label class="label">Sort Order</label>
                    <input class="input" type="number" name="sort_order" value="100">
                </div>
                <div>
                    <label class="label">Credential URL</label>
                    <input class="input" name="credential_url" placeholder="https://learn.microsoft.com/...">
                </div>
                <div>
                    <label class="label">Verification URL</label>
                    <input class="input" name="verification_url" placeholder="https://..."></div>
                <div>
                    <label class="label">Organization Logo</label>
                    <input class="input" name="organization_logo" placeholder="https://logo... or file upload later">
                </div>
                <div>
                    <label class="label">Certificate Image *</label>
                    <input class="input" type="file" name="certificate_image" accept="image/jpeg,image/png,image/webp" required data-image-file-input data-preview-target="#cert-create-preview">
                    <div id="cert-create-preview" class="mt-3 hidden">
                        <img class="w-36 h-auto rounded-xl border border-white/10 object-contain bg-black/20" alt="Certificate upload preview">
                    </div>
                </div>
            </div>

            <div>
                <label class="label">Description</label>
                <textarea class="input" name="description" rows="4" placeholder="What this credential validates..."></textarea>
            </div>

            <div>
                <label class="label">Skills / Technologies</label>
                <div class="space-y-2" data-list-field="skills">
                    <input class="input" name="skills[]" placeholder="Azure">
                    <input class="input" name="skills[]" placeholder="Cloud Computing">
                </div>
                <button type="button" class="btn btn-ghost btn-sm mt-2" data-add-list="skills">+ Add skill</button>
            </div>

            <div class="flex items-center gap-3 pt-2">
                <label class="flex items-center gap-2 text-sm text-white/80">
                    <input type="checkbox" name="is_published" value="1" checked>
                    Published
                </label>
            </div>

            <button class="btn btn-primary"><i class="fa-solid fa-plus"></i> Add Certification</button>
        </form>
    </div>

    <div class="glass rounded-2xl p-5">
        <h2 class="text-lg font-semibold mb-4">Existing certifications</h2>
        <?php if (!$rows): ?>
            <p class="text-white/40 py-4">No certifications yet.</p>
        <?php else: ?>
            <div class="space-y-5">
                <?php foreach ($rows as $row):
                    $image = (string) ($row["certificate_image"] ?? "");
                    $skills = $row["skills"] ?? [];
                ?>
                    <form method="POST" enctype="multipart/form-data" class="border border-white/10 rounded-xl p-4 space-y-4">
                        <?= Csrf::field() ?>
                        <input type="hidden" name="action" value="update">
                        <input type="hidden" name="id" value="<?= (int) $row["id"] ?>">
                        <input type="hidden" name="existing_certificate_image" value="<?= htmlspecialchars($image, ENT_QUOTES) ?>">

                        <div class="flex items-start justify-between gap-4">
                            <div class="flex items-center gap-3">
                                <?php if ($image !== ''): ?>
                                    <img src="/uploads/images/<?= rawurlencode($image) ?>" alt="<?= htmlspecialchars((string) ($row["certificate_name"] ?? $row["title"] ?? "Certificate"), ENT_QUOTES) ?>" class="w-16 h-16 rounded-lg object-cover border border-white/10">
                                <?php else: ?>
                                    <div class="w-16 h-16 rounded-lg border border-dashed border-white/15 bg-white/5 flex items-center justify-center text-white/40"><i class="fa-solid fa-award"></i></div>
                                <?php endif; ?>
                                <div>
                                    <div class="font-semibold"><?= htmlspecialchars((string) ($row["certificate_name"] ?? $row["title"] ?? ""), ENT_QUOTES) ?></div>
                                    <div class="text-xs text-white/50"><?= htmlspecialchars((string) ($row["issuing_organization"] ?? $row["issuer"] ?? ""), ENT_QUOTES) ?></div>
                                </div>
                            </div>
                            <button type="submit" class="btn btn-ghost btn-sm"><i class="fa-solid fa-save"></i></button>
                        </div>

                        <div class="grid md:grid-cols-2 gap-3">
                            <div><label class="label">Certificate Name</label><input class="input" name="certificate_name" value="<?= htmlspecialchars((string) ($row["certificate_name"] ?? $row["title"] ?? ""), ENT_QUOTES) ?>"></div>
                            <div><label class="label">Issuing Organization</label><input class="input" name="issuing_organization" value="<?= htmlspecialchars((string) ($row["issuing_organization"] ?? $row["issuer"] ?? ""), ENT_QUOTES) ?>"></div>
                            <div><label class="label">Issue Date</label><input class="input" type="date" name="issue_date" value="<?= htmlspecialchars((string) ($row["issue_date"] ?? $row["year"] ?? ""), ENT_QUOTES) ?>"></div>
                            <div><label class="label">Expiration Date</label><input class="input" type="date" name="expiration_date" value="<?= htmlspecialchars((string) ($row["expiration_date"] ?? ""), ENT_QUOTES) ?>"></div>
                            <div><label class="label">Credential ID</label><input class="input" name="credential_id" value="<?= htmlspecialchars((string) ($row["credential_id"] ?? ""), ENT_QUOTES) ?>"></div>
                            <div><label class="label">Sort Order</label><input class="input" type="number" name="sort_order" value="<?= (int) ($row["sort_order"] ?? 0) ?>"></div>
                            <div><label class="label">Credential URL</label><input class="input" name="credential_url" value="<?= htmlspecialchars((string) ($row["credential_url"] ?? ""), ENT_QUOTES) ?>"></div>
                            <div><label class="label">Verification URL</label><input class="input" name="verification_url" value="<?= htmlspecialchars((string) ($row["verification_url"] ?? ""), ENT_QUOTES) ?>"></div>
                        </div>

                        <div>
                            <label class="label">Certificate Image</label>
                            <input class="input" type="file" name="certificate_image" accept="image/jpeg,image/png,image/webp" data-image-file-input data-preview-target="#cert-preview-<?= (int) $row["id"] ?>">
                            <div id="cert-preview-<?= (int) $row["id"] ?>" class="mt-3 <?= $image !== '' ? '' : 'hidden' ?>">
                                <?php if ($image !== ''): ?>
                                    <img src="/uploads/images/<?= rawurlencode($image) ?>" alt="Certificate preview" class="w-40 h-auto rounded-xl border border-white/10 object-contain bg-black/20">
                                <?php endif; ?>
                            </div>
                            <?php if ($image !== ''): ?>
                                <label class="flex items-center gap-2 mt-2 text-xs text-red-300 cursor-pointer">
                                    <input type="checkbox" name="remove_certificate_image" value="1">
                                    Remove certificate image
                                </label>
                            <?php endif; ?>
                        </div>

                        <div>
                            <label class="label">Description</label>
                            <textarea class="input" name="description" rows="3"><?= htmlspecialchars((string) ($row["description"] ?? ""), ENT_QUOTES) ?></textarea>
                        </div>

                        <div>
                            <label class="label">Skills / Technologies</label>
                            <div class="space-y-2">
                                <?php foreach ($skills ?: [""] as $value): ?>
                                    <input class="input" name="skills[]" value="<?= htmlspecialchars((string) $value, ENT_QUOTES) ?>">
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <div class="flex items-center gap-3">
                            <label class="flex items-center gap-2 text-sm text-white/80">
                                <input type="checkbox" name="is_published" value="1" <?= !empty($row["is_published"]) ? "checked" : "" ?>>
                                Published
                            </label>
                        </div>

                        <div class="flex justify-end">
                            <button type="submit" class="btn btn-primary btn-sm"><i class="fa-solid fa-save"></i> Save</button>
                            <button type="submit" formaction="/admin/certifications.php" formmethod="POST" class="btn btn-danger btn-sm ml-2" name="action" value="delete" onclick="return confirm('Delete this certification?')">
                                <i class="fa-solid fa-trash"></i> Delete
                            </button>
                        </div>
                    </form>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
    document.addEventListener('click', function (event) {
        const trigger = event.target.closest('[data-add-list]');
        if (!trigger) return;
        const fieldName = trigger.dataset.addList;
        const container = document.querySelector('[data-list-field="' + fieldName + '"]');
        if (!container) return;
        const input = document.createElement('input');
        input.className = 'input';
        input.name = fieldName + '[]';
        input.placeholder = 'Add item';
        container.appendChild(input);
    });

    document.querySelectorAll('[data-image-file-input]').forEach((input) => {
        const selector = input.dataset.previewTarget;
        if (!selector) return;
        const previewBox = document.querySelector(selector);
        if (!previewBox) return;

        input.addEventListener('change', function () {
            const file = this.files && this.files[0];
            if (!file) {
                previewBox.classList.add('hidden');
                previewBox.innerHTML = '';
                return;
            }
            const url = URL.createObjectURL(file);
            previewBox.classList.remove('hidden');
            previewBox.innerHTML = '<img src="' + url + '" alt="Certificate upload preview" class="w-36 h-auto rounded-xl border border-white/10 object-contain bg-black/20">';
        });
    });
</script>

<?php admin_layout_end(); ?>
