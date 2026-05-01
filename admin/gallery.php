<?php
declare(strict_types=1);

require __DIR__ . "/../bootstrap.php";
require __DIR__ . "/partials/layout.php";

use App\GalleryCategory;
use App\GalleryImage;
use App\Csrf;
use App\Upload;

$config = $GLOBALS["APP_CONFIG"];

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    Csrf::require();
    $action = $_POST["action"] ?? "";
    try {
        switch ($action) {
            case "cat_create":
                GalleryCategory::create($_POST);
                flash_set("success", "Category created.");
                break;
            case "cat_update":
                GalleryCategory::update((int)$_POST["id"], $_POST);
                flash_set("success", "Category updated.");
                break;
            case "cat_delete":
                GalleryCategory::delete((int)$_POST["id"]);
                flash_set("success", "Category deleted.");
                break;

            case "img_upload":
                $catId     = (int)$_POST["category_id"];
                $mediaType = $_POST["media_type"] ?? "image";
                if (!$catId) throw new \RuntimeException("Choose a category first.");

                if ($mediaType === "video") {
                    $vf = $_FILES["video_file"] ?? null;
                    if (!$vf || empty($vf["name"])) throw new \RuntimeException("No video file selected.");
                    $stored = Upload::video($vf, $config["paths"]["video_dir"]);
                    GalleryImage::create($catId, $stored, pathinfo((string)$vf["name"], PATHINFO_FILENAME), "", 0, "video", "/uploads/videos/" . $stored);
                    flash_set("success", "Video uploaded.");
                } else {
                    $files = $_FILES["images"] ?? null;
                    if (!$files || empty($files["name"][0])) throw new \RuntimeException("No files selected.");
                    $count = 0;
                    foreach ($files["name"] as $i => $name) {
                        if (!$name) continue;
                        $one = [
                            "name"     => $files["name"][$i],
                            "type"     => $files["type"][$i],
                            "tmp_name" => $files["tmp_name"][$i],
                            "error"    => $files["error"][$i],
                            "size"     => $files["size"][$i],
                        ];
                        $stored = Upload::image($one, $config["paths"]["image_dir"]);
                        GalleryImage::create($catId, $stored, pathinfo((string)$one["name"], PATHINFO_FILENAME), "", $count * 10, $mediaType);
                        $count++;
                    }
                    flash_set("success", "$count file" . ($count === 1 ? "" : "s") . " uploaded.");
                }
                $_GET["cat"] = $catId;
                break;

            case "img_update":
                GalleryImage::update((int)$_POST["id"], $_POST);
                flash_set("success", "Updated.");
                $_GET["cat"] = (int)$_POST["category_id"];
                break;

            case "img_delete":
                $catId = (int)$_POST["category_id"];
                GalleryImage::delete((int)$_POST["id"], $config["paths"]["image_dir"]);
                flash_set("success", "Deleted.");
                $_GET["cat"] = $catId;
                break;
        }
    } catch (\Throwable $e) {
        flash_set("error", $e->getMessage());
    }
    $catParam = isset($_GET["cat"]) ? "?cat=" . (int)$_GET["cat"] : "";
    header("Location: /admin/gallery.php" . $catParam); exit;
}

$catId      = isset($_GET["cat"]) ? (int)$_GET["cat"] : 0;
$tree       = GalleryCategory::tree();
$allCats    = GalleryCategory::withCounts();
$selected   = $catId ? GalleryCategory::find($catId) : null;
$images     = $catId ? GalleryImage::inCategory($catId) : [];
$typeCounts = GalleryImage::countByType();

admin_layout_start("Media Gallery", "gallery");
?>
<?= flash_render() ?>

