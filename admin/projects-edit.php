<?php
declare(strict_types=1);

require __DIR__ . "/../bootstrap.php";
require __DIR__ . "/partials/layout.php";

use App\Project;
use App\ProjectImage;
use App\Software;
use App\Csrf;
use App\Services\ProjectService;

$config   = $GLOBALS["APP_CONFIG"];
$imgDir   = $config["paths"]["image_dir"];
$videoDir = $config["paths"]["video_dir"] ?? ($config["paths"]["root"] . "/uploads/videos");

foreach ([$imgDir, $videoDir] as $_dir) {
    if ($_dir && !is_dir($_dir)) @mkdir($_dir, 0775, true);
}

$projectService = new ProjectService($imgDir, $videoDir);

/* ── Resolve project id from GET or POST body (supports sub-forms that POST without ?id=) ── */
$editId = isset($_GET["id"]) ? (int)$_GET["id"]
        : (int)($_POST["id"] ?? $_POST["project_id"] ?? 0);
if (!$editId && $_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: /admin/projects.php"); exit;
}

/* ── POST handler ── */
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    Csrf::require();
    $action = $_POST["action"] ?? "";
    $backId = (int)($_POST["id"] ?? $_POST["project_id"] ?? $editId);

    if (!$backId) { header("Location: /admin/projects.php"); exit; }

    try {
        if ($action === "toggle_publish") {
            $next = Project::togglePublish($backId);
            flash_set("success", $next ? "Project is now LIVE on the site." : "Project hidden (draft).");
            header("Location: /admin/projects-edit.php?id=" . $backId); exit;
        }

        if ($action === "update") {
            $projectService->update($backId, $_POST, $_FILES);
            flash_set("success", "Project updated.");
            header("Location: /admin/projects-edit.php?id=" . $backId); exit;
        }

        if ($action === "delete_video") {
            $projectService->deleteVideoFile($backId);
            flash_set("success", "Video file removed.");
            header("Location: /admin/projects-edit.php?id=" . $backId); exit;
        }

        if ($action === "delete_poster") {
            $projectService->deletePoster($backId);
            flash_set("success", "Poster removed.");
            header("Location: /admin/projects-edit.php?id=" . $backId); exit;
        }

        if ($action === "delete_image") {
            $imgId = (int)$_POST["image_id"];
            $pid   = (int)($_POST["project_id"] ?? $backId);
            $projectService->deleteGalleryImage($imgId);
            flash_set("success", "Gallery image removed.");
            header("Location: /admin/projects-edit.php?id=" . $pid); exit;
        }

        if ($action === "add_images") {
            $pid    = (int)$_POST["project_id"];
            $result = $projectService->addGalleryImages($pid, $_FILES["gallery"] ?? []);
            if ($result["errors"]) {
                flash_set("error", "Added {$result['count']} image(s) with errors: " . implode(" | ", $result["errors"]));
            } else {
                flash_set("success", "Added {$result['count']} image(s).");
            }
            header("Location: /admin/projects-edit.php?id=" . $pid); exit;
        }

    } catch (Throwable $e) {
        flash_set("error", $e->getMessage());
        header("Location: /admin/projects-edit.php?id=" . $backId); exit;
    }

    header("Location: /admin/projects-edit.php?id=" . $editId); exit;
}

/* ── Load project ── */
$editing = Project::find($editId);
if (!$editing) { header("Location: /admin/projects.php"); exit; }

$gallery    = ProjectImage::forProject($editId);
$catalog    = Software::catalog();
$selectedSw = array_keys(Software::parse((string)($editing["software"] ?? "")));
$mainCat    = $editing["main_category"] ?? "graphic";
$mediaKind  = $editing["media_kind"]    ?? "gallery";

admin_layout_start("Edit Project", "projects");
?>
<?= flash_render() ?>

