<?php
declare(strict_types=1);

require __DIR__ . "/../bootstrap.php";
require __DIR__ . "/partials/layout.php";

use App\Csrf;
use App\Experience;
use App\Upload;

$uploadDir = __DIR__ . "/../uploads/images";

function collectListValues(mixed $values): array {
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
                $existing = Experience::find($id);
                $logo = (string) ($existing["company_logo"] ?? "");
                if ($logo !== '' && preg_match('/^[\w.\-]+$/', $logo) === 1) {
                    Upload::delete($uploadDir, $logo);
                }
                Experience::delete($id);
            }
            flash_set("success", "Experience deleted.");
        } else {
            $payload = [
                "position" => trim((string) ($_POST["position"] ?? $_POST["role"] ?? "")),
                "company_name" => trim((string) ($_POST["company_name"] ?? $_POST["company"] ?? "")),
                "employment_type" => trim((string) ($_POST["employment_type"] ?? "Full-time")),
                "start_date" => trim((string) ($_POST["start_date"] ?? "")),
                "end_date" => trim((string) ($_POST["end_date"] ?? "")),
                "is_current" => !empty($_POST["is_current"]) ? 1 : 0,
                "location" => trim((string) ($_POST["location"] ?? "")),
                "work_mode" => trim((string) ($_POST["work_mode"] ?? "")),
                "description" => trim((string) ($_POST["description"] ?? "")),
                "responsibilities" => collectListValues($_POST["responsibilities"] ?? []),
                "achievements" => collectListValues($_POST["achievements"] ?? []),
                "technologies" => collectListValues($_POST["technologies"] ?? []),
                "company_url" => trim((string) ($_POST["company_url"] ?? "")),
                "sort_order" => (int) ($_POST["sort_order"] ?? 0),
                "is_published" => !empty($_POST["is_published"]) ? 1 : 0,
            ];

            $existingLogo = trim((string) ($_POST["existing_company_logo"] ?? ""));
            $removeLogo = !empty($_POST["remove_company_logo"]);
            if ($removeLogo) {
                $payload["company_logo"] = "";
            } elseif (($file = $_FILES["company_logo"] ?? null) && (($file["error"] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_OK)) {
                $payload["company_logo"] = Upload::image($file, $uploadDir);
            } elseif ($existingLogo !== "") {
                $payload["company_logo"] = $existingLogo;
            } else {
                $payload["company_logo"] = "";
            }

            if ($action === "create") {
                Experience::create($payload);
                flash_set("success", "Experience created.");
            } elseif ($action === "update") {
                $id = (int) ($_POST["id"] ?? 0);
                if ($id > 0) {
                    if ($removeLogo) {
                        $previous = Experience::find($id);
                        $oldLogo = (string) ($previous["company_logo"] ?? "");
                        if ($oldLogo !== '' && preg_match('/^[\\w.\\-]+$/', $oldLogo) === 1) {
                            Upload::delete($uploadDir, $oldLogo);
                        }
                    }
                    Experience::update($id, $payload);
                }
                flash_set("success", "Experience updated.");
            }
        }
    } catch (Throwable $e) {
        flash_set("error", $e->getMessage());
    }
    header("Location: /admin/experience.php");
    exit;
}

$rows = Experience::all();
admin_layout_start("Experience", "experience");
?>
<?= flash_render() ?>

