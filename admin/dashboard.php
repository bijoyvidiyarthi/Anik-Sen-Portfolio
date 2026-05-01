<?php
declare(strict_types=1);

require __DIR__ . "/../bootstrap.php";
require __DIR__ . "/partials/layout.php";

use App\Project;
use App\Skill;
use App\GalleryImage;
use App\GalleryCategory;
use App\FileLibrary;
use App\Message;
use App\Database;
use App\Visitor;

admin_layout_start("Dashboard", "dashboard");

$projects   = count(Project::all(false));
$skills     = count(Skill::all());
$galleries  = count(GalleryCategory::all());
$images     = GalleryImage::totalCount();
$files      = count(FileLibrary::all());
$messagesT  = Message::totalCount();
$unread     = Message::unreadCount();
$visUnique  = Visitor::totalUnique();
$visToday   = Visitor::todayUnique();

$recent = Database::pdo()->query(
    "SELECT id, name, email, subject, created_at, is_read FROM messages ORDER BY created_at DESC LIMIT 5"
)->fetchAll();

$recentProjects = Database::pdo()->query(
    "SELECT id, title, category, created_at FROM projects ORDER BY id DESC LIMIT 5"
)->fetchAll();

$cards = [
    ["label" => "Unique Visitors", "val" => $visUnique,  "icon" => "fa-chart-line",     "grad" => "from-fuchsia-500 to-violet-500", "href" => "#visitor-analytics"],
    ["label" => "Visits Today",    "val" => $visToday,   "icon" => "fa-eye",            "grad" => "from-rose-500 to-orange-500",    "href" => "#visitor-analytics"],
    ["label" => "Projects",        "val" => $projects,   "icon" => "fa-briefcase",      "grad" => "from-indigo-500 to-blue-500",    "href" => "/admin/projects.php"],
    ["label" => "Galleries",       "val" => $galleries,  "icon" => "fa-images",         "grad" => "from-pink-500 to-rose-500",      "href" => "/admin/gallery.php"],
    ["label" => "Messages",        "val" => $messagesT,  "icon" => "fa-inbox",          "grad" => "from-cyan-500 to-sky-500",       "href" => "/admin/messages.php"],
    ["label" => "Files",           "val" => $files,      "icon" => "fa-folder-open",    "grad" => "from-emerald-500 to-teal-500",   "href" => "/admin/files.php"],
];
?>
<?= flash_render() ?>

<div class="grid grid-cols-2 sm:grid-cols-3 xl:grid-cols-6 gap-4 mb-6">
    <?php foreach ($cards as $c): ?>
        <a href="<?= $c["href"] ?>" class="glass rounded-2xl p-4 hover:scale-[1.02] transition">
            <div class="flex items-center justify-between">
                <div class="w-10 h-10 rounded-xl bg-gradient-to-br <?= $c["grad"] ?> flex items-center justify-center shadow-lg">
                    <i class="fa-solid <?= $c["icon"] ?> text-white"></i>
                </div>
                <i class="fa-solid fa-arrow-up-right-from-square text-white/30 text-sm"></i>
            </div>
            <div class="mt-3 text-2xl font-bold text-white"><?= number_format($c["val"]) ?></div>
            <div class="text-xs text-white/60"><?= $c["label"] ?></div>
        </a>
    <?php endforeach; ?>
</div>