<!-- Stats bar -->
<div class="grid grid-cols-2 sm:grid-cols-4 gap-3 mb-5">
    <?php
    $typeInfo = [
        "image" => ["fa-image",     "Images",      "text-blue-400"],
        "logo"  => ["fa-copyright", "Logos",       "text-purple-400"],
        "video" => ["fa-film",      "Videos",      "text-red-400"],
        "icon"  => ["fa-icons",     "Icons & SVGs","text-amber-400"],
    ];
    foreach ($typeInfo as $type => [$ico, $lbl, $clr]):
        $n = $typeCounts[$type] ?? 0;
    ?>
    <div class="glass rounded-xl p-4 flex items-center gap-3">
        <i class="fa-solid <?= $ico ?> text-2xl <?= $clr ?>"></i>
        <div>
            <div class="text-xl font-bold"><?= $n ?></div>
            <div class="text-xs text-white/50"><?= $lbl ?></div>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<div class="grid lg:grid-cols-4 gap-5">
    <!-- LEFT: categories -->
    <div class="glass rounded-2xl p-5 lg:col-span-1">
        <h2 class="text-lg font-semibold mb-3"><i class="fa-solid fa-folder-tree mr-1"></i> Categories</h2>

        <ul class="space-y-1 mb-4 max-h-[28rem] overflow-auto pr-1">
            <?php foreach ($tree as $c): ?>
                <li>
                    <a href="?cat=<?= (int)$c["id"] ?>" class="nav-link <?= $catId === (int)$c["id"] ? 'active' : '' ?>">
                        <i class="fa-solid fa-folder text-amber-400"></i>
                        <span class="flex-1 truncate"><?= htmlspecialchars($c["name"]) ?></span>
                        <span class="text-xs text-white/50"><?= (int)$c["image_count"] ?></span>
                    </a>
                    <?php if (!empty($c["children"])): ?>
                        <ul class="ml-5 border-l border-white/5 pl-2 space-y-1 mt-1">
                            <?php foreach ($c["children"] as $sub): ?>
                                <li>
                                    <a href="?cat=<?= (int)$sub["id"] ?>" class="nav-link <?= $catId === (int)$sub["id"] ? 'active' : '' ?>">
                                        <i class="fa-regular fa-folder text-amber-300"></i>
                                        <span class="flex-1 truncate"><?= htmlspecialchars($sub["name"]) ?></span>
                                        <span class="text-xs text-white/50"><?= (int)$sub["image_count"] ?></span>
                                    </a>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </li>
            <?php endforeach; ?>
            <?php if (!$tree): ?><li class="text-xs text-white/40 px-2">No categories yet.</li><?php endif; ?>
        </ul>

        <form method="POST" class="space-y-2 border-t border-white/5 pt-3">
            <input type="hidden" name="_csrf" value="<?= htmlspecialchars(Csrf::token(), ENT_QUOTES) ?>">
            <input type="hidden" name="action" value="cat_create">
            <div class="text-xs font-semibold text-white/70">+ New category</div>
            <input class="input" name="name" placeholder="Category name" required>
            <select class="select" name="parent_id">
                <option value="">— Top level —</option>
                <?php foreach ($allCats as $c): if (!empty($c["parent_id"])) continue; ?>
                    <option value="<?= (int)$c["id"] ?>"><?= htmlspecialchars($c["name"]) ?></option>
                <?php endforeach; ?>
            </select>
            <input class="input" name="description" placeholder="Description (optional)">
            <input class="input" type="number" name="sort_order" placeholder="Sort" value="100">
            <button class="btn btn-primary w-full justify-center text-sm"><i class="fa-solid fa-plus"></i> Create</button>
        </form>
    </div>

    <!-- RIGHT: content -->
    <div class="lg:col-span-3 space-y-5">
        <?php if ($selected): ?>

            <!-- Edit category -->
            <form method="POST" class="glass rounded-2xl p-5 grid sm:grid-cols-5 gap-3 items-end">
                <input type="hidden" name="_csrf" value="<?= htmlspecialchars(Csrf::token(), ENT_QUOTES) ?>">
                <input type="hidden" name="action" value="cat_update">
                <input type="hidden" name="id" value="<?= (int)$selected["id"] ?>">
                <div><label class="label">Name</label><input class="input" name="name" value="<?= htmlspecialchars($selected["name"]) ?>"></div>
                <div><label class="label">Parent</label>
                    <select class="select" name="parent_id">
                        <option value="">— Top level —</option>
                        <?php foreach ($allCats as $c):
                            if ((int)$c["id"] === (int)$selected["id"]) continue;
                            if (!empty($c["parent_id"])) continue; ?>
                            <option value="<?= (int)$c["id"] ?>" <?= (int)$selected["parent_id"] === (int)$c["id"] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($c["name"]) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="sm:col-span-2"><label class="label">Description</label><input class="input" name="description" value="<?= htmlspecialchars($selected["description"] ?? '') ?>"></div>
                <div><label class="label">Sort</label><input class="input" type="number" name="sort_order" value="<?= (int)$selected["sort_order"] ?>"></div>
                <div class="sm:col-span-5 flex justify-between">
                    <button class="btn btn-primary text-sm"><i class="fa-solid fa-save"></i> Update category</button>
                    <button form="cat-delete-form" class="btn btn-danger text-sm" onclick="return confirm('Delete this category and all its media?')"><i class="fa-solid fa-trash"></i> Delete</button>
                </div>
            </form>
            <form method="POST" id="cat-delete-form">
                <input type="hidden" name="_csrf" value="<?= htmlspecialchars(Csrf::token(), ENT_QUOTES) ?>">
                <input type="hidden" name="action" value="cat_delete">
                <input type="hidden" name="id" value="<?= (int)$selected["id"] ?>">
            </form>

            <!-- Upload panel -->
            <div class="glass rounded-2xl p-5">
                <h3 class="font-semibold mb-4">
                    <i class="fa-solid fa-cloud-arrow-up mr-1"></i>
                    Add media to <span class="grad-text"><?= htmlspecialchars($selected["name"]) ?></span>
                </h3>

                <!-- Type tabs -->
                <div class="flex flex-wrap gap-2 mb-4">
                    <?php
                    $tabs = [
                        "image" => ["fa-image",     "Image"],
                        "logo"  => ["fa-copyright", "Logo"],
                        "icon"  => ["fa-icons",     "Icon / SVG"],
                        "video" => ["fa-film",       "Video"],
                    ];
                    foreach ($tabs as $t => [$ico, $lbl]):
                    ?>
                    <button type="button"
                        onclick="switchUploadTab('<?= $t ?>')"
                        id="tab-<?= $t ?>"
                        class="btn btn-ghost text-xs tab-btn <?= $t === 'image' ? 'ring-1 ring-violet-400' : '' ?>">
                        <i class="fa-solid <?= $ico ?>"></i> <?= $lbl ?>
                    </button>
                    <?php endforeach; ?>
                </div>

                <!-- Image / Logo / Icon upload -->
                <form method="POST" enctype="multipart/form-data" id="form-image" class="upload-form flex flex-col sm:flex-row gap-3 items-start sm:items-center">
                    <input type="hidden" name="_csrf" value="<?= htmlspecialchars(Csrf::token(), ENT_QUOTES) ?>">
                    <input type="hidden" name="action" value="img_upload">
                    <input type="hidden" name="category_id" value="<?= (int)$selected["id"] ?>">
                    <input type="hidden" name="media_type" id="hidden-media-type" value="image">
                    <input type="file" name="images[]" multiple accept="image/*,.svg" class="input flex-1">
                    <button class="btn btn-primary"><i class="fa-solid fa-upload"></i> Upload</button>
                </form>

                <!-- Video upload -->
                <form method="POST" enctype="multipart/form-data" id="form-video" class="upload-form hidden flex-col sm:flex-row gap-3 items-start sm:items-center">
                    <input type="hidden" name="_csrf" value="<?= htmlspecialchars(Csrf::token(), ENT_QUOTES) ?>">
                    <input type="hidden" name="action" value="img_upload">
                    <input type="hidden" name="category_id" value="<?= (int)$selected["id"] ?>">
                    <input type="hidden" name="media_type" value="video">
                    <input type="file" name="video_file" accept="video/mp4,video/webm,video/ogg" class="input flex-1">
                    <button class="btn btn-primary"><i class="fa-solid fa-upload"></i> Upload Video</button>
                </form>
            </div>

            <!-- Media grid -->
            <div class="glass rounded-2xl p-5">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="font-semibold">
                        <?= count($images) ?> item<?= count($images) === 1 ? '' : 's' ?>
                    </h3>
                    <?php if ($images):
                        $catTypeCounts = [];
                        foreach ($images as $img) {
                            $mt = $img["media_type"] ?? "image";
                            $catTypeCounts[$mt] = ($catTypeCounts[$mt] ?? 0) + 1;
                        }
                    ?>
                    <div class="flex gap-2 flex-wrap">
                        <?php foreach ($catTypeCounts as $mt => $n):
                            $badgeClass = match($mt) {
                                "logo"  => "bg-purple-500/20 text-purple-300",
                                "video" => "bg-red-500/20 text-red-300",
                                "icon"  => "bg-amber-500/20 text-amber-300",
                                default => "bg-blue-500/20 text-blue-300",
                            };
                        ?>
                        <span class="text-xs px-2 py-0.5 rounded-full <?= $badgeClass ?>"><?= ucfirst($mt) ?>: <?= $n ?></span>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </div>

                <?php if (!$images): ?>
                    <div class="text-center py-14 text-white/40">
                        <i class="fa-solid fa-photo-film text-6xl text-amber-400/20 mb-4 block"></i>
                        <div class="text-lg">No media in this category yet.</div>
                        <div class="text-sm mt-1">Use the upload panel above to add images, logos, icons or videos.</div>
                    </div>
                <?php else: ?>
                    <div class="grid sm:grid-cols-3 lg:grid-cols-4 gap-3">
                        <?php foreach ($images as $img):
                            $mt         = $img["media_type"] ?? "image";
                            $fn         = $img["filename"]   ?? "";
                            $ext        = strtolower(pathinfo($fn, PATHINFO_EXTENSION));
                            $vpath      = (string)($img["video_path"] ?? "");
                            $projId     = (int)($img["project_id"] ?? 0);

                            // Detect YouTube / Vimeo URL for thumbnail generation.
                            $ytId = "";
                            if ($mt === "video" && preg_match('/(?:youtube\.com\/(?:watch\?v=|embed\/|shorts\/)|youtu\.be\/)([\w-]{6,})/', $vpath, $m)) {
                                $ytId = $m[1];
                            }
                            $isLocalVideo = $mt === "video" && $fn !== "" && $ytId === "";

                            $badgeClass = match($mt) {
                                "logo"  => "bg-purple-500/20 text-purple-300",
                                "video" => "bg-red-500/20 text-red-300",
                                "icon"  => "bg-amber-500/20 text-amber-300",
                                default => "bg-blue-500/20 text-blue-300",
                            };
                            $badgeIcon = match($mt) {
                                "logo"  => "fa-copyright",
                                "video" => "fa-film",
                                "icon"  => "fa-icons",
                                default => "fa-image",
                            };
                        ?>
                        <div class="rounded-xl overflow-hidden border border-white/10 bg-black/30 flex flex-col">

                            <!-- Preview -->
                            <div class="aspect-square bg-black/60 relative flex items-center justify-center overflow-hidden">
                                <?php if ($mt === "video"): ?>
                                    <?php if ($ytId): ?>
                                        <!-- YouTube: show hi-res thumbnail -->
                                        <img src="https://img.youtube.com/vi/<?= htmlspecialchars($ytId) ?>/mqdefault.jpg"
                                             class="w-full h-full object-cover"
                                             alt="<?= htmlspecialchars($img["title"] ?? '') ?>"
                                             onerror="this.src='https://img.youtube.com/vi/<?= htmlspecialchars($ytId) ?>/default.jpg'">
                                        <a href="<?= htmlspecialchars($vpath) ?>" target="_blank" rel="noopener"
                                           class="absolute inset-0 flex items-center justify-center group/yt">
                                            <span class="w-12 h-12 rounded-full bg-red-600/90 flex items-center justify-center shadow-lg group-hover/yt:scale-110 transition-transform">
                                                <i class="fa-brands fa-youtube text-white text-xl"></i>
                                            </span>
                                        </a>
                                    <?php elseif ($isLocalVideo): ?>
                                        <!-- Local video file -->
                                        <video src="/uploads/videos/<?= rawurlencode($fn) ?>"
                                               class="w-full h-full object-cover"
                                               muted playsinline preload="metadata"
                                               onmouseover="this.play()" onmouseout="this.pause();this.currentTime=0;">
                                        </video>
                                        <div class="absolute inset-0 flex items-center justify-center pointer-events-none">
                                            <i class="fa-solid fa-circle-play text-white/50 text-4xl"></i>
                                        </div>
                                    <?php else: ?>
                                        <!-- Unknown video source -->
                                        <div class="flex flex-col items-center gap-2 text-white/30">
                                            <i class="fa-solid fa-film text-4xl"></i>
                                            <span class="text-[10px]">No preview</span>
                                        </div>
                                    <?php endif; ?>
                                <?php elseif ($ext === "svg" || $mt === "icon" || $mt === "logo"): ?>
                                    <img src="/uploads/images/<?= htmlspecialchars($fn) ?>"
                                         class="w-24 h-24 object-contain p-3"
                                         alt="<?= htmlspecialchars($img["alt_text"] ?? '') ?>">
                                <?php else: ?>
                                    <img src="/uploads/images/<?= htmlspecialchars($fn) ?>"
                                         class="w-full h-full object-cover"
                                         alt="<?= htmlspecialchars($img["alt_text"] ?? '') ?>">
                                <?php endif; ?>
                            </div>

                            <!-- Badges row -->
                            <div class="px-2 pt-2 flex flex-wrap gap-1">
                                <span class="inline-flex items-center gap-1 text-xs px-2 py-0.5 rounded-full <?= $badgeClass ?>">
                                    <i class="fa-solid <?= $badgeIcon ?> text-[10px]"></i>
                                    <?= $ytId ? "YouTube" : ucfirst($mt) ?>
                                </span>
                                <?php if ($projId): ?>
                                <a href="/admin/projects.php?edit=<?= $projId ?>"
                                   class="inline-flex items-center gap-1 text-xs px-2 py-0.5 rounded-full bg-violet-500/20 text-violet-300"
                                   title="View project">
                                    <i class="fa-solid fa-link text-[10px]"></i> Project
                                </a>
                                <?php endif; ?>
                            </div>

                            <!-- Edit form -->
                            <form method="POST" class="p-2 space-y-1.5 flex-1">
                                <input type="hidden" name="_csrf"       value="<?= htmlspecialchars(Csrf::token(), ENT_QUOTES) ?>">
                                <input type="hidden" name="action"      value="img_update">
                                <input type="hidden" name="id"          value="<?= (int)$img["id"] ?>">
                                <input type="hidden" name="category_id" value="<?= (int)$selected["id"] ?>">
                                <input type="hidden" name="media_type"  value="<?= htmlspecialchars($mt) ?>">
                                <input type="hidden" name="video_path"  value="<?= htmlspecialchars($vpath) ?>">
                                <input class="input text-xs" name="title"    value="<?= htmlspecialchars($img["title"]    ?? '') ?>" placeholder="Title">
                                <input class="input text-xs" name="alt_text" value="<?= htmlspecialchars($img["alt_text"] ?? '') ?>" placeholder="Alt / description">
                                <div class="flex gap-1">
                                    <input class="input text-xs flex-1" type="number" name="sort_order" value="<?= (int)$img["sort_order"] ?>">
                                    <button class="btn btn-ghost text-xs" title="Save"><i class="fa-solid fa-save"></i></button>
                                </div>
                            </form>

                            <!-- Delete -->
                            <form method="POST" class="px-2 pb-2"
                                  onsubmit="return confirm('<?= $projId ? 'This entry is auto-synced from a project. It will reappear when the project is updated. Delete it from the gallery now?' : 'Delete this item?' ?>')">
                                <input type="hidden" name="_csrf"       value="<?= htmlspecialchars(Csrf::token(), ENT_QUOTES) ?>">
                                <input type="hidden" name="action"      value="img_delete">
                                <input type="hidden" name="id"          value="<?= (int)$img["id"] ?>">
                                <input type="hidden" name="category_id" value="<?= (int)$selected["id"] ?>">
                                <?php if ($projId): ?>
                                    <a href="/admin/projects.php?edit=<?= $projId ?>"
                                       class="btn btn-ghost text-xs w-full justify-center mb-1">
                                        <i class="fa-solid fa-pen"></i> Edit in Projects
                                    </a>
                                <?php endif; ?>
                                <button class="btn btn-danger text-xs w-full justify-center"><i class="fa-solid fa-trash"></i> Delete</button>
                            </form>
                        </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

        <?php else: ?>
            <div class="glass rounded-2xl p-12 text-center text-white/60">
                <i class="fa-solid fa-photo-film text-7xl text-amber-400/20 mb-5 block"></i>
                <div class="text-xl font-semibold mb-2">Select a category to browse media</div>
                <div class="text-sm text-white/40">Or create a new category on the left to start adding images, logos, videos, and icons.</div>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
function switchUploadTab(type) {
    document.querySelectorAll('.upload-form').forEach(f => f.classList.add('hidden'));
    if (type === 'video') {
        document.getElementById('form-video').classList.remove('hidden');
    } else {
        const f = document.getElementById('form-image');
        f.classList.remove('hidden');
        f.querySelector('#hidden-media-type').value = type;
    }
    document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('ring-1','ring-violet-400'));
    document.getElementById('tab-' + type).classList.add('ring-1','ring-violet-400');
}
</script>

<?php admin_layout_end(); ?>
