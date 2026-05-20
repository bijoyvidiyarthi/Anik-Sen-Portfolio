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

foreach ([$imgDir, $videoDir] as $_dir) {
    if ($_dir && !is_dir($_dir)) @mkdir($_dir, 0775, true);
}

$projectService = new ProjectService($imgDir, $videoDir);

/* ── POST: create only ── */
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    Csrf::require();
    $action = $_POST["action"] ?? "";
    try {
        if ($action === "create") {
            $result = $projectService->create($_POST, $_FILES);
            flash_set("success", "Project created successfully.");
            header("Location: /admin/projects-edit.php?id=" . $result["id"]); exit;
        }
    } catch (Throwable $e) {
        flash_set("error", $e->getMessage());
        header("Location: /admin/projects-add.php"); exit;
    }
    header("Location: /admin/projects-add.php"); exit;
}

/* ── Render ── */
$editing    = null;
$catalog    = Software::catalog();
$selectedSw = [];
$mainCat    = "graphic";
$mediaKind  = "gallery";

admin_layout_start("Add Project", "projects");
?>
<?= flash_render() ?>

<!-- Page header -->
<div class="flex items-center gap-4 mb-6">
    <a href="/admin/projects.php"
       class="w-9 h-9 rounded-xl bg-white/5 border border-white/8 flex items-center justify-center text-white/50 hover:text-white hover:bg-white/10 transition shrink-0">
        <i class="fa-solid fa-arrow-left text-sm"></i>
    </a>
    <div>
        <h1 class="text-xl font-bold flex items-center gap-2">
            <span class="w-7 h-7 rounded-lg bg-emerald-500/20 border border-emerald-500/30 flex items-center justify-center text-emerald-300">
                <i class="fa-solid fa-plus text-xs"></i>
            </span>
            Add New Project
        </h1>
        <p class="text-sm text-white/40 mt-0.5">Fill in the details below and click "Create project".</p>
    </div>
</div>

<!-- Form — centered, single column -->
<div class="max-w-2xl mx-auto">
    <?php require __DIR__ . "/partials/project-form.php"; ?>
</div>

<?php admin_layout_end(); ?>
