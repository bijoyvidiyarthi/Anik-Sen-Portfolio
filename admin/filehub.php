<?php
declare(strict_types=1);

require __DIR__ . "/../bootstrap.php";
require __DIR__ . "/partials/layout.php";

use App\Database;

$tab = $_GET["tab"] ?? "images";
$tab = in_array($tab, ["images", "videos", "docs"], true) ? $tab : "images";

$pdo = Database::pdo();

/**
 * local filename check
 */
function isLocalName(?string $val): bool {
    if (!$val) return false;
    return preg_match('/^\w[\w.\-]+$/', $val) === 1;
}

/* ---- 1. Count ---- */
$counts = ['images' => 0, 'videos' => 0, 'docs' => 0];
try {
    $counts['images'] = (int)$pdo->query("SELECT COUNT(*) FROM projects WHERE image IS NOT NULL AND image != ''")->fetchColumn();
    $counts['videos'] = (int)$pdo->query("SELECT COUNT(*) FROM projects WHERE (video_file IS NOT NULL AND video_file != '') OR (video_url IS NOT NULL AND video_url != '')")->fetchColumn();
    $counts['docs']   = (int)$pdo->query("SELECT COUNT(*) FROM file_library")->fetchColumn();
} catch (\Throwable $e) {
    //  error_log($e->getMessage());
}

/* ---- 2. Fetch Data  ---- */
$images = [];
$videos = [];
$docs   = [];

