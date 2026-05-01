<?php
declare(strict_types=1);

require __DIR__ . "/../bootstrap.php";
require __DIR__ . "/partials/layout.php";

use App\Project;
use App\ProjectImage;
use App\GalleryImage;
use App\Software;
use App\Csrf;
use App\Upload;

$config   = $GLOBALS["APP_CONFIG"];
$imgDir   = $config["paths"]["image_dir"];
$videoDir = $config["paths"]["video_dir"] ?? "";
$catalog  = Software::catalog();

/* ─────────────────────────────────────────────────────────────
   POST HANDLER
   $backToEdit is captured BEFORE the try block so the catch
   can always redirect back to the correct editor context.
───────────────────────────────────────────────────────────── */
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    Csrf::require();
    $action     = $_POST["action"] ?? "";
    $backToEdit = (int)($_POST["id"] ?? $_POST["project_id"] ?? 0);

    try {

        /* ── delete project ── */
        if ($action === "delete") {
            $id  = (int)$_POST["id"];
            $row = Project::find($id);
            if ($row && $row["image"] && file_exists($imgDir . "/" . $row["image"])) {
                Upload::delete($imgDir, $row["image"]);
            }
            if ($row && !empty($row["video_file"]) && $videoDir) {
                Upload::delete($videoDir, (string)$row["video_file"]);
            }
            if ($row && !empty($row["video_poster"])) {
                Upload::delete($imgDir, (string)$row["video_poster"]);
            }
            foreach (ProjectImage::forProject($id) as $img) {
                Upload::delete($imgDir, $img["filename"]);
            }
            Project::delete($id);
            GalleryImage::removeProjectVideo($id);
            flash_set("success", "Project deleted.");
            header("Location: /admin/projects.php"); exit;
        }

        /* ── remove self-hosted video file ── */
        if ($action === "delete_video") {
            $id  = (int)$_POST["id"];
            $row = Project::find($id);
            if ($row && !empty($row["video_file"]) && $videoDir) {
                Upload::delete($videoDir, (string)$row["video_file"]);
            }
            Project::clearMedia($id, "video_file");
            $updated = Project::find($id);
            if ($updated) {
                GalleryImage::syncProjectVideo($id, (string)$updated["title"], null, $updated["video_url"] ?? null);
            }
            flash_set("success", "Video file removed.");
            header("Location: /admin/projects.php?edit=" . $id); exit;
        }

        /* ── remove poster image ── */
        if ($action === "delete_poster") {
            $id  = (int)$_POST["id"];
            $row = Project::find($id);
            if ($row && !empty($row["video_poster"])) {
                Upload::delete($imgDir, (string)$row["video_poster"]);
            }
            Project::clearMedia($id, "video_poster");
            flash_set("success", "Poster image removed.");
            header("Location: /admin/projects.php?edit=" . $id); exit;
        }

        /* ── toggle live / draft ── */
        if ($action === "toggle_publish") {
            $id   = (int)$_POST["id"];
            $next = Project::togglePublish($id);
            flash_set("success", $next ? "Project is now LIVE on the site." : "Project hidden (draft).");
            $back = isset($_POST["back_to_edit"]) ? "?edit=" . (int)$_POST["back_to_edit"] : "";
            header("Location: /admin/projects.php{$back}"); exit;
        }

        /* ── delete single gallery image ── */
        if ($action === "delete_image") {
            $imgId = (int)$_POST["image_id"];
            $back  = (int)($_POST["project_id"] ?? 0);
            $name  = ProjectImage::delete($imgId);
            if ($name) Upload::delete($imgDir, $name);
            flash_set("success", "Gallery image removed.");
            header("Location: /admin/projects.php?edit=" . $back); exit;
        }

        /* ── upload gallery images ── */
        if ($action === "add_images") {
            $pid    = (int)$_POST["project_id"];
            $count  = 0;
            $errors = [];
            if (!empty($_FILES["gallery"]["name"][0])) {
                foreach ($_FILES["gallery"]["name"] as $i => $name) {
                    if (!$name) continue;
                    $f = [
                        "name"     => $_FILES["gallery"]["name"][$i],
                        "type"     => $_FILES["gallery"]["type"][$i],
                        "tmp_name" => $_FILES["gallery"]["tmp_name"][$i],
                        "error"    => $_FILES["gallery"]["error"][$i],
                        "size"     => $_FILES["gallery"]["size"][$i],
                    ];
                    try {
                        $fn = Upload::image($f, $imgDir);
                        ProjectImage::add($pid, $fn, "", ($count + 1) * 10);
                        $count++;
                    } catch (Throwable $imgErr) {
                        $errors[] = htmlspecialchars(basename((string)$name)) . ": " . $imgErr->getMessage();
                    }
                }
            }
            if ($errors) {
                flash_set("error", "Added {$count} image(s) with errors: " . implode(" | ", $errors));
            } else {
                flash_set("success", "Added {$count} image(s).");
            }
            /* Always redirect back to the project editor, never to the list */
            header("Location: /admin/projects.php?edit=" . $pid); exit;
        }

        /* ── cover image upload ── */
        $img = null;
        if (!empty($_FILES["image"]["name"])) {
            $img = Upload::image($_FILES["image"], $imgDir);
        }

        /* ── video uploads — only processed when media_kind === "video" ── */
        $submittedKind = trim((string)($_POST["media_kind"] ?? "gallery"));
        $videoFile     = null;
        $videoPoster   = null;

        if ($submittedKind === "video") {
            if (!empty($_FILES["video_file"]["name"]) && $videoDir) {
                $videoFile = Upload::video($_FILES["video_file"], $videoDir);
            }
            if (!empty($_FILES["video_poster"]["name"])) {
                $videoPoster = Upload::image($_FILES["video_poster"], $imgDir);
            }
        }

        /* ── create ── */
        if ($action === "create") {
            $newId   = Project::create($_POST, $img, $videoFile, $videoPoster);
            $created = Project::find($newId);
            if ($created && ($created["media_kind"] ?? "") === "video") {
                GalleryImage::syncProjectVideo(
                    $newId,
                    (string)$created["title"],
                    $created["video_file"] ?? null,
                    $created["video_url"]  ?? null
                );
            }
            flash_set("success", "Project created successfully.");
            header("Location: /admin/projects.php?edit=" . $newId); exit;
        }

        /* ── update ── */
        if ($action === "update") {
            $id       = (int)$_POST["id"];
            $existing = Project::find($id);
            if ($img && $existing && !empty($existing["image"])) {
                Upload::delete($imgDir, $existing["image"]);
            }
            if ($videoFile && $existing && !empty($existing["video_file"]) && $videoDir) {
                Upload::delete($videoDir, (string)$existing["video_file"]);
            }
            if ($videoPoster && $existing && !empty($existing["video_poster"])) {
                Upload::delete($imgDir, (string)$existing["video_poster"]);
            }
            Project::update($id, $_POST, $img, $videoFile, $videoPoster);
            $saved = Project::find($id);
            if ($saved && ($saved["media_kind"] ?? "") === "video") {
                GalleryImage::syncProjectVideo(
                    $id,
                    (string)$saved["title"],
                    $saved["video_file"] ?? null,
                    $saved["video_url"]  ?? null
                );
            } else {
                GalleryImage::removeProjectVideo($id);
            }
            flash_set("success", "Project updated.");
            header("Location: /admin/projects.php?edit=" . $id); exit;
        }

    } catch (Throwable $e) {
        flash_set("error", $e->getMessage());
        /* Preserve edit context: go back to the editor, not the blank list */
        $dest = $backToEdit ? "/admin/projects.php?edit=" . $backToEdit : "/admin/projects.php";
        header("Location: " . $dest); exit;
    }

    header("Location: /admin/projects.php"); exit;
}