<div class="space-y-6">
    <div class="glass rounded-2xl p-5">
        <h2 class="text-lg font-semibold mb-4"><i class="fa-solid fa-plus mr-1"></i> Add experience</h2>
        <form method="POST" enctype="multipart/form-data" class="space-y-4">
            <?= Csrf::field() ?>
            <input type="hidden" name="action" value="create">
            <div class="grid md:grid-cols-2 gap-4">
                <div>
                    <label class="label">Position *</label>
                    <input class="input" name="position" required placeholder="Full Stack Developer">
                </div>
                <div>
                    <label class="label">Company Name *</label>
                    <input class="input" name="company_name" required placeholder="CodeCraft Labs">
                </div>
                <div>
                    <label class="label">Employment Type</label>
                    <select class="input" name="employment_type">
                        <option>Full-time</option>
                        <option>Part-time</option>
                        <option>Internship</option>
                        <option>Freelance</option>
                        <option>Contract</option>
                        <option>Remote</option>
                    </select>
                </div>
                <div>
                    <label class="label">Company Logo</label>
                    <input class="input" type="file" name="company_logo" accept="image/*">
                </div>
                <div>
                    <label class="label">Start Date *</label>
                    <input class="input" type="date" name="start_date" required>
                </div>
                <div>
                    <label class="label">End Date</label>
                    <input class="input" type="date" name="end_date">
                </div>
                <div>
                    <label class="label">Location</label>
                    <input class="input" name="location" placeholder="Kathmandu, Nepal">
                </div>
                <div>
                    <label class="label">Work Mode</label>
                    <select class="input" name="work_mode">
                        <option value="">Select mode</option>
                        <option>Remote</option>
                        <option>Hybrid</option>
                        <option>On-site</option>
                    </select>
                </div>
                <div>
                    <label class="label">Company Website</label>
                    <input class="input" name="company_url" placeholder="https://example.com">
                </div>
                <div>
                    <label class="label">Sort Order</label>
                    <input class="input" type="number" name="sort_order" value="100">
                </div>
            </div>

            <div>
                <label class="label">Professional Description</label>
                <textarea class="input" name="description" rows="4" placeholder="Describe the role, product, and impact..."></textarea>
            </div>

            <div>
                <label class="label">Responsibilities</label>
                <div class="space-y-2" data-list-field="responsibilities">
                    <input class="input" name="responsibilities[]" placeholder="Responsible for building application features">
                    <input class="input" name="responsibilities[]" placeholder="Improved API performance and reliability">
                </div>
                <button type="button" class="btn btn-ghost btn-sm mt-2" data-add-list="responsibilities">+ Add responsibility</button>
            </div>

            <div>
                <label class="label">Achievements</label>
                <div class="space-y-2" data-list-field="achievements">
                    <input class="input" name="achievements[]" placeholder="Reduced page load time by 35%">
                </div>
                <button type="button" class="btn btn-ghost btn-sm mt-2" data-add-list="achievements">+ Add achievement</button>
            </div>

            <div>
                <label class="label">Technologies / Skills</label>
                <div class="space-y-2" data-list-field="technologies">
                    <input class="input" name="technologies[]" placeholder="PHP">
                    <input class="input" name="technologies[]" placeholder="React">
                </div>
                <button type="button" class="btn btn-ghost btn-sm mt-2" data-add-list="technologies">+ Add technology</button>
            </div>

            <div class="flex items-center gap-3 pt-2">
                <label class="flex items-center gap-2 text-sm text-white/80">
                    <input type="checkbox" name="is_current" value="1">
                    Currently working here
                </label>
                <label class="flex items-center gap-2 text-sm text-white/80">
                    <input type="checkbox" name="is_published" value="1" checked>
                    Published
                </label>
            </div>

            <button class="btn btn-primary"><i class="fa-solid fa-plus"></i> Add Experience</button>
        </form>
    </div>

    <div class="glass rounded-2xl p-5">
        <h2 class="text-lg font-semibold mb-4">Existing experience</h2>
        <?php if (!$rows): ?>
            <p class="text-white/40 py-4">No experience items yet.</p>
        <?php else: ?>
            <div class="space-y-5">
                <?php foreach ($rows as $row):
                    $logo = (string) ($row["company_logo"] ?? "");
                    $responsibilities = $row["responsibilities"] ?? [];
                    $achievements = $row["achievements"] ?? [];
                    $technologies = $row["technologies"] ?? [];
                ?>
                    <form method="POST" enctype="multipart/form-data" class="border border-white/10 rounded-xl p-4 space-y-4">
                        <?= Csrf::field() ?>
                        <input type="hidden" name="action" value="update">
                        <input type="hidden" name="id" value="<?= (int) $row["id"] ?>">
                        <input type="hidden" name="existing_company_logo" value="<?= htmlspecialchars($logo, ENT_QUOTES) ?>">

                        <div class="flex items-start justify-between gap-4">
                            <div class="flex items-center gap-3">
                                <?php if ($logo !== ''): ?>
                                    <img src="/uploads/images/<?= rawurlencode($logo) ?>" alt="<?= htmlspecialchars((string) ($row["company_name"] ?? $row["company"] ?? "Company logo"), ENT_QUOTES) ?>" class="w-12 h-12 rounded-lg object-cover border border-white/10">
                                <?php else: ?>
                                    <div class="w-12 h-12 rounded-lg border border-dashed border-white/15 bg-white/5 flex items-center justify-center text-white/40"><i class="fa-solid fa-building"></i></div>
                                <?php endif; ?>
                                <div>
                                    <div class="font-semibold"><?= htmlspecialchars((string) ($row["position"] ?? $row["role"] ?? ""), ENT_QUOTES) ?></div>
                                    <div class="text-xs text-white/50"><?= htmlspecialchars((string) ($row["company_name"] ?? $row["company"] ?? ""), ENT_QUOTES) ?></div>
                                </div>
                            </div>
                            <button type="submit" class="btn btn-ghost btn-sm"><i class="fa-solid fa-save"></i></button>
                        </div>

                        <div class="grid md:grid-cols-2 gap-3">
                            <div><label class="label">Position</label><input class="input" name="position" value="<?= htmlspecialchars((string) ($row["position"] ?? $row["role"] ?? ""), ENT_QUOTES) ?>"></div>
                            <div><label class="label">Company</label><input class="input" name="company_name" value="<?= htmlspecialchars((string) ($row["company_name"] ?? $row["company"] ?? ""), ENT_QUOTES) ?>"></div>
                            <div><label class="label">Employment Type</label><input class="input" name="employment_type" value="<?= htmlspecialchars((string) ($row["employment_type"] ?? "Full-time"), ENT_QUOTES) ?>"></div>
                            <div><label class="label">Company Website</label><input class="input" name="company_url" value="<?= htmlspecialchars((string) ($row["company_url"] ?? ""), ENT_QUOTES) ?>"></div>
                            <div><label class="label">Start Date</label><input class="input" type="date" name="start_date" value="<?= htmlspecialchars((string) ($row["start_date"] ?? ""), ENT_QUOTES) ?>"></div>
                            <div><label class="label">End Date</label><input class="input" type="date" name="end_date" value="<?= htmlspecialchars((string) ($row["end_date"] ?? ""), ENT_QUOTES) ?>"></div>
                            <div><label class="label">Location</label><input class="input" name="location" value="<?= htmlspecialchars((string) ($row["location"] ?? ""), ENT_QUOTES) ?>"></div>
                            <div><label class="label">Work Mode</label><input class="input" name="work_mode" value="<?= htmlspecialchars((string) ($row["work_mode"] ?? ""), ENT_QUOTES) ?>"></div>
                            <div><label class="label">Sort Order</label><input class="input" type="number" name="sort_order" value="<?= (int) ($row["sort_order"] ?? 0) ?>"></div>
                            <div class="flex items-end gap-3">
                                <label class="flex items-center gap-2 text-sm text-white/80">
                                    <input type="checkbox" name="is_current" value="1" <?= !empty($row["is_current"]) ? "checked" : "" ?>>
                                    Current
                                </label>
                                <label class="flex items-center gap-2 text-sm text-white/80">
                                    <input type="checkbox" name="is_published" value="1" <?= !empty($row["is_published"]) ? "checked" : "" ?>>
                                    Published
                                </label>
                            </div>
                        </div>

                        <div>
                            <label class="label">Company Logo</label>
                            <input class="input" type="file" name="company_logo" accept="image/*">
                            <?php if ($logo !== ''): ?>
                                <label class="flex items-center gap-2 mt-2 text-xs text-red-300 cursor-pointer">
                                    <input type="checkbox" name="remove_company_logo" value="1">
                                    Remove logo
                                </label>
                            <?php endif; ?>
                        </div>

                        <div>
                            <label class="label">Description</label>
                            <textarea class="input" name="description" rows="3"><?= htmlspecialchars((string) ($row["description"] ?? ""), ENT_QUOTES) ?></textarea>
                        </div>

                        <div class="grid md:grid-cols-3 gap-3">
                            <div>
                                <label class="label">Responsibilities</label>
                                <div class="space-y-2">
                                    <?php foreach ($responsibilities ?: [""] as $value): ?>
                                        <input class="input" name="responsibilities[]" value="<?= htmlspecialchars((string) $value, ENT_QUOTES) ?>">
                                    <?php endforeach; ?>
                                </div>
                            </div>
                            <div>
                                <label class="label">Achievements</label>
                                <div class="space-y-2">
                                    <?php foreach ($achievements ?: [""] as $value): ?>
                                        <input class="input" name="achievements[]" value="<?= htmlspecialchars((string) $value, ENT_QUOTES) ?>">
                                    <?php endforeach; ?>
                                </div>
                            </div>
                            <div>
                                <label class="label">Technologies</label>
                                <div class="space-y-2">
                                    <?php foreach ($technologies ?: [""] as $value): ?>
                                        <input class="input" name="technologies[]" value="<?= htmlspecialchars((string) $value, ENT_QUOTES) ?>">
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>

                        <div class="flex justify-end">
                            <button type="submit" class="btn btn-primary btn-sm"><i class="fa-solid fa-save"></i> Save</button>
                            <button type="submit" formaction="/admin/experience.php" formmethod="POST" class="btn btn-danger btn-sm ml-2" name="action" value="delete" onclick="return confirm('Delete this experience?')">
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
</script>

<?php admin_layout_end(); ?>