<!-- Page header -->
<div class="flex flex-wrap items-center justify-between gap-4 mb-6">
    <div class="flex items-center gap-4">
        <a href="/admin/projects.php"
           class="w-9 h-9 rounded-xl bg-white/5 border border-white/8 flex items-center justify-center text-white/50 hover:text-white hover:bg-white/10 transition shrink-0">
            <i class="fa-solid fa-arrow-left text-sm"></i>
        </a>
        <div>
            <h1 class="text-xl font-bold flex items-center gap-2">
                <span class="w-7 h-7 rounded-lg bg-purple-500/20 border border-purple-500/30 flex items-center justify-center text-purple-300">
                    <i class="fa-solid fa-pen text-xs"></i>
                </span>
                Edit Project
            </h1>
            <p class="text-sm text-white/40 mt-0.5 truncate max-w-xs">
                <?= htmlspecialchars($editing["title"]) ?>
            </p>
        </div>
    </div>

    <div class="flex items-center gap-2">
        <!-- Quick publish toggle -->
        <form method="POST">
            <input type="hidden" name="_csrf"  value="<?= htmlspecialchars(Csrf::token(), ENT_QUOTES) ?>">
            <input type="hidden" name="action" value="toggle_publish">
            <input type="hidden" name="id"     value="<?= $editId ?>">
            <button type="submit"
                    class="btn text-sm <?= $editing["is_published"]
                        ? 'btn-ghost border-emerald-400/40 text-emerald-200'
                        : 'btn-ghost border-amber-400/40  text-amber-200' ?>">
                <i class="fa-solid <?= $editing["is_published"] ? 'fa-eye' : 'fa-eye-slash' ?>"></i>
                <?= $editing["is_published"] ? "Live" : "Draft" ?>
            </button>
        </form>
        <a href="/admin/projects-add.php" class="btn btn-primary text-sm">
            <i class="fa-solid fa-plus"></i> New project
        </a>
    </div>
</div>

<!-- Two-column layout: form + gallery -->
<div class="grid lg:grid-cols-3 gap-6 items-start">

    <!-- ── LEFT: edit form (2/3 width) ── -->
    <div class="lg:col-span-2">
        <?php require __DIR__ . "/partials/project-form.php"; ?>
    </div>

    <!-- ── RIGHT: gallery manager (1/3 width) ── -->
    <div class="lg:col-span-1 space-y-4 lg:sticky lg:top-20">

        <!-- Gallery upload -->
        <div class="glass rounded-2xl p-5 space-y-4">
            <div class="flex items-center justify-between">
                <h3 class="text-sm font-semibold flex items-center gap-2">
                    <span class="w-6 h-6 rounded-lg bg-indigo-500/20 border border-indigo-500/30 flex items-center justify-center text-indigo-300">
                        <i class="fa-solid fa-images text-[10px]"></i>
                    </span>
                    Gallery images
                </h3>
                <span class="text-[11px] px-2 py-0.5 rounded-full bg-white/5 border border-white/8 text-white/50">
                    <?= count($gallery) ?> image<?= count($gallery) !== 1 ? 's' : '' ?>
                </span>
            </div>
            <p class="text-[11px] text-white/38 leading-relaxed -mt-2">
                Lightbox slideshow images for graphic projects. Cover image appears first automatically.
            </p>

            <form method="POST" enctype="multipart/form-data" id="galleryForm">
                <input type="hidden" name="_csrf"      value="<?= htmlspecialchars(Csrf::token(), ENT_QUOTES) ?>">
                <input type="hidden" name="action"     value="add_images">
                <input type="hidden" name="project_id" value="<?= $editId ?>">

                <label for="galleryFileInput" id="galleryDropZone"
                       class="gallery-dropzone flex flex-col items-center justify-center gap-2 rounded-xl border-2 border-dashed border-white/12 bg-black/20 hover:bg-white/4 hover:border-indigo-500/40 cursor-pointer transition p-5 mb-3 text-center">
                    <span class="w-9 h-9 rounded-xl bg-indigo-500/15 border border-indigo-500/25 flex items-center justify-center text-indigo-300 text-base">
                        <i class="fa-solid fa-cloud-arrow-up"></i>
                    </span>
                    <span class="text-sm font-medium text-white/70" id="dropZoneLabel">
                        Click to choose images, or drag &amp; drop
                    </span>
                    <span class="text-[10px] text-white/35">JPG · PNG · WebP · max 8 MB each · multiple OK</span>
                    <input type="file" id="galleryFileInput" name="gallery[]"
                           multiple accept="image/*" class="sr-only">
                </label>

                <div id="galleryPending" class="hidden mb-3">
                    <div class="text-[11px] text-white/45 mb-2 flex items-center gap-1.5">
                        <i class="fa-solid fa-circle-check text-emerald-400"></i>
                        <span id="galleryPendingLabel"></span>
                    </div>
                    <div id="galleryPendingThumbs" class="flex flex-wrap gap-1.5"></div>
                </div>

                <button type="submit" id="galleryBtn" class="btn btn-primary text-sm w-full justify-center">
                    <i class="fa-solid fa-cloud-arrow-up" id="galleryIcon"></i>
                    <span id="galleryLabel">Upload images</span>
                </button>
            </form>
        </div>

        <!-- Gallery grid -->
        <?php if ($gallery): ?>
            <div class="glass rounded-2xl p-4">
                <p class="text-[11px] text-white/40 mb-3 font-semibold uppercase tracking-wider">Saved images</p>
                <div class="grid grid-cols-3 gap-2">
                    <?php foreach ($gallery as $gi => $g): ?>
                        <div class="relative rounded-xl overflow-hidden border border-white/8 bg-black/30 aspect-square group">
                            <img src="/uploads/images/<?= htmlspecialchars($g["filename"]) ?>"
                                 class="w-full h-full object-cover transition duration-200 group-hover:scale-105"
                                 loading="lazy"
                                 onerror="this.closest('div').style.display='none'" alt="">
                            <div class="absolute inset-0 bg-black/0 group-hover:bg-black/45 transition duration-200 flex items-center justify-center">
                                <form method="POST" class="opacity-0 group-hover:opacity-100 transition duration-200"
                                      onsubmit="return confirm('Remove this image?')">
                                    <input type="hidden" name="_csrf"      value="<?= htmlspecialchars(Csrf::token(), ENT_QUOTES) ?>">
                                    <input type="hidden" name="action"     value="delete_image">
                                    <input type="hidden" name="image_id"   value="<?= (int)$g["id"] ?>">
                                    <input type="hidden" name="project_id" value="<?= $editId ?>">
                                    <button title="Remove"
                                            class="w-8 h-8 rounded-full flex items-center justify-center bg-rose-500 hover:bg-rose-600 border border-rose-400/50 text-white text-xs shadow-lg">
                                        <i class="fa-solid fa-trash text-[11px]"></i>
                                    </button>
                                </form>
                            </div>
                            <span class="absolute bottom-1 left-1 text-[9px] px-1.5 py-0.5 rounded-md bg-black/60 text-white/60 backdrop-blur-sm font-mono">
                                <?= $gi + 1 ?>
                            </span>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>

    </div><!-- /right -->
