<?php
declare(strict_types=1);

require __DIR__ . "/../bootstrap.php";
require __DIR__ . "/partials/layout.php";

use App\MediaScanner;
use App\Upload;

$tab = $_GET["tab"] ?? "images";
$tab = in_array($tab, ["images", "videos", "docs"], true) ? $tab : "images";

$summary = MediaScanner::summary();
$images  = $tab === "images" ? MediaScanner::images() : [];
$videos  = $tab === "videos" ? MediaScanner::videos() : [];
$docs    = $tab === "docs"   ? MediaScanner::docs()   : [];

admin_layout_start("Media & Files Hub", "media");
?>
<?= flash_render() ?>

<!-- ===== Summary tiles ===== -->
<div class="grid sm:grid-cols-3 gap-4 mb-6">
    <a href="?tab=images" class="glass rounded-2xl p-5 flex items-center gap-4 hover:bg-white/[.06] transition <?= $tab==='images' ? 'ring-2 ring-purple-500/50' : '' ?>">
        <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-indigo-500/30 to-purple-500/30 border border-indigo-300/20 flex items-center justify-center text-indigo-200">
            <i class="fa-solid fa-images text-xl"></i>
        </div>
        <div>
            <div class="text-2xl font-semibold leading-none"><?= (int)$summary["images"] ?></div>
            <div class="text-xs text-white/60 mt-1">Images, logos & avatars</div>
        </div>
    </a>
    <a href="?tab=videos" class="glass rounded-2xl p-5 flex items-center gap-4 hover:bg-white/[.06] transition <?= $tab==='videos' ? 'ring-2 ring-purple-500/50' : '' ?>">
        <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-rose-500/30 to-pink-500/30 border border-rose-300/20 flex items-center justify-center text-rose-200">
            <i class="fa-solid fa-circle-play text-xl"></i>
        </div>
        <div>
            <div class="text-2xl font-semibold leading-none"><?= (int)$summary["videos"] ?></div>
            <div class="text-xs text-white/60 mt-1">Project videos (local + embedded)</div>
        </div>
    </a>
    <a href="?tab=docs" class="glass rounded-2xl p-5 flex items-center gap-4 hover:bg-white/[.06] transition <?= $tab==='docs' ? 'ring-2 ring-purple-500/50' : '' ?>">
        <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-emerald-500/30 to-cyan-500/30 border border-emerald-300/20 flex items-center justify-center text-emerald-200">
            <i class="fa-solid fa-folder-open text-xl"></i>
        </div>
        <div>
            <div class="text-2xl font-semibold leading-none"><?= (int)$summary["docs"] ?></div>
            <div class="text-xs text-white/60 mt-1">Downloadable files (/uploads/docs/)</div>
        </div>
    </a>
</div>

<!-- ===== Tab content ===== -->
<?php if ($tab === "images"): ?>
    <div class="glass rounded-2xl p-5">
        <div class="flex items-center justify-between mb-4 flex-wrap gap-2">
            <h2 class="text-lg font-semibold"><i class="fa-solid fa-photo-film mr-1"></i> Media Gallery</h2>
            <p class="text-xs text-white/50">Auto-scanned from <code>projects</code>, <code>hero_content</code>, <code>clients</code>, <code>about_content</code>, <code>gallery_images</code> &amp; admin avatars.</p>
        </div>

        <?php if (!$images): ?>
            <div class="text-center py-10 text-white/40">No images referenced in the database yet.</div>
        <?php else: ?>
            <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-3">
                <?php foreach ($images as $img): ?>
                    <a href="<?= htmlspecialchars($img["url"]) ?>" target="_blank"
                       class="group rounded-xl overflow-hidden border border-white/10 bg-black/30 block">
                        <div class="aspect-square bg-black/60 relative">
                            <img src="<?= htmlspecialchars($img["url"]) ?>"
                                 alt="<?= htmlspecialchars($img["title"]) ?>"
                                 class="w-full h-full object-cover" loading="lazy"
                                 onerror="this.style.display='none';this.parentNode.classList.add('media-broken');">
                            <span class="absolute top-2 left-2 badge badge-info text-[10px]"><?= htmlspecialchars($img["kind"]) ?></span>
                        </div>
                        <div class="p-2">
                            <div class="text-xs text-white truncate font-medium"><?= htmlspecialchars($img["title"]) ?></div>
                            <div class="text-[10px] text-white/50 truncate"><?= htmlspecialchars($img["source"]) ?></div>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

