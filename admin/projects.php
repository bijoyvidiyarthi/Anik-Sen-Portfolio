<?php
declare(strict_types=1);

require __DIR__ . "/../bootstrap.php";
require __DIR__ . "/partials/layout.php";

use App\Project;
use App\Software;
use App\Csrf;
use App\Services\ProjectService;

$config   = $GLOBALS["APP_CONFIG"];
$imgDir   = $config["paths"]["image_dir"];
$videoDir = $config["paths"]["video_dir"] ?? ($config["paths"]["root"] . "/uploads/videos");

$projectService = new ProjectService($imgDir, $videoDir);

/* ── POST: only delete + toggle_publish live here ── */
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    Csrf::require();
    $action = $_POST["action"] ?? "";
    try {
        if ($action === "delete") {
            $projectService->delete((int)$_POST["id"]);
            flash_set("success", "Project deleted.");
        } elseif ($action === "toggle_publish") {
            $id   = (int)$_POST["id"];
            $next = Project::togglePublish($id);
            flash_set("success", $next ? "Project is now LIVE on the site." : "Project hidden (draft).");
        }
    } catch (Throwable $e) {
        flash_set("error", $e->getMessage());
    }
    header("Location: /admin/projects.php"); exit;
}

/* ── Data ── */
$projects  = Project::all(false);
$total     = count($projects);
$published = count(array_filter($projects, fn($p) => $p["is_published"]));
$drafts    = $total - $published;
$videos    = count(array_filter($projects, fn($p) => ($p["main_category"] ?? "") === "video"));
$graphics  = $total - $videos;

admin_layout_start("Projects", "projects");
?>
<?= flash_render() ?>

<!-- ── Page header ── -->
<div class="flex flex-wrap items-center justify-between gap-4 mb-6">
    <div>
        <h1 class="text-2xl font-bold flex items-center gap-2">
            <span class="w-8 h-8 rounded-lg bg-purple-500/20 border border-purple-500/30 flex items-center justify-center text-purple-300">
                <i class="fa-solid fa-briefcase text-sm"></i>
            </span>
            All Projects
            <span class="text-white/30 text-lg font-normal ml-1">(<?= $total ?>)</span>
        </h1>
        <p class="text-sm text-white/40 mt-1">Manage your portfolio projects — publish, edit, or remove.</p>
    </div>
    <a href="/admin/projects-add.php" class="btn btn-primary">
        <i class="fa-solid fa-plus"></i> Add New Project
    </a>
</div>

<!-- ── Stats bar ── -->
<div class="grid grid-cols-2 sm:grid-cols-4 gap-3 mb-6">
    <?php
    $stats = [
        ["All",       $total,     "fa-layer-group",   "text-white/60",   "bg-white/5 border-white/8"],
        ["Published", $published, "fa-eye",            "text-emerald-300","bg-emerald-500/8 border-emerald-400/20"],
        ["Drafts",    $drafts,    "fa-eye-slash",      "text-amber-300",  "bg-amber-500/8 border-amber-400/20"],
        ["Video",     $videos,    "fa-circle-play",    "text-rose-300",   "bg-rose-500/8 border-rose-400/20"],
    ];
    foreach ($stats as [$label, $count, $icon, $color, $bg]): ?>
        <div class="glass rounded-xl px-4 py-3 border <?= $bg ?> flex items-center gap-3">
            <span class="text-lg <?= $color ?>"><i class="fa-solid <?= $icon ?>"></i></span>
            <div>
                <div class="text-xl font-bold text-white"><?= $count ?></div>
                <div class="text-[11px] text-white/40"><?= $label ?></div>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<!-- ── Project grid ── -->
<?php if (!$projects): ?>
    <div class="glass rounded-2xl p-16 flex flex-col items-center gap-4 text-center text-white/35">
        <i class="fa-solid fa-folder-open text-5xl opacity-30"></i>
        <p class="text-lg font-medium">No projects yet</p>
        <p class="text-sm text-white/25">Get started by adding your first project.</p>
        <a href="/admin/projects-add.php" class="btn btn-primary mt-2">
            <i class="fa-solid fa-plus"></i> Add First Project
        </a>
    </div>