if ($tab === "images") {
    // Image Source configuration
    $imageSources = [
        ['sql' => "SELECT title as label, image as file, 'Cover' as type FROM projects", 'dir' => '/uploads/images/'],
        ['sql' => "SELECT p.title as label, pi.filename as file, 'Gallery' as type FROM project_images pi LEFT JOIN projects p ON p.id = pi.project_id", 'dir' => '/uploads/images/'],
        ['sql' => "SELECT name as label, avatar as file, 'Hero' as type FROM hero_content", 'dir' => '/uploads/images/'],
        ['sql' => "SELECT 'About' as label, profile_image as file, 'Profile' as type FROM about_content", 'dir' => '/uploads/images/'],
        ['sql' => "SELECT name as label, logo as file, 'Client' as type FROM clients", 'dir' => '/uploads/images/'],
        ['sql' => "SELECT COALESCE(full_name, username) as label, profile_pic as file, 'Admin' as type FROM admin_users", 'dir' => '/uploads/admins/'],
        ['sql' => "SELECT title as label, filename as file, 'Gallery' as type FROM gallery_images", 'dir' => '/uploads/images/']
    ];

    foreach ($imageSources as $source) {
        try {
            foreach ($pdo->query($source['sql'])->fetchAll() as $r) {
                if (isLocalName($r['file'])) {
                    $images[] = [
                        "url"   => $source['dir'] . rawurlencode($r['file']),
                        "title" => $r['type'] . " — " . ($r['label'] ?: 'Untitled'),
                        "kind"  => strtolower($r['type'])
                    ];
                }
            }
        } catch (\Throwable $e) {}
    }
} elseif ($tab === "videos") {
    try {
        $stmt = $pdo->query("SELECT id, title, video_url, video_file, video_poster FROM projects 
                             WHERE (video_file != '' AND video_file IS NOT NULL) 
                             OR (video_url != '' AND video_url IS NOT NULL)");
        foreach ($stmt->fetchAll() as $r) {
            $hasFile = isLocalName($r["video_file"]);
            $poster  = isLocalName($r["video_poster"]) ? "/uploads/images/" . rawurlencode($r["video_poster"]) : null;
            
            $videos[] = [
                "id"        => (int)$r["id"],
                "title"     => (string)$r["title"],
                "local_url" => $hasFile ? "/uploads/videos/" . rawurlencode($r["video_file"]) : null,
                "ext_b64"   => (!$hasFile && !empty($r["video_url"])) ? base64_encode($r["video_url"]) : null,
                "kind"      => $hasFile ? "local" : "external",
                "poster"    => $poster
            ];
        }
    } catch (\Throwable $e) {}
} elseif ($tab === "docs") {
    try {
        foreach ($pdo->query("SELECT * FROM file_library ORDER BY created_at DESC")->fetchAll() as $r) {
            $docs[] = [
                "url"           => "/uploads/docs/" . rawurlencode($r["filename"]),
                "title"         => $r["title"],
                "folder"        => $r["folder"] ?? "general",
                "original_name" => $r["original_name"] ?: $r["filename"],
                "size"          => number_format($r["size_bytes"] / 1024, 1) . " KB",
                "ext"           => strtolower(pathinfo($r["filename"], PATHINFO_EXTENSION)),
                "date"          => $r["created_at"]
            ];
        }
    } catch (\Throwable $e) {}
}

admin_layout_start("Media & Files Hub", "media");
?>

<?= flash_render() ?>

<!-- Status Cards -->
<div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-8">
    <?php 
    $cards = [
        ['id' => 'images', 'label' => 'Images', 'count' => $counts['images'], 'icon' => 'fa-images', 'color' => 'from-indigo-500/30 to-purple-500/30', 'text' => 'text-indigo-200'],
        ['id' => 'videos', 'label' => 'Videos', 'count' => $counts['videos'], 'icon' => 'fa-circle-play', 'color' => 'from-rose-500/30 to-pink-500/30', 'text' => 'text-rose-200'],
        ['id' => 'docs',   'label' => 'Documents', 'count' => $counts['docs'],   'icon' => 'fa-folder-open', 'color' => 'from-emerald-500/30 to-cyan-500/30', 'text' => 'text-emerald-200'],
    ];
    foreach ($cards as $card): ?>
        <a href="?tab=<?= $card['id'] ?>" class="glass rounded-2xl p-5 flex items-center gap-4 hover:scale-[1.02] transition-all <?= $tab === $card['id'] ? 'ring-2 ring-white/30 bg-white/10' : '' ?>">
            <div class="w-12 h-12 rounded-xl bg-gradient-to-br <?= $card['color'] ?> border border-white/10 flex items-center justify-center <?= $card['text'] ?>">
                <i class="fa-solid <?= $card['icon'] ?> text-xl"></i>
            </div>
            <div>
                <div class="text-2xl font-bold leading-none"><?= $card['count'] ?></div>
                <div class="text-xs text-white/50 mt-1"><?= $card['label'] ?></div>
            </div>
        </a>
    <?php endforeach; ?>
</div>

<!-- Content Section -->
<div class="glass rounded-2xl p-6 min-h-[400px]">
    <?php if ($tab === "images"): ?>
        <div class="flex justify-between items-center mb-6">
            <h2 class="text-xl font-bold italic"><i class="fa-solid fa-camera-retro mr-2"></i> Image Assets</h2>
        </div>
        
        <?php if (!$images): ?>
            <div class="flex flex-col items-center justify-center py-20 text-white/30">
                <i class="fa-solid fa-image-portrait text-5xl mb-4"></i>
                <p>No images found in the database.</p>
            </div>
        <?php else: ?>
            <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-6 gap-4">
                <?php foreach ($images as $img): ?>
                    <div class="group relative rounded-xl overflow-hidden bg-black/40 border border-white/5 hover:border-white/20 transition-all">
                        <div class="aspect-square">
                            <img src="<?= htmlspecialchars($img["url"]) ?>" alt="" class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-500" loading="lazy" onerror="this.src='/assets/img/placeholder.jpg'">
                        </div>
                        <div class="absolute inset-0 bg-gradient-to-t from-black/80 via-transparent to-transparent opacity-0 group-hover:opacity-100 transition-opacity flex flex-col justify-end p-3">
                            <span class="text-[10px] uppercase tracking-wider text-white/50"><?= $img['kind'] ?></span>
                            <div class="text-xs text-white font-medium truncate"><?= htmlspecialchars($img["title"]) ?></div>
                            <a href="<?= htmlspecialchars($img["url"]) ?>" target="_blank" class="mt-2 text-[10px] bg-white/20 hover:bg-white/40 text-center py-1 rounded">View Full</a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

    <?php elseif ($tab === "videos"): ?>
        <h2 class="text-xl font-bold mb-6 italic"><i class="fa-solid fa-clapperboard mr-2"></i> Video Gallery</h2>
        <div class="grid md:grid-cols-2 xl:grid-cols-3 gap-6">
            <?php foreach ($videos as $v): ?>
                <div class="glass bg-black/20 rounded-2xl overflow-hidden border border-white/10">
                    <div class="aspect-video bg-black relative">
                        <?php if ($v["kind"] === "local"): ?>
                            <video src="<?= htmlspecialchars($v["local_url"]) ?>" poster="<?= $v["poster"] ? htmlspecialchars($v["poster"]) : '' ?>" controls class="w-full h-full"></video>
                        <?php else: ?>
                            <div class="w-full h-full flex flex-col items-center justify-center gap-3">
                                <i class="fa-solid fa-link text-3xl text-white/20"></i>
                                <a href="#" data-xref="<?= $v["ext_b64"] ?>" target="_blank" class="btn btn-sm btn-outline border-white/20 text-white">External Link</a>
                            </div>
                        <?php endif; ?>
                        <span class="absolute top-3 left-3 badge badge-sm <?= $v["kind"] === 'local' ? 'badge-success' : 'badge-info' ?>"><?= ucfirst($v["kind"]) ?></span>
                    </div>
                    <div class="p-4 flex justify-between items-center">
                        <span class="font-medium text-sm truncate pr-2"><?= htmlspecialchars($v["title"]) ?></span>
                        <a href="/admin/projects.php?edit=<?= $v["id"] ?>" class="text-white/40 hover:text-white transition"><i class="fa-solid fa-pen-to-square"></i></a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

    <?php else: ?>
        <div class="flex justify-between items-center mb-6">
            <h2 class="text-xl font-bold italic"><i class="fa-solid fa-file-invoice mr-2"></i> Document Library</h2>
            <a href="/admin/files.php" class="btn btn-primary btn-sm rounded-lg"><i class="fa-solid fa-upload mr-1"></i> Upload</a>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left border-separate border-spacing-y-2">
                <thead>
                    <tr class="text-white/40 text-xs uppercase tracking-widest">
                        <th class="px-4 py-2">File Info</th>
                        <th class="px-4 py-2">Category</th>
                        <th class="px-4 py-2">Size</th>
                        <th class="px-4 py-2 text-right">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($docs as $d): ?>
                        <tr class="bg-white/5 hover:bg-white/10 transition-colors">
                            <td class="px-4 py-3 rounded-l-xl">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 rounded-lg bg-white/5 flex items-center justify-center text-xl">
                                        <?php 
                                            $icon = match($d['ext']) {
                                                'pdf' => 'fa-file-pdf text-red-400',
                                                'doc','docx' => 'fa-file-word text-blue-400',
                                                'xls','xlsx' => 'fa-file-excel text-green-400',
                                                'zip','7z' => 'fa-file-zipper text-yellow-400',
                                                default => 'fa-file-lines text-gray-400'
                                            };
                                        ?>
                                        <i class="fa-solid <?= $icon ?>"></i>
                                    </div>
                                    <div class="max-w-[200px]">
                                        <div class="text-sm font-bold truncate"><?= htmlspecialchars($d['title']) ?></div>
                                        <div class="text-[10px] text-white/40 truncate"><?= htmlspecialchars($d['original_name']) ?></div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-4 py-3"><span class="badge badge-ghost text-[10px] uppercase"><?= htmlspecialchars($d['folder']) ?></span></td>
                            <td class="px-4 py-3 text-xs text-white/50"><?= $d['size'] ?></td>
                            <td class="px-4 py-3 text-right rounded-r-xl">
                                <a href="<?= htmlspecialchars($d['url']) ?>" target="_blank" class="btn btn-circle btn-ghost btn-xs text-info"><i class="fa-solid fa-download"></i></a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<script>
/** 
 * Base64 decoding & External link handling
 */
document.querySelectorAll('[data-xref]').forEach(el => {
    const b64 = el.getAttribute('data-xref');
    if (b64) {
        const url = atob(b64);
        if (el.tagName === 'A') el.href = url;
        if (el.tagName === 'IMG') el.src = url;
    }
});
</script>

<?php admin_layout_end(); ?>