<?php elseif ($tab === "videos"): ?>
    <div class="glass rounded-2xl p-5">
        <div class="flex items-center justify-between mb-4 flex-wrap gap-2">
            <h2 class="text-lg font-semibold"><i class="fa-solid fa-film mr-1"></i> Project videos</h2>
            <p class="text-xs text-white/50">Self-hosted videos play inline; YouTube/Vimeo links open externally.</p>
        </div>
        <?php if (!$videos): ?>
            <div class="text-center py-10 text-white/40">No videos referenced yet. Upload one from <a href="/admin/projects.php" class="underline">Projects</a>.</div>
        <?php else: ?>
            <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-4">
                <?php foreach ($videos as $v): ?>
                    <div class="rounded-xl overflow-hidden border border-white/10 bg-black/30">
                        <div class="aspect-video bg-black/70 relative">
                            <?php if ($v["kind"] === "local"): ?>
                                <video src="<?= htmlspecialchars($v["url"]) ?>"
                                       <?= $v["poster"] ? 'poster="'.htmlspecialchars($v["poster"]).'"' : '' ?>
                                       controls preload="metadata" class="w-full h-full object-contain bg-black"></video>
                            <?php else: ?>
                                <a href="<?= htmlspecialchars($v["url"]) ?>" target="_blank" class="absolute inset-0 flex items-center justify-center text-white/70 hover:text-white">
                                    <i class="fa-solid fa-up-right-from-square text-2xl"></i>
                                </a>
                            <?php endif; ?>
                            <span class="absolute top-2 left-2 badge <?= $v["kind"] === 'local' ? 'badge-success' : 'badge-info' ?> text-[10px]">
                                <?= $v["kind"] === 'local' ? 'Self-hosted' : 'External' ?>
                            </span>
                        </div>
                        <div class="p-3">
                            <div class="text-sm font-semibold text-white truncate"><?= htmlspecialchars($v["title"]) ?></div>
                            <div class="text-[11px] text-white/50 truncate" title="<?= htmlspecialchars($v["url"]) ?>"><?= htmlspecialchars($v["url"]) ?></div>
                            <div class="mt-2 flex gap-2">
                                <a href="/admin/projects.php?edit=<?= (int)$v["ref_id"] ?>" class="btn btn-ghost text-xs">
                                    <i class="fa-solid fa-pen"></i> Edit project
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

<?php else: /* docs */ ?>
    <div class="glass rounded-2xl p-5">
        <div class="flex items-center justify-between mb-4 flex-wrap gap-2">
            <div>
                <h2 class="text-lg font-semibold"><i class="fa-solid fa-folder-open mr-1"></i> File Gallery</h2>
                <p class="text-xs text-white/50 mt-1">Every file under <code>/uploads/docs/</code>. Manage tracked entries from <a href="/admin/files.php" class="underline">File Library</a>.</p>
            </div>
            <a href="/admin/files.php" class="btn btn-primary text-sm"><i class="fa-solid fa-plus"></i> Upload new</a>
        </div>

        <?php if (!$docs): ?>
            <div class="text-center py-10 text-white/40">No documents in <code>/uploads/docs/</code> yet.</div>
        <?php else: ?>
            <div class="overflow-x-auto">
            <table class="data">
                <thead>
                    <tr><th>File</th><th>Folder</th><th>Type</th><th>Size</th><th>Modified</th><th>Tracked</th><th></th></tr>
                </thead>
                <tbody>
                <?php foreach ($docs as $d):
                    $iconClass = match ($d["ext"]) {
                        "pdf"        => "fa-file-pdf text-rose-300",
                        "doc","docx" => "fa-file-word text-sky-300",
                        "xls","xlsx","csv" => "fa-file-excel text-emerald-300",
                        "ppt","pptx" => "fa-file-powerpoint text-orange-300",
                        "zip","rar"  => "fa-file-zipper text-amber-300",
                        "txt"        => "fa-file-lines text-white/70",
                        default      => "fa-file text-white/60",
                    };
                ?>
                    <tr>
                        <td>
                            <div class="flex items-center gap-2 min-w-0">
                                <i class="fa-solid <?= $iconClass ?> w-5 text-center"></i>
                                <div class="min-w-0">
                                    <div class="text-sm font-medium text-white truncate max-w-xs"><?= htmlspecialchars($d["title"]) ?></div>
                                    <div class="text-[11px] text-white/50 truncate max-w-xs"><?= htmlspecialchars($d["original_name"]) ?></div>
                                </div>
                            </div>
                        </td>
                        <td><span class="badge badge-info"><?= htmlspecialchars($d["folder"]) ?></span></td>
                        <td class="text-xs text-white/70 uppercase">.<?= htmlspecialchars($d["ext"]) ?></td>
                        <td class="text-xs text-white/70"><?= Upload::humanSize($d["size_bytes"]) ?></td>
                        <td class="text-xs text-white/60"><?= htmlspecialchars($d["modified_at"]) ?></td>
                        <td>
                            <?php if ($d["tracked"]): ?>
                                <span class="badge badge-success"><i class="fa-solid fa-check"></i> Yes</span>
                            <?php else: ?>
                                <span class="badge badge-warn">Untracked</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-right">
                            <a href="<?= htmlspecialchars($d["url"]) ?>" target="_blank" class="btn btn-ghost text-xs" title="Download">
                                <i class="fa-solid fa-download"></i>
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            </div>
        <?php endif; ?>
    </div>
<?php endif; ?>

<style>
    .media-broken::after {
        content: "image unavailable";
        position: absolute; inset: 0;
        display: flex; align-items: center; justify-content: center;
        font-size: 11px; color: rgba(255,255,255,.4);
        background: rgba(0,0,0,.45);
    }
</style>
<?php admin_layout_end(); ?>