/* ─────────────────────────────────────────────────────────────
   RENDER
───────────────────────────────────────────────────────────── */
$editId   = isset($_GET["edit"]) ? (int)$_GET["edit"] : 0;
$editing  = $editId ? Project::find($editId) : null;
$gallery  = $editing ? ProjectImage::forProject((int)$editing["id"]) : [];
$projects = Project::all(false);

$selectedSw = $editing ? array_keys(Software::parse((string)($editing["software"] ?? ""))) : [];
$mainCat    = $editing["main_category"] ?? "graphic";
$mediaKind  = $editing["media_kind"]    ?? "gallery";

admin_layout_start("Projects", "projects");
?>
<?= flash_render() ?>

<div class="grid lg:grid-cols-5 gap-5">

    <!-- ══════════════════════════════════════════════════
         LEFT — project list
    ══════════════════════════════════════════════════ -->
    <div class="lg:col-span-3 glass rounded-2xl p-5">
        <div class="flex items-center justify-between mb-4">
            <h2 class="text-lg font-semibold">
                All Projects
                <span class="text-white/35 text-base font-normal ml-1">(<?= count($projects) ?>)</span>
            </h2>
            <a href="/admin/projects.php" class="btn btn-primary text-sm">
                <i class="fa-solid fa-plus"></i> New project
            </a>
        </div>

        <?php if (!$projects): ?>
            <div class="text-center py-12 text-white/35 flex flex-col items-center gap-3">
                <i class="fa-solid fa-folder-open text-4xl"></i>
                <p>No projects yet — add your first one on the right.</p>
            </div>
        <?php else: ?>
            <div class="grid sm:grid-cols-2 gap-3">
            <?php foreach ($projects as $p):
                $img = $p["image"] ?? "";
                if ($img && file_exists($imgDir . "/" . $img))  $src = "/uploads/images/" . htmlspecialchars($img);
                elseif ($img && str_starts_with($img, "http"))  $src = htmlspecialchars($img);
                elseif ($img)                                   $src = "/assets/images/"  . htmlspecialchars($img);
                else                                            $src = "";
                $sw       = Software::parse((string)($p["software"] ?? ""));
                $isActive = $editing && (int)$editing["id"] === (int)$p["id"];
            ?>
                <div class="rounded-xl overflow-hidden border bg-black/30 transition
                    <?= $isActive ? 'border-purple-500/50 ring-2 ring-purple-500/20' : 'border-white/5 hover:border-white/12' ?>">

                    <!-- Thumbnail -->
                    <div class="aspect-video bg-black/50 flex items-center justify-center relative overflow-hidden">
                        <?php if ($src): ?>
                            <img src="<?= $src ?>" class="w-full h-full object-cover" loading="lazy"
                                 onerror="this.style.display='none'">
                        <?php else: ?>
                            <i class="fa-solid fa-image text-3xl text-white/12"></i>
                        <?php endif; ?>
                        <?php if (($p["media_kind"] ?? "") === "video"): ?>
                            <span class="absolute top-2 left-2 text-[10px] px-2 py-0.5 rounded-full
                                         bg-rose-500/30 border border-rose-400/30 text-rose-100 backdrop-blur-sm">
                                <i class="fa-solid fa-circle-play mr-0.5"></i> Video
                            </span>
                        <?php endif; ?>
                        <?php if (!$p["is_published"]): ?>
                            <span class="absolute top-2 right-2 text-[10px] px-2 py-0.5 rounded-full
                                         bg-amber-500/30 border border-amber-400/30 text-amber-100 backdrop-blur-sm">
                                <i class="fa-solid fa-eye-slash mr-0.5"></i> Draft
                            </span>
                        <?php endif; ?>
                    </div>

                    <!-- Card body -->
                    <div class="p-3">
                        <div class="flex items-start justify-between gap-2 mb-1">
                            <div class="min-w-0 flex-1">
                                <div class="font-semibold truncate text-white text-sm leading-tight">
                                    <?= htmlspecialchars($p["title"]) ?>
                                </div>
                                <div class="text-[11px] text-white/40 mt-0.5 truncate">
                                    <?= ($p["main_category"] ?? "") === "video" ? "Video" : "Graphic" ?>
                                    <?= $p["sub_category"] ? " · " . htmlspecialchars($p["sub_category"]) : "" ?>
                                </div>
                            </div>
                            <!-- Quick live / draft toggle -->
                            <form method="POST" class="shrink-0">
                                <input type="hidden" name="_csrf"  value="<?= htmlspecialchars(Csrf::token(), ENT_QUOTES) ?>">
                                <input type="hidden" name="action" value="toggle_publish">
                                <input type="hidden" name="id"     value="<?= (int)$p["id"] ?>">
                                <?php if ($isActive): ?>
                                    <input type="hidden" name="back_to_edit" value="<?= (int)$p["id"] ?>">
                                <?php endif; ?>
                                <button type="submit"
                                        title="<?= $p["is_published"] ? 'Live — click to hide' : 'Draft — click to publish' ?>"
                                        class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[10px] font-semibold border transition
                                        <?= $p["is_published"]
                                            ? 'bg-emerald-500/15 border-emerald-400/40 text-emerald-200 hover:bg-emerald-500/30'
                                            : 'bg-amber-500/15  border-amber-400/40  text-amber-200  hover:bg-amber-500/30' ?>">
                                    <i class="fa-solid <?= $p["is_published"] ? 'fa-eye' : 'fa-eye-slash' ?>"></i>
                                    <?= $p["is_published"] ? 'Live' : 'Draft' ?>
                                </button>
                            </form>
                        </div>

                        <?php if ($sw): ?>
                            <div class="flex flex-wrap gap-1 mt-1.5">
                                <?php foreach ($sw as $k => [$lab, $let, $col, $bg]): ?>
                                    <span title="<?= htmlspecialchars($lab) ?>"
                                          class="text-[9px] font-bold w-5 h-5 rounded-md flex items-center justify-center shrink-0"
                                          style="background:<?= $bg ?>;color:<?= $col ?>"><?= htmlspecialchars($let) ?></span>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>

                        <div class="flex gap-2 mt-3">
                            <a href="/admin/projects.php?edit=<?= (int)$p["id"] ?>"
                               class="btn btn-ghost text-xs flex-1 justify-center
                               <?= $isActive ? 'bg-purple-500/15 border-purple-500/30 text-purple-200' : '' ?>">
                                <i class="fa-solid fa-pen"></i>
                                <?= $isActive ? 'Editing…' : 'Edit' ?>
                            </a>
                            <form method="POST"
                                  onsubmit="return confirm('Delete this project and all its gallery images? This cannot be undone.')">
                                <input type="hidden" name="_csrf"  value="<?= htmlspecialchars(Csrf::token(), ENT_QUOTES) ?>">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id"     value="<?= (int)$p["id"] ?>">
                                <button class="btn btn-danger text-xs" title="Delete project">
                                    <i class="fa-solid fa-trash"></i>
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- ══════════════════════════════════════════════════
         RIGHT — editor
    ══════════════════════════════════════════════════ -->
    <div class="lg:col-span-2 space-y-5">

        <div class="glass rounded-2xl p-5 lg:sticky lg:top-20 max-h-[calc(100vh-5.5rem)] overflow-y-auto sidebar-scroll">

            <!-- Editor heading -->
            <div class="flex items-center justify-between mb-5">
                <h2 class="text-base font-semibold flex items-center gap-2">
                    <?php if ($editing): ?>
                        <span class="w-7 h-7 rounded-lg bg-purple-500/20 border border-purple-500/30 flex items-center justify-center text-purple-300">
                            <i class="fa-solid fa-pen text-xs"></i>
                        </span>
                        Edit Project
                    <?php else: ?>
                        <span class="w-7 h-7 rounded-lg bg-emerald-500/20 border border-emerald-500/30 flex items-center justify-center text-emerald-300">
                            <i class="fa-solid fa-plus text-xs"></i>
                        </span>
                        Add New Project
                    <?php endif; ?>
                </h2>
                <?php if ($editing): ?>
                    <a href="/admin/projects.php" class="btn btn-ghost text-xs py-1.5 px-3">
                        <i class="fa-solid fa-plus"></i> New
                    </a>
                <?php endif; ?>
            </div>

            <form method="POST" enctype="multipart/form-data" id="projectForm" novalidate class="space-y-5">
                <input type="hidden" name="_csrf"  value="<?= htmlspecialchars(Csrf::token(), ENT_QUOTES) ?>">
                <input type="hidden" name="action" value="<?= $editing ? 'update' : 'create' ?>">
                <?php if ($editing): ?>
                    <input type="hidden" name="id" value="<?= (int)$editing["id"] ?>">
                <?php endif; ?>

                <!-- ╔══════════════════════════════╗
                     ║  SECTION 1 — Basic info      ║
                     ╚══════════════════════════════╝ -->
                <div class="space-y-3">
                    <p class="form-section-label">
                        <i class="fa-solid fa-circle-info"></i> Basic info
                    </p>

                    <!-- Title -->
                    <div>
                        <label class="label">
                            Title <span class="text-rose-400 ml-0.5">*</span>
                        </label>
                        <input class="input" name="title" required
                               value="<?= htmlspecialchars($editing["title"] ?? "") ?>"
                               placeholder="Project name">
                    </div>

                    <!-- Main + sub category -->
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="label">Main category</label>
                            <select name="main_category" id="mainCat" class="select">
                                <?php foreach (Project::MAIN_CATEGORIES as $k => $label): ?>
                                    <option value="<?= $k ?>" <?= $mainCat === $k ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($label) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label class="label">Sub-category</label>
                            <select name="sub_category" id="subCat" class="select"></select>
                        </div>
                    </div>

                    <!-- Media kind — radio pill group -->
                    <div>
                        <label class="label">Display as</label>
                        <div class="grid grid-cols-3 gap-2">
                            <?php
                            $kindMeta = [
                                "video"   => ["fa-circle-play",              "Video player"],
                                "gallery" => ["fa-images",                   "Lightbox"],
                                "link"    => ["fa-arrow-up-right-from-square","External link"],
                            ];
                            foreach (Project::MEDIA_KINDS as $k => $label):
                                [$icon, $short] = $kindMeta[$k];
                                $active = $mediaKind === $k;
                            ?>
                                <label class="kind-pill flex flex-col items-center gap-1.5 py-3 px-2 rounded-xl border cursor-pointer select-none transition"
                                       data-kind="<?= $k ?>">
                                    <input type="radio" name="media_kind" value="<?= $k ?>"
                                           class="sr-only" <?= $active ? 'checked' : '' ?>>
                                    <i class="fa-solid <?= $icon ?> text-sm"></i>
                                    <span class="text-[10px] font-semibold text-center leading-tight"><?= $short ?></span>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- Sort + Status -->
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="label">Sort order</label>
                            <input class="input" type="number" name="sort_order"
                                   value="<?= (int)($editing["sort_order"] ?? 0) ?>" min="0">
                        </div>
                        <div>
                            <label class="label">Status</label>
                            <select name="is_published" class="select">
                                <option value="1" <?= !isset($editing) || $editing["is_published"] ? 'selected' : '' ?>>✅ Published</option>
                                <option value="0" <?= isset($editing) && !$editing["is_published"] ? 'selected' : '' ?>>⏸ Draft</option>
                            </select>
                        </div>
                    </div>
                </div>

                <hr class="border-white/6">

                <!-- ╔══════════════════════════════╗
                     ║  SECTION 2 — Cover image     ║
                     ╚══════════════════════════════╝ -->
                <div class="space-y-3">
                    <p class="form-section-label">
                        <i class="fa-solid fa-image"></i> Cover image
                    </p>

                    <?php if ($editing && !empty($editing["image"])):
                        $isUpload = file_exists($imgDir . "/" . $editing["image"]);
                        $isUrl    = str_starts_with((string)$editing["image"], "http");
                        $coverSrc = $isUrl
                            ? $editing["image"]
                            : ($isUpload ? '/uploads/images/' . $editing["image"] : '/assets/images/' . $editing["image"]);
                    ?>
                        <div class="rounded-xl overflow-hidden border border-white/8 bg-black/30">
                            <img src="<?= htmlspecialchars($coverSrc) ?>"
                                 class="w-full max-h-44 object-cover"
                                 onerror="this.parentElement.style.display='none'"
                                 alt="Current cover image">
                            <div class="px-3 py-2 text-[10px] text-white/35 truncate border-t border-white/5">
                                <?= htmlspecialchars($editing["image"]) ?>
                            </div>
                        </div>
                    <?php endif; ?>

                    <div>
                        <label class="label">
                            <?= ($editing && !empty($editing["image"])) ? 'Replace cover image' : 'Upload cover image' ?>
                        </label>
                        <input type="file" name="image" id="coverInput" class="input" accept="image/*">
                        <p class="text-[10px] text-white/35 mt-1">JPG · PNG · WebP · GIF · max 8 MB</p>
                        <!-- Local preview before upload -->
                        <div id="coverPreview" class="hidden mt-2 rounded-xl overflow-hidden border border-white/8 bg-black/30">
                            <img id="coverPreviewImg" src="" class="w-full max-h-40 object-cover" alt="Preview">
                            <div class="px-3 py-2 text-[10px] text-white/35" id="coverPreviewName"></div>
                        </div>
                    </div>
                </div>

                <hr class="border-white/6">

                <!-- ╔══════════════════════════════════════╗
                     ║  SECTION 3 — Video settings          ║
                     ║  (toggled by JS; hidden for gallery) ║
                     ╚══════════════════════════════════════╝ -->
                <div id="videoSection" class="space-y-3">
                    <p class="form-section-label">
                        <i class="fa-solid fa-circle-play"></i> Video settings
                    </p>

                    <!-- YouTube / Vimeo URL -->
                    <div>
                        <label class="label">
                            Video URL
                            <span class="text-white/35 font-normal text-[10px] ml-1">YouTube / Vimeo — fallback when no file is uploaded</span>
                        </label>
                        <input class="input" name="video_url"
                               value="<?= htmlspecialchars($editing["video_url"] ?? "") ?>"
                               placeholder="https://www.youtube.com/watch?v=...">
                    </div>

                    <!-- Self-hosted video block -->
                    <div class="rounded-xl border border-white/8 bg-black/25 p-4 space-y-4">
                        <div class="flex items-center justify-between">
                            <span class="text-sm font-semibold text-white">
                                <i class="fa-solid fa-film mr-1.5 text-purple-400"></i> Self-hosted video file
                            </span>
                            <span class="text-[10px] text-white/35">MP4 · WebM · MOV · max 50 MB</span>
                        </div>

                        <?php if ($editing && !empty($editing["video_file"])): ?>
                            <!-- Existing video preview -->
                            <div class="rounded-lg overflow-hidden border border-white/8 bg-black">
                                <video src="/uploads/videos/<?= htmlspecialchars($editing["video_file"]) ?>"
                                       <?= !empty($editing["video_poster"]) ? 'poster="/uploads/images/'.htmlspecialchars($editing["video_poster"]).'"' : '' ?>
                                       class="w-full max-h-44" controls preload="metadata"></video>
                            </div>
                            <div class="flex items-center justify-between gap-2 p-2.5 rounded-lg bg-black/30 border border-white/6">
                                <span class="text-[10px] text-white/55 truncate flex items-center gap-1.5">
                                    <i class="fa-solid fa-file-video text-purple-400"></i>
                                    <?= htmlspecialchars($editing["video_file"]) ?>
                                </span>
                                <form method="POST" class="shrink-0"
                                      onsubmit="return confirm('Permanently delete this video file?')">
                                    <input type="hidden" name="_csrf"  value="<?= htmlspecialchars(Csrf::token(), ENT_QUOTES) ?>">
                                    <input type="hidden" name="action" value="delete_video">
                                    <input type="hidden" name="id"     value="<?= (int)$editing["id"] ?>">
                                    <button class="btn btn-danger text-xs py-1">
                                        <i class="fa-solid fa-trash"></i> Remove
                                    </button>
                                </form>
                            </div>
                            <div>
                                <label class="label">Replace video file</label>
                                <input type="file" name="video_file" id="videoFileInput" class="input"
                                       accept="video/mp4,video/webm,video/quicktime,video/ogg,.mp4,.webm,.mov,.m4v,.ogg,.ogv">
                            </div>
                        <?php else: ?>
                            <div>
                                <label class="label">Upload video file</label>
                                <input type="file" name="video_file" id="videoFileInput" class="input"
                                       accept="video/mp4,video/webm,video/quicktime,video/ogg,.mp4,.webm,.mov,.m4v,.ogg,.ogv">
                            </div>
                        <?php endif; ?>

                        <!-- Upload progress bar (injected by JS) -->
                        <div id="videoProgress" class="hidden space-y-1">
                            <div class="flex items-center justify-between text-[10px] text-white/50">
                                <span>Uploading video…</span>
                                <span id="videoPct">0%</span>
                            </div>
                            <div class="h-1.5 bg-white/10 rounded-full overflow-hidden">
                                <div id="videoBar"
                                     class="h-full bg-gradient-to-r from-indigo-500 to-purple-500 rounded-full transition-all duration-300"
                                     style="width:0%"></div>
                            </div>
                        </div>

                        <hr class="border-white/6">

                        <!-- Poster image -->
                        <?php if ($editing && !empty($editing["video_poster"])): ?>
                            <div>
                                <label class="label">Current poster</label>
                                <div class="flex items-center gap-3 p-2.5 rounded-lg bg-black/30 border border-white/6">
                                    <img src="/uploads/images/<?= htmlspecialchars($editing["video_poster"]) ?>"
                                         class="w-20 h-12 object-cover rounded border border-white/10 shrink-0"
                                         onerror="this.style.display='none'" alt="">
                                    <div class="flex-1 min-w-0">
                                        <div class="text-[10px] text-white/55 truncate">
                                            <?= htmlspecialchars($editing["video_poster"]) ?>
                                        </div>
                                        <form method="POST" class="mt-2"
                                              onsubmit="return confirm('Delete the poster image?')">
                                            <input type="hidden" name="_csrf"  value="<?= htmlspecialchars(Csrf::token(), ENT_QUOTES) ?>">
                                            <input type="hidden" name="action" value="delete_poster">
                                            <input type="hidden" name="id"     value="<?= (int)$editing["id"] ?>">
                                            <button class="btn btn-danger text-xs py-1">
                                                <i class="fa-solid fa-trash"></i> Remove poster
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                            <div>
                                <label class="label">Replace poster image</label>
                                <input type="file" name="video_poster" class="input" accept="image/*">
                            </div>
                        <?php else: ?>
                            <div>
                                <label class="label">
                                    Poster image
                                    <span class="text-white/35 font-normal text-[10px] ml-1">shown before playback</span>
                                </label>
                                <input type="file" name="video_poster" class="input" accept="image/*">
                                <p class="text-[10px] text-white/35 mt-1">Falls back to cover image if not set.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <hr class="border-white/6" id="videoHr">

                <!-- ╔══════════════════════════════╗
                     ║  SECTION 4 — Skills & tools  ║
                     ╚══════════════════════════════╝ -->
                <div class="space-y-3">
                    <p class="form-section-label">
                        <i class="fa-solid fa-layer-group"></i> Skills &amp; tools
                    </p>

                    <div>
                        <label class="label">Skills / Techniques used</label>
                        <input class="input" name="skills_used"
                               value="<?= htmlspecialchars($editing["skills_used"] ?? "") ?>"
                               placeholder="Color grading, motion tracking, sound design…">
                    </div>

                    <div>
                        <label class="label">Software stack</label>
                        <div class="grid grid-cols-3 gap-1.5 max-h-40 overflow-y-auto p-2 rounded-xl
                                    bg-black/30 border border-white/8 sidebar-scroll">
                            <?php foreach ($catalog as $key => [$lab, $let, $col, $bg]):
                                $checked = in_array($key, $selectedSw, true);
                            ?>
                                <label class="flex items-center gap-1.5 px-2 py-1.5 rounded-lg cursor-pointer
                                              hover:bg-white/5 transition <?= $checked ? 'bg-white/5' : '' ?>"
                                       title="<?= htmlspecialchars($lab) ?>">
                                    <input type="checkbox" name="software[]" value="<?= $key ?>"
                                           <?= $checked ? 'checked' : '' ?> class="accent-purple-500">
                                    <span class="text-[9px] font-bold w-5 h-5 rounded-md flex items-center justify-center shrink-0"
                                          style="background:<?= $bg ?>;color:<?= $col ?>">
                                        <?= htmlspecialchars($let) ?>
                                    </span>
                                    <span class="text-[11px] truncate"><?= htmlspecialchars($lab) ?></span>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>

                <hr class="border-white/6">

                <!-- ╔══════════════════════════════╗
                     ║  SECTION 5 — Details         ║
                     ╚══════════════════════════════╝ -->
                <div class="space-y-3">
                    <p class="form-section-label">
                        <i class="fa-solid fa-align-left"></i> Details
                    </p>

                    <div>
                        <label class="label">Description</label>
                        <textarea name="description" class="textarea" rows="3"
                                  placeholder="A short description of the project…"><?= htmlspecialchars($editing["description"] ?? "") ?></textarea>
                    </div>

                    <div>
                        <label class="label">
                            External URL
                            <span class="text-white/35 font-normal text-[10px] ml-1">optional</span>
                        </label>
                        <input class="input" name="project_url"
                               value="<?= htmlspecialchars($editing["project_url"] ?? "") ?>"
                               placeholder="https://...">
                    </div>
                </div>

                <!-- ── Submit row ── -->
                <div class="flex gap-2 pt-1">
                    <button type="submit" id="submitBtn" class="btn btn-primary flex-1 justify-center">
                        <i class="fa-solid fa-save" id="submitIcon"></i>
                        <span id="submitLabel"><?= $editing ? "Save changes" : "Create project" ?></span>
                    </button>
                    <?php if ($editing): ?>
                        <a href="/admin/projects.php" class="btn btn-ghost" title="Discard / back to list">
                            <i class="fa-solid fa-xmark"></i>
                        </a>
                    <?php endif; ?>
                </div>
            </form>

            <?php if ($editing): ?>
            <!-- ══════════════════════════════════════════════════
                 Gallery image manager — lives INSIDE the same
                 scrollable panel so it never floats separately.
            ══════════════════════════════════════════════════ -->
            <div class="mt-6 pt-5 border-t border-white/8">

                <!-- Section header -->
                <div class="flex items-center justify-between mb-1">
                    <h3 class="text-sm font-semibold flex items-center gap-2 text-white">
                        <span class="w-6 h-6 rounded-lg bg-indigo-500/20 border border-indigo-500/30
                                     flex items-center justify-center text-indigo-300">
                            <i class="fa-solid fa-images text-[10px]"></i>
                        </span>
                        Gallery images
                    </h3>
                    <span class="text-[11px] px-2 py-0.5 rounded-full bg-white/5 border border-white/8 text-white/50">
                        <?= count($gallery) ?> image<?= count($gallery) !== 1 ? 's' : '' ?>
                    </span>
                </div>
                <p class="text-[11px] text-white/38 mb-4 leading-relaxed">
                    Lightbox slideshow images for graphic projects.
                    The cover image appears first automatically.
                </p>

                <!-- Upload drop-zone styled form -->
                <form method="POST" enctype="multipart/form-data" id="galleryForm">
                    <input type="hidden" name="_csrf"      value="<?= htmlspecialchars(Csrf::token(), ENT_QUOTES) ?>">
                    <input type="hidden" name="action"     value="add_images">
                    <input type="hidden" name="project_id" value="<?= (int)$editing["id"] ?>">

                    <!-- Custom drop-zone label wrapping the real input -->
                    <label for="galleryFileInput"
                           id="galleryDropZone"
                           class="gallery-dropzone flex flex-col items-center justify-center gap-2
                                  rounded-xl border-2 border-dashed border-white/12
                                  bg-black/20 hover:bg-white/4 hover:border-indigo-500/40
                                  cursor-pointer transition p-5 mb-3 text-center">
                        <span class="w-9 h-9 rounded-xl bg-indigo-500/15 border border-indigo-500/25
                                     flex items-center justify-center text-indigo-300 text-base">
                            <i class="fa-solid fa-cloud-arrow-up"></i>
                        </span>
                        <span class="text-sm font-medium text-white/70" id="dropZoneLabel">
                            Click to choose images, or drag &amp; drop
                        </span>
                        <span class="text-[10px] text-white/35">
                            JPG · PNG · WebP · max 8 MB each · select multiple at once
                        </span>
                        <input type="file" id="galleryFileInput" name="gallery[]"
                               multiple accept="image/*" class="sr-only">
                    </label>

                    <!-- Selected files preview strip (populated by JS) -->
                    <div id="galleryPending" class="hidden mb-3">
                        <div class="text-[11px] text-white/45 mb-2 flex items-center gap-1.5">
                            <i class="fa-solid fa-circle-check text-emerald-400"></i>
                            <span id="galleryPendingLabel"></span>
                        </div>
                        <div id="galleryPendingThumbs" class="flex flex-wrap gap-1.5"></div>
                    </div>

                    <button type="submit" id="galleryBtn"
                            class="btn btn-primary text-sm w-full justify-center">
                        <i class="fa-solid fa-cloud-arrow-up" id="galleryIcon"></i>
                        <span id="galleryLabel">Upload images</span>
                    </button>
                </form>

                <!-- Existing gallery grid -->
                <?php if (!$gallery): ?>
                    <div class="text-center text-xs text-white/30 py-8 flex flex-col items-center gap-2 mt-4">
                        <i class="fa-regular fa-images text-2xl opacity-40"></i>
                        <span>No extra gallery images yet.</span>
                    </div>
                <?php else: ?>
                    <div class="grid grid-cols-3 sm:grid-cols-4 gap-2 mt-4">
                        <?php foreach ($gallery as $gi => $g): ?>
                            <div class="relative rounded-xl overflow-hidden border border-white/8
                                        bg-black/30 aspect-square group">
                                <img src="/uploads/images/<?= htmlspecialchars($g["filename"]) ?>"
                                     class="w-full h-full object-cover transition duration-200 group-hover:scale-105"
                                     loading="lazy"
                                     onerror="this.closest('.gallery-item-wrap,div').style.display='none'"
                                     alt="">
                                <!-- Overlay on hover -->
                                <div class="absolute inset-0 bg-black/0 group-hover:bg-black/40 transition duration-200 flex items-center justify-center">
                                    <form method="POST" class="opacity-0 group-hover:opacity-100 transition duration-200"
                                          onsubmit="return confirm('Remove this image from the gallery?')">
                                        <input type="hidden" name="_csrf"      value="<?= htmlspecialchars(Csrf::token(), ENT_QUOTES) ?>">
                                        <input type="hidden" name="action"     value="delete_image">
                                        <input type="hidden" name="image_id"   value="<?= (int)$g["id"] ?>">
                                        <input type="hidden" name="project_id" value="<?= (int)$editing["id"] ?>">
                                        <button title="Remove image"
                                                class="w-8 h-8 rounded-full flex items-center justify-center
                                                       bg-rose-500 hover:bg-rose-600 border border-rose-400/50
                                                       text-white text-xs shadow-lg transition">
                                            <i class="fa-solid fa-trash text-[11px]"></i>
                                        </button>
                                    </form>
                                </div>
                                <!-- Always-visible image index badge -->
                                <span class="absolute bottom-1 left-1 text-[9px] px-1.5 py-0.5 rounded-md
                                             bg-black/60 text-white/60 backdrop-blur-sm font-mono leading-none">
                                    <?= $gi + 1 ?>
                                </span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
            <?php endif; ?>

        </div><!-- /editor panel -->

    </div><!-- /right col -->