<!-- ==================== VISITOR ANALYTICS ==================== -->
<section id="visitor-analytics" class="glass rounded-2xl p-6 mb-6">
    <div class="flex items-center justify-between mb-4 flex-wrap gap-3">
        <div>
            <h2 class="text-lg font-semibold flex items-center gap-2">
                <i class="fa-solid fa-chart-line text-violet-300"></i> Unique visitor analytics
            </h2>
            <p class="text-xs text-white/50 mt-1">
                Same visitor in a 24-hour window counts once. IP addresses are SHA-256 hashed before storage.
            </p>
        </div>
        <div class="inline-flex rounded-xl bg-white/5 border border-white/10 p-1" role="tablist" aria-label="Time range">
            <button type="button" data-range="weekly"  class="va-tab px-4 py-1.5 text-xs rounded-lg font-medium transition">Weekly</button>
            <button type="button" data-range="monthly" class="va-tab px-4 py-1.5 text-xs rounded-lg font-medium transition">Monthly</button>
            <button type="button" data-range="yearly"  class="va-tab px-4 py-1.5 text-xs rounded-lg font-medium transition">Yearly</button>
        </div>
    </div>

    <div class="grid grid-cols-3 gap-3 mb-4">
        <div class="rounded-xl bg-white/5 border border-white/10 p-3">
            <div class="text-[11px] uppercase tracking-wider text-white/50">In range</div>
            <div id="vaTotal" class="mt-1 text-xl font-bold text-white">—</div>
        </div>
        <div class="rounded-xl bg-white/5 border border-white/10 p-3">
            <div class="text-[11px] uppercase tracking-wider text-white/50">Peak day</div>
            <div id="vaPeak" class="mt-1 text-xl font-bold text-white">—</div>
        </div>
        <div class="rounded-xl bg-white/5 border border-white/10 p-3">
            <div class="text-[11px] uppercase tracking-wider text-white/50">Average</div>
            <div id="vaAvg" class="mt-1 text-xl font-bold text-white">—</div>
        </div>
    </div>

    <div class="relative h-64">
        <canvas id="visitorChart"></canvas>
        <div id="vaEmpty" class="hidden absolute inset-0 flex flex-col items-center justify-center text-white/40 text-sm">
            <i class="fa-solid fa-chart-area text-3xl mb-2"></i>
            No visitor data for this range yet.
        </div>
    </div>
</section>

<div class="grid lg:grid-cols-3 gap-5">
    <div class="glass rounded-2xl p-5 lg:col-span-2">
        <div class="flex items-center justify-between mb-3">
            <h2 class="text-base font-semibold flex items-center gap-2">
                <i class="fa-regular fa-envelope text-pink-400"></i> Recent Messages
                <?php if ($unread): ?>
                    <span class="badge badge-warn"><?= $unread ?> unread</span>
                <?php endif; ?>
            </h2>
            <a href="/admin/messages.php" class="text-xs text-white/60 hover:text-white">View all →</a>
        </div>
        <?php if (!$recent): ?>
            <div class="text-sm text-white/50 py-6 text-center">No messages yet.</div>
        <?php else: ?>
            <ul class="divide-y divide-white/5">
                <?php foreach ($recent as $m): ?>
                    <li class="py-3 flex items-center gap-3">
                        <div class="w-9 h-9 rounded-full bg-gradient-to-br from-indigo-500 to-pink-500 flex items-center justify-center text-xs font-bold">
                            <?= strtoupper(htmlspecialchars(substr($m["name"], 0, 1))) ?>
                        </div>
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center gap-2">
                                <span class="font-semibold text-sm text-white truncate"><?= htmlspecialchars($m["name"]) ?></span>
                                <?php if (!$m["is_read"]): ?><span class="badge badge-info">new</span><?php endif; ?>
                            </div>
                            <div class="text-xs text-white/60 truncate"><?= htmlspecialchars($m["subject"]) ?></div>
                        </div>
                        <div class="text-xs text-white/40 whitespace-nowrap"><?= htmlspecialchars(date("M j", strtotime((string) $m["created_at"]))) ?></div>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </div>

    <div class="glass rounded-2xl p-5">
        <div class="flex items-center justify-between mb-3">
            <h2 class="text-base font-semibold flex items-center gap-2"><i class="fa-solid fa-briefcase text-indigo-400"></i> Latest Projects</h2>
            <a href="/admin/projects.php" class="text-xs text-white/60 hover:text-white">Manage →</a>
        </div>
        <?php if (!$recentProjects): ?>
            <div class="text-sm text-white/50 py-6 text-center">No projects yet.</div>
        <?php else: ?>
            <ul class="space-y-2">
                <?php foreach ($recentProjects as $p): ?>
                    <li class="flex items-center justify-between py-2 px-3 rounded-lg hover:bg-white/5">
                        <div class="min-w-0">
                            <div class="font-medium text-sm text-white truncate"><?= htmlspecialchars($p["title"]) ?></div>
                            <div class="text-xs text-white/50"><?= htmlspecialchars($p["category"]) ?></div>
                        </div>
                        <a href="/admin/projects.php?edit=<?= (int)$p["id"] ?>" class="text-white/40 hover:text-white text-sm">
                            <i class="fa-solid fa-pen"></i>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </div>
</div>

