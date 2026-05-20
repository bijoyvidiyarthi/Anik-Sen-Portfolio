<?php
/**
 * Admin layout: open with admin_layout_start(), close with admin_layout_end().
 * Glassmorphic Tailwind UI (Tailwind via CDN; ok for admin internal tooling).
 */

declare(strict_types=1);

use App\Auth;
use App\Settings;
use App\Message;

function admin_layout_start(string $title, string $active = "dashboard"): void
{
    // Session-check header — every admin page passes through this guard.
    Auth::require();
    $user      = Auth::user();
    $siteName  = Settings::get("site_name", "Portfolio");
    $unread    = Message::unreadCount();

    // Header / navbar avatar info
    $userName    = (string)($user["full_name"] ?? $user["username"] ?? "Admin");
    $userInitial = strtoupper(mb_substr($userName, 0, 1));
    $userAvatar  = !empty($user["profile_pic"])
        ? "/uploads/admins/" . rawurlencode((string)$user["profile_pic"])
        : null;
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title><?= htmlspecialchars($title) ?> — Admin</title>
        <script src="https://cdn.tailwindcss.com"></script>
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
        <style>
          :root { --grad-1:#6366f1; --grad-2:#a855f7; --grad-3:#ec4899; }
          html, body { font-family: 'Inter', system-ui, sans-serif; }
          body {
            background:
              radial-gradient(1100px 700px at 5% -10%, rgba(99,102,241,.30), transparent 60%),
              radial-gradient(900px 600px at 100% 20%, rgba(236,72,153,.25), transparent 60%),
              radial-gradient(900px 700px at 50% 110%, rgba(34,211,238,.20), transparent 60%),
              #07080f;
            color: #e6e8f1;
            min-height: 100vh;
          }
          .glass {
            background: rgba(20, 22, 38, .55);
            backdrop-filter: blur(18px) saturate(140%);
            -webkit-backdrop-filter: blur(18px) saturate(140%);
            border: 1px solid rgba(255,255,255,.08);
            box-shadow: 0 20px 60px -10px rgba(2,4,18,.6), inset 0 1px 0 rgba(255,255,255,.05);
          }
          .glass-strong {
            background: rgba(14, 16, 30, .75);
            backdrop-filter: blur(24px) saturate(160%);
            border: 1px solid rgba(255,255,255,.10);
          }
          .grad-text {
            background: linear-gradient(90deg, var(--grad-1), var(--grad-2), var(--grad-3));
            -webkit-background-clip: text; background-clip: text;
            color: transparent;
          }
          .btn {
            display: inline-flex; align-items: center; gap: .5rem;
            padding: .55rem 1rem; border-radius: .65rem; font-weight: 600;
            transition: transform .15s ease, box-shadow .2s ease, background .2s ease;
          }
          .btn-primary {
            background: linear-gradient(135deg, var(--grad-1), var(--grad-3));
            color: white;
            box-shadow: 0 12px 30px -8px rgba(168,85,247,.55);
          }
          .btn-primary:hover { transform: translateY(-1px); box-shadow: 0 16px 40px -8px rgba(168,85,247,.65); }
          .btn-ghost { background: rgba(255,255,255,.06); color: #e6e8f1; border: 1px solid rgba(255,255,255,.08); }
          .btn-ghost:hover { background: rgba(255,255,255,.10); }
          .btn-danger { background: rgba(244,63,94,.15); color: #ff8aa1; border: 1px solid rgba(244,63,94,.35); }
          .btn-danger:hover { background: rgba(244,63,94,.25); }
          .input, .select, .textarea {
            width: 100%; padding: .65rem .8rem; border-radius: .6rem;
            background: rgba(8, 10, 24, .55); border: 1px solid rgba(255,255,255,.08);
            color: #e6e8f1; outline: none; transition: border-color .15s ease, box-shadow .15s ease;
          }
          .input:focus, .select:focus, .textarea:focus {
            border-color: rgba(168,85,247,.55);
            box-shadow: 0 0 0 3px rgba(168,85,247,.18);
          }
          .label { font-size: .8rem; font-weight: 600; color: #b9bdd1; margin-bottom: .35rem; display:block; }
          .nav-link {
            display:flex; align-items:center; gap:.7rem; padding:.6rem .85rem;
            border-radius: .6rem; color:#c5c9dd; font-weight:500; transition:all .15s;
            position: relative;
          }
          .nav-link:hover { background: rgba(255,255,255,.05); color:#fff; }
          .nav-link.active {
            background: linear-gradient(135deg, rgba(99,102,241,.25), rgba(236,72,153,.20));
            color:#fff;
            box-shadow: inset 0 0 0 1px rgba(255,255,255,.10);
          }
          .nav-link.active::before {
            content:""; position:absolute; left:-12px; top:8px; bottom:8px; width:3px;
            background: linear-gradient(180deg, var(--grad-1), var(--grad-3));
            border-radius: 99px;
          }
          .badge {
            display:inline-flex; align-items:center; gap:.3rem;
            padding:.15rem .55rem; border-radius:99px; font-size:.7rem; font-weight:600;
          }
          .badge-info    { background: rgba(99,102,241,.15); color:#a5b4fc; border:1px solid rgba(99,102,241,.3); }
          .badge-success { background: rgba(16,185,129,.15); color:#6ee7b7; border:1px solid rgba(16,185,129,.3); }
          .badge-warn    { background: rgba(245,158,11,.15); color:#fcd34d; border:1px solid rgba(245,158,11,.3); }
          .badge-danger  { background: rgba(244,63,94,.15);  color:#fda4af; border:1px solid rgba(244,63,94,.3); }
          table.data { width:100%; border-collapse: collapse; }
          table.data th { text-align:left; font-size:.72rem; text-transform:uppercase; letter-spacing:.05em; color:#9aa0bd; padding:.65rem .8rem; border-bottom:1px solid rgba(255,255,255,.07); }
          table.data td { padding:.85rem .8rem; border-bottom: 1px solid rgba(255,255,255,.05); vertical-align: middle; font-size:.9rem; }
          table.data tr:hover td { background: rgba(255,255,255,.025); }
          .alert { padding:.75rem 1rem; border-radius:.6rem; margin-bottom:1rem; font-size:.9rem; }
          .alert-success { background: rgba(16,185,129,.12); border:1px solid rgba(16,185,129,.3); color:#a7f3d0; }
          .alert-error   { background: rgba(244,63,94,.12);  border:1px solid rgba(244,63,94,.3);  color:#fecdd3; }
          .alert-info    { background: rgba(99,102,241,.12); border:1px solid rgba(99,102,241,.3); color:#c7d2fe; }
          /* Toast notifications (theme-consistent glassmorphic) */
          #toast-stack {
            position: fixed; right: 1.25rem; bottom: 1.25rem; z-index: 80;
            display: flex; flex-direction: column; gap: .55rem; pointer-events: none;
          }
          .toast {
            pointer-events: auto;
            min-width: 280px; max-width: 380px;
            display: flex; align-items: flex-start; gap: .65rem;
            padding: .75rem .9rem; border-radius: .75rem;
            background: rgba(20, 22, 38, .82);
            backdrop-filter: blur(22px) saturate(160%);
            border: 1px solid rgba(255,255,255,.10);
            box-shadow: 0 22px 60px -12px rgba(2,4,18,.7);
            color: #e6e8f1; font-size: .88rem; line-height: 1.35;
            transform: translateX(120%); opacity: 0;
            transition: transform .35s cubic-bezier(.2,.9,.25,1), opacity .25s;
          }
          .toast.show { transform: translateX(0); opacity: 1; }
          .toast .toast-icon {
            width: 1.9rem; height: 1.9rem; border-radius: .5rem; flex: 0 0 auto;
            display:flex; align-items:center; justify-content:center; font-size: .9rem;
          }
          .toast.success .toast-icon { background: rgba(16,185,129,.18); color:#6ee7b7; border:1px solid rgba(16,185,129,.35); }
          .toast.error   .toast-icon { background: rgba(244,63,94,.18);  color:#fda4af; border:1px solid rgba(244,63,94,.35); }
          .toast.info    .toast-icon { background: rgba(99,102,241,.18); color:#a5b4fc; border:1px solid rgba(99,102,241,.35); }
          .toast .toast-close {
            background: transparent; border: 0; color: rgba(255,255,255,.5);
            cursor: pointer; padding: 0 .15rem; font-size: .85rem; margin-left: auto;
          }
          .toast .toast-close:hover { color: #fff; }
          @media (max-width: 1023px) {
            #sidebar {
              transform: translateX(-110%);
              transition: transform .25s ease;
              position: fixed;
              top: 0; left: 0;
              height: 100vh;
              max-height: 100vh;
              overflow-y: auto;
              z-index: 50;
              -webkit-overflow-scrolling: touch;
              overscroll-behavior: contain;
            }
            #sidebar.open { transform: translateX(0); }
            #scrim { display:none; position:fixed; inset:0; background:rgba(0,0,0,.5); z-index:40; }
            #sidebar.open + #scrim { display:block; }
          }
          /* Glassmorphic scrollbar — applied to the sidebar (and any element
             that opts in via .sidebar-scroll). Uses a translucent gradient
             thumb so it blends with the glass-strong sidebar background. */
          .sidebar-scroll {
            scrollbar-width: thin;
            scrollbar-color: rgba(168,85,247,.45) transparent;
          }
          .sidebar-scroll::-webkit-scrollbar { width: 8px; height: 8px; }
          .sidebar-scroll::-webkit-scrollbar-track {
            background: rgba(255,255,255,.02);
            border-radius: 99px;
          }
          .sidebar-scroll::-webkit-scrollbar-thumb {
            background: linear-gradient(180deg, rgba(99,102,241,.55), rgba(236,72,153,.55));
            border-radius: 99px;
            border: 2px solid rgba(14,16,30,.45);
            background-clip: padding-box;
          }
          .sidebar-scroll::-webkit-scrollbar-thumb:hover {
            background: linear-gradient(180deg, rgba(99,102,241,.85), rgba(236,72,153,.85));
            background-clip: padding-box;
          }
        </style>
    </head>
    <body class="min-h-screen">
      <div class="flex">
        <?php include __DIR__ . "/sidebar.php"; ?>
        <div id="scrim" onclick="document.getElementById('sidebar').classList.remove('open')"></div>

        <main class="flex-1 min-w-0">
          <header class="glass-strong sticky top-0 z-30 px-5 lg:px-8 py-3 flex items-center justify-between border-b border-white/5">
            <div class="flex items-center gap-3">
              <button onclick="document.getElementById('sidebar').classList.toggle('open')"
                      class="lg:hidden btn btn-ghost p-2" aria-label="Toggle menu">
                <i class="fa-solid fa-bars"></i>
              </button>
              <div>
                <div class="text-xs text-white/50 leading-none mb-1">Admin</div>
                <h1 class="text-lg font-semibold text-white"><?= htmlspecialchars($title) ?></h1>
              </div>
            </div>
            <div class="flex items-center gap-3">
              <a href="/" target="_blank" class="btn btn-ghost text-sm">
                <i class="fa-solid fa-up-right-from-square"></i><span class="hidden sm:inline">View site</span>
              </a>
              <a href="/admin/messages.php" class="btn btn-ghost text-sm relative" title="Inbox">
                <i class="fa-regular fa-envelope"></i>
                <?php if ($unread > 0): ?>
                  <span class="absolute -top-1 -right-1 bg-pink-500 text-[10px] rounded-full px-1.5 py-0.5"><?= $unread ?></span>
                <?php endif; ?>
              </a>
              <a href="/admin/account.php"
                 class="hidden sm:flex items-center gap-2 px-2.5 py-1.5 rounded-lg bg-white/5 hover:bg-white/10 transition"
                 title="My account">
                <?php if ($userAvatar): ?>
                  <img src="<?= htmlspecialchars($userAvatar) ?>"
                       alt="" class="w-7 h-7 rounded-full object-cover border border-white/10">
                <?php else: ?>
                  <div class="w-7 h-7 rounded-full bg-gradient-to-br from-indigo-500 to-pink-500 flex items-center justify-center text-xs font-bold">
                    <?= htmlspecialchars($userInitial) ?>
                  </div>
                <?php endif; ?>
                <span class="text-sm text-white/80 max-w-[140px] truncate"><?= htmlspecialchars($userName) ?></span>
              </a>
              <a href="/admin/logout.php" class="btn btn-ghost text-sm" title="Sign out">
                <i class="fa-solid fa-right-from-bracket"></i>
              </a>
            </div>
          </header>

          <section class="px-5 lg:px-8 py-6">
    <?php
}

function admin_layout_end(): void
{
    // Pull any queued flash AND any pre-queued toasts into the page.
    $toasts = $_SESSION["_toasts"] ?? [];
    if (!empty($_SESSION["_flash"])) {
        $toasts[] = $_SESSION["_flash"];
        unset($_SESSION["_flash"]);
    }
    unset($_SESSION["_toasts"]);
    ?>
          </section>
        </main>
      </div>

      <!-- Toast stack -->
      <div id="toast-stack" aria-live="polite" aria-atomic="true"></div>

      <script>
        // Tiny toast renderer. Theme-consistent with the glassmorphic UI.
        (function () {
          const ICONS = {
            success: 'fa-circle-check',
            error:   'fa-circle-exclamation',
            info:    'fa-circle-info',
          };
          window.showToast = function (type, msg, timeout = 4000) {
            const stack = document.getElementById('toast-stack');
            if (!stack) return;
            type = ICONS[type] ? type : 'info';
            const el = document.createElement('div');
            el.className = 'toast ' + type;
            el.innerHTML = `
              <div class="toast-icon"><i class="fa-solid ${ICONS[type]}"></i></div>
              <div class="toast-body flex-1">${String(msg).replace(/[<>&]/g, c => ({'<':'&lt;','>':'&gt;','&':'&amp;'}[c]))}</div>
              <button class="toast-close" aria-label="Dismiss">
                <i class="fa-solid fa-xmark"></i>
              </button>`;
            el.querySelector('.toast-close').addEventListener('click', () => dismiss());
            stack.appendChild(el);
            requestAnimationFrame(() => el.classList.add('show'));
            const t = setTimeout(dismiss, timeout);
            function dismiss() {
              clearTimeout(t);
              el.classList.remove('show');
              setTimeout(() => el.remove(), 350);
            }
          };

          // Hydrate any server-queued toasts.
          const queued = <?= json_encode(array_values(array_map(
              fn($t) => ["type" => $t["type"] ?? "info", "msg" => $t["msg"] ?? ""],
              $toasts
          )), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>;
          queued.forEach((q, i) => setTimeout(() => showToast(q.type, q.msg), 80 + i * 120));
        })();

        // Confirm-on-click links (preserve original behaviour).
        document.querySelectorAll('[data-confirm]').forEach(el => {
          el.addEventListener('click', e => {
            if (!confirm(el.dataset.confirm)) e.preventDefault();
          });
        });
      </script>
    </body>
    </html>
    <?php
}

/**
 * Queue a flash message. Rendered as a toast notification on the next
 * page load (and, if the page calls flash_render(), also inlined for
 * progressive enhancement / no-JS fallback).
 */
function flash_set(string $type, string $msg): void
{
    $_SESSION["_flash"] = ["type" => $type, "msg" => $msg];
}

/**
 * Queue an additional toast notification (independent of the single-shot
 * "_flash" slot). Useful when one action emits multiple notifications.
 */
function toast_push(string $type, string $msg): void
{
    if (!isset($_SESSION["_toasts"]) || !is_array($_SESSION["_toasts"])) {
        $_SESSION["_toasts"] = [];
    }
    $_SESSION["_toasts"][] = ["type" => $type, "msg" => $msg];
}

/**
 * Optional inline alert renderer kept for backward compatibility. Pages
 * that already call flash_render() continue to work; the same message is
 * also shown as a toast (the alert provides a no-JS fallback).
 */
function flash_render(): string
{
    if (empty($_SESSION["_flash"])) return "";
    $f = $_SESSION["_flash"]; // do NOT consume — admin_layout_end() reads it for the toast.
    $cls = match ($f["type"]) {
        "success" => "alert alert-success",
        "error"   => "alert alert-error",
        default   => "alert alert-info",
    };
    $icon = match ($f["type"]) {
        "success" => "fa-circle-check",
        "error"   => "fa-circle-exclamation",
        default   => "fa-circle-info",
    };
    return '<noscript><div class="' . $cls . '"><i class="fa-solid ' . $icon . ' mr-2"></i>'
        . htmlspecialchars($f["msg"]) . '</div></noscript>';
}