</div>

<style>
    .form-section-label {
        display: flex;
        align-items: center;
        gap: .45rem;
        font-size: .7rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: .06em;
        color: rgba(255,255,255,.28);
    }
    /* Radio kind pills */
    .kind-pill {
        border-color: rgba(255,255,255,.08);
        background: rgba(0,0,0,.22);
        color: rgba(255,255,255,.45);
    }
    .kind-pill:hover {
        border-color: rgba(255,255,255,.18);
        color: rgba(255,255,255,.75);
    }
    .kind-pill.active {
        border-color: rgba(168,85,247,.55);
        background: rgba(168,85,247,.12);
        color: #fff;
        box-shadow: 0 0 0 1px rgba(168,85,247,.25);
    }
    /* Gallery drop zone */
    .gallery-dropzone {
        border-color: rgba(255,255,255,.10);
        background:   rgba(0,0,0,.18);
        transition: border-color .2s, background .2s;
    }
    .gallery-dropzone:hover,
    .gallery-dropzone.drag-over {
        border-color: rgba(99,102,241,.50);
        background:   rgba(99,102,241,.07);
    }
    /* Gallery image hover overlay – ensure scale works on items */
    .gallery-thumb { transform-origin: center; }
</style>

<script>
(function () {
    /* ── constants from PHP ── */
    const SUBS       = <?= json_encode(Project::SUB_CATEGORIES) ?>;
    const currentSub = <?= json_encode($editing["sub_category"] ?? "") ?>;
    const initKind   = <?= json_encode($mediaKind) ?>;

    /* ── DOM refs ── */
    const mainSel   = document.getElementById('mainCat');
    const subSel    = document.getElementById('subCat');
    const videoSec  = document.getElementById('videoSection');
    const videoHr   = document.getElementById('videoHr');
    const kindPills = document.querySelectorAll('.kind-pill');
    const kindRadios= document.querySelectorAll('input[name="media_kind"]');
    const form      = document.getElementById('projectForm');

    /* ── sub-category rebuild ── */
    function rebuildSubs(preserve) {
        const list = SUBS[mainSel.value] || [];
        subSel.innerHTML = '';
        if (list.length === 0) {
            const opt = document.createElement('option');
            opt.value = ''; opt.textContent = '— none —';
            subSel.appendChild(opt);
            return;
        }
        list.forEach(function (label) {
            const opt = document.createElement('option');
            opt.value = label; opt.textContent = label;
            if (preserve && label === preserve) opt.selected = true;
            subSel.appendChild(opt);
        });
    }

    /* ── get current media_kind ── */
    function getKind() {
        let val = 'gallery';
        kindRadios.forEach(function (r) { if (r.checked) val = r.value; });
        return val;
    }

    /* ── sync kind pill styles + show/hide video section ── */
    function syncKind() {
        const cur = getKind();
        kindPills.forEach(function (el) {
            const on = el.dataset.kind === cur;
            el.classList.toggle('active', on);
        });
        if (videoSec) videoSec.style.display = cur === 'video' ? '' : 'none';
        if (videoHr)  videoHr.style.display  = cur === 'video' ? '' : 'none';
    }

    /* ── kind pill click: toggle radio + refresh UI ── */
    let userChoseKind = false;
    kindPills.forEach(function (el) {
        el.addEventListener('click', function () {
            const radio = el.querySelector('input[type="radio"]');
            if (radio) { radio.checked = true; }
            userChoseKind = true;
            syncKind();
        });
    });

    /* ── main category change ── */
    mainSel.addEventListener('change', function () {
        rebuildSubs(null);
        /* Only auto-suggest media_kind if the user hasn't explicitly picked one */
        if (!userChoseKind) {
            const suggested = mainSel.value === 'video' ? 'video' : 'gallery';
            kindRadios.forEach(function (r) { r.checked = (r.value === suggested); });
            syncKind();
        }
    });

    /* ── cover image local preview ── */
    const coverInput   = document.getElementById('coverInput');
    const coverPreview = document.getElementById('coverPreview');
    const coverImg     = document.getElementById('coverPreviewImg');
    const coverName    = document.getElementById('coverPreviewName');
    if (coverInput) {
        coverInput.addEventListener('change', function () {
            const file = coverInput.files[0];
            if (!file) { if (coverPreview) coverPreview.classList.add('hidden'); return; }
            if (coverImg) coverImg.src = URL.createObjectURL(file);
            if (coverName) coverName.textContent = file.name + ' (' + (file.size / 1024 / 1024).toFixed(2) + ' MB)';
            if (coverPreview) coverPreview.classList.remove('hidden');
        });
    }

    /* ── client-side file size guards ── */
    const VIDEO_MAX = 50 * 1024 * 1024;   // 50 MB
    const IMG_MAX   = 8  * 1024 * 1024;   //  8 MB

    function checkSizes() {
        const vInput = document.getElementById('videoFileInput');
        if (vInput && vInput.files[0] && vInput.files[0].size > VIDEO_MAX) {
            alert('The selected video file is larger than 50 MB.\nPlease choose a smaller file.');
            vInput.value = '';
            return false;
        }
        if (coverInput && coverInput.files[0] && coverInput.files[0].size > IMG_MAX) {
            alert('The selected cover image is larger than 8 MB.\nPlease choose a smaller image.');
            coverInput.value = '';
            if (coverPreview) coverPreview.classList.add('hidden');
            return false;
        }
        return true;
    }

    /* ── submit: progress bar for video uploads, spinner otherwise ── */
    const submitBtn   = document.getElementById('submitBtn');
    const submitIcon  = document.getElementById('submitIcon');
    const submitLabel = document.getElementById('submitLabel');
    const progressWrap= document.getElementById('videoProgress');
    const progressBar = document.getElementById('videoBar');
    const progressPct = document.getElementById('videoPct');

    function setSubmitting(uploading) {
        if (!submitBtn) return;
        submitBtn.disabled = true;
        if (submitIcon) submitIcon.className = 'fa-solid fa-spinner fa-spin';
        if (submitLabel) submitLabel.textContent = uploading ? 'Uploading…' : 'Saving…';
    }

    function resetSubmit() {
        if (!submitBtn) return;
        submitBtn.disabled = false;
        if (submitIcon) submitIcon.className = 'fa-solid fa-save';
        if (submitLabel) submitLabel.textContent = <?= json_encode($editing ? 'Save changes' : 'Create project') ?>;
    }

    if (form) {
        form.addEventListener('submit', function (e) {
            e.preventDefault();
            if (!checkSizes()) return;

            const vInput  = document.getElementById('videoFileInput');
            const hasVideo = vInput && vInput.files[0];

            if (!hasVideo) {
                setSubmitting(false);
                form.submit();
                return;
            }

            /* XHR upload with progress bar */
            setSubmitting(true);
            if (progressWrap) progressWrap.classList.remove('hidden');

            const xhr = new XMLHttpRequest();
            xhr.upload.addEventListener('progress', function (ev) {
                if (!ev.lengthComputable) return;
                const pct = Math.round(ev.loaded / ev.total * 100);
                if (progressBar) progressBar.style.width = pct + '%';
                if (progressPct) progressPct.textContent  = pct + '%';
            });
            xhr.addEventListener('load', function () {
                if (xhr.status >= 200 && xhr.status < 400) {
                    window.location.href = xhr.responseURL || '/admin/projects.php';
                } else {
                    resetSubmit();
                    if (progressWrap) progressWrap.classList.add('hidden');
                    alert('Upload failed (HTTP ' + xhr.status + '). Please try again.');
                }
            });
            xhr.addEventListener('error', function () {
                resetSubmit();
                if (progressWrap) progressWrap.classList.add('hidden');
                alert('Network error during upload. Check your connection and try again.');
            });
            xhr.open('POST', form.action || window.location.href);
            xhr.send(new FormData(form));
        });
    }

    /* ── gallery drop-zone: drag + file preview + loading state ── */
    const galleryForm      = document.getElementById('galleryForm');
    const galleryFileInput = document.getElementById('galleryFileInput');
    const galleryDropZone  = document.getElementById('galleryDropZone');
    const galleryBtn       = document.getElementById('galleryBtn');
    const galleryIcon      = document.getElementById('galleryIcon');
    const galleryLabel     = document.getElementById('galleryLabel');
    const galleryPending   = document.getElementById('galleryPending');
    const galleryPendingLb = document.getElementById('galleryPendingLabel');
    const galleryThumbs    = document.getElementById('galleryPendingThumbs');
    const dropLabel        = document.getElementById('dropZoneLabel');

    function renderGalleryPreviews(files) {
        if (!galleryThumbs || !galleryPending || !galleryPendingLb) return;
        galleryThumbs.innerHTML = '';
        if (!files || files.length === 0) {
            galleryPending.classList.add('hidden');
            return;
        }
        galleryPendingLb.textContent = files.length + ' file' + (files.length > 1 ? 's' : '') + ' ready to upload';
        Array.from(files).forEach(function (f) {
            const wrap = document.createElement('div');
            wrap.className = 'relative w-14 h-14 rounded-lg overflow-hidden border border-white/15 bg-black/30 shrink-0';
            if (f.type.startsWith('image/')) {
                const img = document.createElement('img');
                img.src = URL.createObjectURL(f);
                img.className = 'w-full h-full object-cover';
                wrap.appendChild(img);
            } else {
                wrap.innerHTML = '<div class="w-full h-full flex items-center justify-center text-white/40 text-xs">file</div>';
            }
            galleryThumbs.appendChild(wrap);
        });
        galleryPending.classList.remove('hidden');
    }

    if (galleryFileInput) {
        galleryFileInput.addEventListener('change', function () {
            renderGalleryPreviews(galleryFileInput.files);
            const n = galleryFileInput.files.length;
            if (dropLabel) dropLabel.textContent = n > 0
                ? n + ' file' + (n > 1 ? 's' : '') + ' selected'
                : 'Click to choose images, or drag & drop';
        });
    }

    /* Drag-and-drop handling on the drop zone label */
    if (galleryDropZone) {
        galleryDropZone.addEventListener('dragover', function (e) {
            e.preventDefault();
            galleryDropZone.classList.add('drag-over');
        });
        ['dragleave', 'dragend'].forEach(function (ev) {
            galleryDropZone.addEventListener(ev, function () {
                galleryDropZone.classList.remove('drag-over');
            });
        });
        galleryDropZone.addEventListener('drop', function (e) {
            e.preventDefault();
            galleryDropZone.classList.remove('drag-over');
            const dt = e.dataTransfer;
            if (!dt || !galleryFileInput) return;
            /* Transfer dropped files into the real input via DataTransfer */
            try {
                galleryFileInput.files = dt.files;
            } catch (_) { /* Safari fallback – can't assign, just skip preview */ }
            renderGalleryPreviews(dt.files);
            const n = dt.files.length;
            if (dropLabel) dropLabel.textContent = n + ' file' + (n > 1 ? 's' : '') + ' dropped';
        });
    }

    if (galleryForm) {
        galleryForm.addEventListener('submit', function () {
            if (galleryBtn)   galleryBtn.disabled = true;
            if (galleryIcon)  galleryIcon.className = 'fa-solid fa-spinner fa-spin';
            if (galleryLabel) galleryLabel.textContent = 'Uploading…';
        });
    }

    /* ── init ── */
    rebuildSubs(currentSub);
    syncKind();
})();
</script>

<?php admin_layout_end(); ?>