<div class="mt-6 glass rounded-2xl p-5">
    <h2 class="text-base font-semibold flex items-center gap-2 mb-3">
        <i class="fa-solid fa-bolt text-amber-400"></i> Quick Actions
    </h2>
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
        <a href="/admin/projects.php?new=1" class="btn btn-ghost justify-center"><i class="fa-solid fa-plus"></i> Add Project</a>
        <a href="/admin/gallery.php" class="btn btn-ghost justify-center"><i class="fa-solid fa-image"></i> Add Gallery Image</a>
        <a href="/admin/sections.php" class="btn btn-ghost justify-center"><i class="fa-solid fa-toggle-on"></i> Sections & Menus</a>
        <a href="/admin/settings.php" class="btn btn-ghost justify-center"><i class="fa-solid fa-sliders"></i> Site Settings</a>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>
<style>
    .va-tab        { color: rgba(255,255,255,0.6); }
    .va-tab:hover  { color: #fff; }
    .va-tab.active { background: rgba(255,255,255,0.10); color: #fff; box-shadow: inset 0 0 0 1px rgba(255,255,255,0.10); }
</style>
<script>
(function () {
    const canvas = document.getElementById("visitorChart");
    if (!canvas || typeof Chart === "undefined") return;

    const ctx     = canvas.getContext("2d");
    const tabs    = document.querySelectorAll(".va-tab");
    const elTotal = document.getElementById("vaTotal");
    const elPeak  = document.getElementById("vaPeak");
    const elAvg   = document.getElementById("vaAvg");
    const elEmpty = document.getElementById("vaEmpty");

    const grad = ctx.createLinearGradient(0, 0, 0, 260);
    grad.addColorStop(0, "rgba(168, 85, 247, 0.55)");
    grad.addColorStop(1, "rgba(99, 102, 241, 0.04)");

    const chart = new Chart(ctx, {
        type: "line",
        data: {
            labels: [],
            datasets: [{
                label: "Unique visitors",
                data: [],
                borderColor: "#a855f7",
                backgroundColor: grad,
                pointBackgroundColor: "#ec4899",
                pointBorderColor: "#fff",
                pointRadius: 4,
                pointHoverRadius: 6,
                borderWidth: 2.5,
                tension: 0.35,
                fill: true,
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: { mode: "index", intersect: false },
            plugins: {
                legend: { display: false },
                tooltip: {
                    backgroundColor: "rgba(15, 17, 32, 0.95)",
                    titleColor: "#fff", bodyColor: "#e5e7eb",
                    borderColor: "rgba(255,255,255,0.1)", borderWidth: 1,
                    padding: 10, displayColors: false,
                }
            },
            scales: {
                x: {
                    grid:   { color: "rgba(255,255,255,0.04)" },
                    ticks:  { color: "rgba(255,255,255,0.55)", maxRotation: 0 }
                },
                y: {
                    beginAtZero: true,
                    grid:   { color: "rgba(255,255,255,0.06)" },
                    ticks:  { color: "rgba(255,255,255,0.55)", precision: 0 }
                }
            }
        }
    });

    function setActive(range) {
        tabs.forEach(t => t.classList.toggle("active", t.dataset.range === range));
    }

    async function load(range) {
        try {
            const res  = await fetch("/admin/api/visitors.php?range=" + encodeURIComponent(range), {
                credentials: "same-origin",
                headers: { "Accept": "application/json" }
            });
            if (!res.ok) throw new Error("HTTP " + res.status);
            const data = await res.json();
            if (!data.success) throw new Error(data.error || "Bad response");

            chart.data.labels       = data.labels;
            chart.data.datasets[0].data = data.counts;
            chart.update();

            const counts = data.counts || [];
            const total  = data.total ?? counts.reduce((a, b) => a + b, 0);
            const peakI  = counts.length ? counts.indexOf(Math.max(...counts)) : -1;
            const avg    = counts.length ? (total / counts.length) : 0;

            elTotal.textContent = total.toLocaleString();
            elPeak.textContent  = peakI >= 0
                ? `${counts[peakI].toLocaleString()} on ${data.labels[peakI]}`
                : "—";
            elAvg.textContent   = avg.toFixed(avg >= 10 ? 0 : 1);

            elEmpty.classList.toggle("hidden", total > 0);
        } catch (err) {
            elTotal.textContent = elPeak.textContent = elAvg.textContent = "—";
            elEmpty.classList.remove("hidden");
            elEmpty.innerHTML = '<i class="fa-solid fa-triangle-exclamation text-3xl mb-2 text-rose-300"></i>Could not load analytics.';
        }
    }

    tabs.forEach(t => t.addEventListener("click", () => {
        setActive(t.dataset.range);
        load(t.dataset.range);
    }));

    setActive("weekly");
    load("weekly");
})();
</script>
<?php admin_layout_end(); ?>