<?php else: ?>
    <div class="grid sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
        <?php foreach ($projects as $p):
            $img = $p["image"] ?? "";
            if ($img && file_exists($imgDir . "/" . $img))   $src = "/uploads/images/" . htmlspecialchars($img);
            elseif ($img && str_starts_with($img, "http"))   $src = htmlspecialchars($img);
            elseif ($img)                                    $src = "/assets/images/"  . htmlspecialchars($img);
            else                                             $src = "";
            $sw        = Software::parse((string)($p["software"] ?? ""));
            $isVideo   = ($p["main_category"] ?? "") === "video";
            $isMediaV  = ($p["media_kind"] ?? "") === "video";
            $isPublished = (bool)$p["is_published"];
        ?>
            <div class="glass rounded-2xl overflow-hidden border border-white/6 hover:border-white/14 transition group flex flex-col">

                <!-- Thumbnail -->
                <div class="aspect-video bg-black/40 relative overflow-hidden">
                    <?php if ($src): ?>
                        <img src="<?= $src ?>" class="w-full h-full object-cover group-hover:scale-105 transition duration-500"
                             loading="lazy" onerror="this.style.display='none'">
                    <?php else: ?>
                        <div class="w-full h-full flex items-center justify-center">
                            <i class="fa-solid fa-image text-3xl text-white/10"></i>
                        </div>
                    <?php endif; ?>
                    <!-- Badges -->
                    <div class="absolute top-2 left-2 flex gap-1 flex-wrap">
                        <?php if ($isMediaV): ?>
                            <span class="text-[10px] px-2 py-0.5 rounded-full bg-rose-500/30 border border-rose-400/30 text-rose-100 backdrop-blur-sm">
                                <i class="fa-solid fa-circle-play mr-0.5"></i> Video
                            </span>
                        <?php endif; ?>
                        <?php if (!$isPublished): ?>
                            <span class="text-[10px] px-2 py-0.5 rounded-full bg-amber-500/30 border border-amber-400/30 text-amber-100 backdrop-blur-sm">
                                <i class="fa-solid fa-eye-slash mr-0.5"></i> Draft
                            </span>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Card body -->
                <div class="p-4 flex flex-col gap-3 flex-1">
                    <div class="flex items-start justify-between gap-2">
                        <div class="min-w-0 flex-1">
                            <div class="font-semibold text-white text-sm truncate leading-tight">
                                <?= htmlspecialchars($p["title"]) ?>
                            </div>
                            <div class="text-[11px] text-white/40 mt-0.5 truncate">
                                <?= $isVideo ? "Video" : "Graphic" ?>
                                <?= $p["sub_category"] ? " · " . htmlspecialchars($p["sub_category"]) : "" ?>
                            </div>
                        </div>
                        <!-- Quick publish toggle -->
                        <form method="POST" class="shrink-0">
                            <input type="hidden" name="_csrf"  value="<?= htmlspecialchars(Csrf::token(), ENT_QUOTES) ?>">
                            <input type="hidden" name="action" value="toggle_publish">
                            <input type="hidden" name="id"     value="<?= (int)$p["id"] ?>">
                            <button type="submit"
                                    title="<?= $isPublished ? 'Live — click to hide' : 'Draft — click to publish' ?>"
                                    class="text-[10px] px-2 py-1 rounded-full font-semibold border transition
                                    <?= $isPublished
                                        ? 'bg-emerald-500/15 border-emerald-400/40 text-emerald-200 hover:bg-emerald-500/30'
                                        : 'bg-amber-500/15  border-amber-400/40  text-amber-200  hover:bg-amber-500/30' ?>">
                                <i class="fa-solid <?= $isPublished ? 'fa-eye' : 'fa-eye-slash' ?>"></i>
                                <?= $isPublished ? 'Live' : 'Draft' ?>
                            </button>
                        </form>
                    </div>

                    <?php if ($sw): ?>
                        <div class="flex flex-wrap gap-1">
                            <?php foreach ($sw as $k => [$lab, $let, $col, $bg]): ?>
                                <span title="<?= htmlspecialchars($lab) ?>"
                                      class="text-[9px] font-bold w-5 h-5 rounded-md flex items-center justify-center shrink-0"
                                      style="background:<?= $bg ?>;color:<?= $col ?>"><?= htmlspecialchars($let) ?></span>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>

                    <!-- Actions -->
                    <div class="flex gap-2 mt-auto pt-1">
                        <a href="/admin/projects-edit.php?id=<?= (int)$p["id"] ?>"
                           class="btn btn-ghost text-xs flex-1 justify-center">
                            <i class="fa-solid fa-pen"></i> Edit
                        </a>
                        <form method="POST"
                              onsubmit="return confirm('Delete this project and all its images? This cannot be undone.')">
                            <input type="hidden" name="_csrf"  value="<?= htmlspecialchars(Csrf::token(), ENT_QUOTES) ?>">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="id"     value="<?= (int)$p["id"] ?>">
                            <button class="btn btn-danger text-xs" title="Delete">
                                <i class="fa-solid fa-trash"></i>
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php admin_layout_end(); ?>
