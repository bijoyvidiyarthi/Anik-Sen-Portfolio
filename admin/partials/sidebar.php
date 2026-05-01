<?php
/** @var string $active */
use App\Auth;
use App\Settings;

$siteName = Settings::get("site_name", "Portfolio");
$me       = Auth::user() ?? [];
$isMain   = Auth::isMain();

$displayName = (string)($me["full_name"] ?? $me["username"] ?? "Admin");
$roleLabel   = ($me["role"] ?? "sub") === "main" ? "Main administrator" : "Sub-admin";
$initial     = strtoupper(mb_substr($displayName, 0, 1));
$avatarUrl   = !empty($me["profile_pic"])
    ? "/uploads/admins/" . rawurlencode((string)$me["profile_pic"])
    : null;

$nav = [
    ["dashboard", "/admin/",              "fa-gauge-high",       "Dashboard"],
    ["settings",  "/admin/settings.php",  "fa-sliders",          "Site Settings"],
    ["uitext",    "/admin/uitext.php",    "fa-language",         "UI Text & Labels"],
    ["hero",      "/admin/hero.php",      "fa-star",             "Hero Section"],
    ["about",     "/admin/about.php",     "fa-user-pen",         "About / Bio"],
    ["projects",  "/admin/projects.php",  "fa-briefcase",        "Projects"],
    ["skills",    "/admin/skills.php",    "fa-layer-group",      "Skills"],
    ["education", "/admin/education.php", "fa-graduation-cap",   "Education"],
    ["reviews",   "/admin/reviews.php",   "fa-star-half-stroke", "Reviews"],
    ["clients",   "/admin/clients.php",   "fa-handshake",        "Trusted Clients"],
    ["gallery",   "/admin/gallery.php",   "fa-images",           "Image Gallery"],
    ["media",     "/admin/media.php",     "fa-photo-film",       "Media & Files Hub"],
    ["files",     "/admin/files.php",     "fa-folder-open",      "File Library"],
    ["messages",  "/admin/messages.php",  "fa-inbox",            "Messages"],
    ["sections",  "/admin/sections.php",  "fa-toggle-on",        "Sections & Menus"],
    ["account",   "/admin/account.php",   "fa-user-shield",      "My Account"],
];
// Manage Users — visible only to the main administrator.
if ($isMain) {
    $nav[] = ["users", "/admin/users.php", "fa-users-gear", "Manage Users"];
}
?>
<aside id="sidebar"
       class="glass-strong w-72 shrink-0 border-r border-white/5 px-5 py-6
              lg:sticky lg:top-0
              h-screen lg:h-screen overflow-y-auto sidebar-scroll">
    <div class="flex items-center gap-3 mb-6">
        <div class="w-11 h-11 rounded-2xl bg-gradient-to-br from-indigo-500 via-purple-500 to-pink-500 flex items-center justify-center shadow-lg">
            <i class="fa-solid fa-wand-magic-sparkles text-white"></i>
        </div>
        <div class="min-w-0">
            <div class="font-semibold text-white leading-none truncate"><?= htmlspecialchars($siteName) ?></div>
            <div class="text-xs text-white/50 mt-1">CMS Console</div>
        </div>
    </div>

    <!-- Logged-in admin profile card -->
    <a href="/admin/account.php"
       class="flex items-center gap-3 p-3 mb-5 rounded-xl border border-white/10 bg-white/[.04] hover:bg-white/[.07] transition group">
        <?php if ($avatarUrl): ?>
            <img src="<?= htmlspecialchars($avatarUrl) ?>"
                 alt="Profile picture"
                 class="w-11 h-11 rounded-xl object-cover border border-white/10 shrink-0">
        <?php else: ?>
            <div class="w-11 h-11 rounded-xl bg-gradient-to-br from-indigo-500 via-purple-500 to-pink-500 flex items-center justify-center font-bold text-white shrink-0">
                <?= htmlspecialchars($initial) ?>
            </div>
        <?php endif; ?>
        <div class="min-w-0 flex-1">
            <div class="text-sm font-semibold text-white truncate"><?= htmlspecialchars($displayName) ?></div>
            <div class="text-[11px] text-white/50 truncate">
                <i class="fa-solid <?= $isMain ? 'fa-crown text-amber-300' : 'fa-user' ?> mr-0.5"></i>
                <?= htmlspecialchars($roleLabel) ?>
            </div>
        </div>
        <i class="fa-solid fa-chevron-right text-white/40 text-xs group-hover:text-white/80 transition"></i>
    </a>

    <nav class="space-y-1">
        <?php foreach ($nav as [$k, $href, $icon, $label]): ?>
            <a href="<?= $href ?>" class="nav-link <?= $active === $k ? 'active' : '' ?>">
                <i class="fa-solid <?= $icon ?> w-5 text-center opacity-80"></i>
                <span><?= $label ?></span>
            </a>
        <?php endforeach; ?>

        <a href="/admin/logout.php" class="nav-link text-rose-200 hover:!bg-rose-500/10 mt-3">
            <i class="fa-solid fa-right-from-bracket w-5 text-center opacity-80"></i>
            <span>Sign out</span>
        </a>
    </nav>

    <div class="mt-8 p-4 rounded-xl border border-white/5 bg-white/[.03]">
        <div class="text-xs text-white/60 mb-2"><i class="fa-solid fa-shield-halved mr-1"></i> Security tip</div>
        <div class="text-xs text-white/70 leading-relaxed">
            Change the default admin password from the
            <a href="/admin/account.php" class="underline">Account</a> page on first login.
        </div>
    </div>
</aside>