</div>

<style>
.gallery-dropzone { border-color:rgba(255,255,255,.10); background:rgba(0,0,0,.18); transition:border-color .2s,background .2s; }
.gallery-dropzone:hover,.gallery-dropzone.drag-over { border-color:rgba(99,102,241,.50); background:rgba(99,102,241,.07); }
</style>

<script>
(function(){
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

    function renderPreviews(files){
        if(!galleryThumbs||!galleryPending||!galleryPendingLb)return;
        galleryThumbs.innerHTML='';
        if(!files||!files.length){galleryPending.classList.add('hidden');return;}
        galleryPendingLb.textContent=files.length+' file'+(files.length>1?'s':'')+' ready';
        Array.from(files).forEach(function(f){
            const w=document.createElement('div');
            w.className='relative w-14 h-14 rounded-lg overflow-hidden border border-white/15 bg-black/30 shrink-0';
            if(f.type.startsWith('image/')){
                const img=document.createElement('img'); img.src=URL.createObjectURL(f);
                img.className='w-full h-full object-cover'; w.appendChild(img);
            } else {
                w.innerHTML='<div class="w-full h-full flex items-center justify-center text-white/40 text-xs">file</div>';
            }
            galleryThumbs.appendChild(w);
        });
        galleryPending.classList.remove('hidden');
    }

    if(galleryFileInput){
        galleryFileInput.addEventListener('change',function(){
            renderPreviews(galleryFileInput.files);
            const n=galleryFileInput.files.length;
            if(dropLabel) dropLabel.textContent=n>0?n+' file'+(n>1?'s':'')+' selected':'Click to choose images, or drag & drop';
        });
    }
    if(galleryDropZone){
        galleryDropZone.addEventListener('dragover',function(e){e.preventDefault();galleryDropZone.classList.add('drag-over');});
        ['dragleave','dragend'].forEach(function(ev){galleryDropZone.addEventListener(ev,function(){galleryDropZone.classList.remove('drag-over');});});
        galleryDropZone.addEventListener('drop',function(e){
            e.preventDefault(); galleryDropZone.classList.remove('drag-over');
            const dt=e.dataTransfer; if(!dt||!galleryFileInput)return;
            try{galleryFileInput.files=dt.files;}catch(_){}
            renderPreviews(dt.files);
            const n=dt.files.length; if(dropLabel) dropLabel.textContent=n+' file'+(n>1?'s':'')+' dropped';
        });
    }
    if(galleryForm){
        galleryForm.addEventListener('submit',function(){
            if(galleryBtn){galleryBtn.disabled=true;}
            if(galleryIcon) galleryIcon.className='fa-solid fa-spinner fa-spin';
            if(galleryLabel) galleryLabel.textContent='Uploading…';
        });
    }
})();
</script>

<?php admin_layout_end(); ?>
