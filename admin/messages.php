<?php
declare(strict_types=1);

require __DIR__ . "/../bootstrap.php";
require __DIR__ . "/partials/layout.php";

use App\Message;
use App\Csrf;

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    Csrf::require();
    $action = $_POST["action"] ?? "";
    try {
        if ($action === "mark_read")    Message::markRead((int)$_POST["id"], true);
        elseif ($action === "mark_unread") Message::markRead((int)$_POST["id"], false);
        elseif ($action === "delete")   Message::delete((int)$_POST["id"]);
        flash_set("success", "Done.");
    } catch (Throwable $e) { flash_set("error", $e->getMessage()); }
    $back = "/admin/messages.php";
    if (!empty($_POST["back"])) $back = (string)$_POST["back"];
    header("Location: $back"); exit;
}

$filter = $_GET["filter"] ?? "all";
$search = (string)($_GET["q"] ?? "");
$rows = Message::all($search, $filter);
$openId = isset($_GET["id"]) ? (int)$_GET["id"] : 0;
$open = $openId ? Message::find($openId) : null;
if ($open && !$open["is_read"]) {
    Message::markRead($openId, true);
    $open["is_read"] = 1;
}

admin_layout_start("Messages", "messages");
?>
<?= flash_render() ?>

<div class="grid lg:grid-cols-5 gap-5">
    <div class="lg:col-span-2 glass rounded-2xl p-5">
        <form class="flex gap-2 mb-3" method="GET">
            <input class="input flex-1" name="q" placeholder="Search messages..." value="<?= htmlspecialchars($search) ?>">
            <select class="select w-32" name="filter">
                <option value="all"    <?= $filter === 'all' ? 'selected' : '' ?>>All</option>
                <option value="unread" <?= $filter === 'unread' ? 'selected' : '' ?>>Unread</option>
                <option value="read"   <?= $filter === 'read' ? 'selected' : '' ?>>Read</option>
            </select>
            <button class="btn btn-ghost"><i class="fa-solid fa-magnifying-glass"></i></button>
        </form>

        <ul class="divide-y divide-white/5 max-h-[70vh] overflow-y-auto">
            <?php foreach ($rows as $m): ?>
                <li>
                    <a href="?id=<?= (int)$m["id"] ?>&filter=<?= urlencode($filter) ?>&q=<?= urlencode($search) ?>"
                       class="flex gap-3 p-3 rounded-lg hover:bg-white/5 transition <?= $openId === (int)$m["id"] ? 'bg-white/[.07]' : '' ?>">
                        <div class="w-9 h-9 rounded-full bg-gradient-to-br from-indigo-500 to-pink-500 flex items-center justify-center text-xs font-bold flex-shrink-0">
                            <?= strtoupper(htmlspecialchars(substr($m["name"], 0, 1))) ?>
                        </div>
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center gap-2">
                                <span class="font-semibold text-sm <?= $m["is_read"] ? 'text-white/70' : 'text-white' ?> truncate"><?= htmlspecialchars($m["name"]) ?></span>
                                <?php if (!$m["is_read"]): ?><span class="badge badge-info">new</span><?php endif; ?>
                                <span class="ml-auto text-[10px] text-white/40 whitespace-nowrap"><?= htmlspecialchars(date("M j", strtotime((string)$m["created_at"]))) ?></span>
                            </div>
                            <div class="text-xs <?= $m["is_read"] ? 'text-white/50' : 'text-white/80' ?> truncate"><?= htmlspecialchars($m["subject"]) ?></div>
                            <div class="text-[11px] text-white/40 truncate mt-0.5"><?= htmlspecialchars(mb_substr($m["message"], 0, 80)) ?>…</div>
                        </div>
                    </a>
                </li>
            <?php endforeach; ?>
            <?php if (!$rows): ?><li class="py-8 text-center text-white/40 text-sm">No messages match.</li><?php endif; ?>
        </ul>
    </div>

    <div class="lg:col-span-3 glass rounded-2xl p-6">
        <?php if (!$open): ?>
            <div class="text-center py-16 text-white/40">
                <i class="fa-regular fa-envelope-open text-5xl mb-3"></i>
                <div>Select a message from the list.</div>
            </div>
        <?php else: ?>
            <div class="flex items-start justify-between mb-4">
                <div>
                    <div class="text-xs text-white/50">From</div>
                    <div class="font-semibold text-white text-lg"><?= htmlspecialchars($open["name"]) ?></div>
                    <a href="mailto:<?= htmlspecialchars($open["email"]) ?>" class="text-sm text-indigo-300 hover:text-indigo-200"><?= htmlspecialchars($open["email"]) ?></a>
                </div>
                <div class="text-xs text-white/50 text-right">
                    <?= htmlspecialchars(date("M j, Y H:i", strtotime((string)$open["created_at"]))) ?>
                </div>
            </div>

            <div class="text-lg font-semibold mb-2"><?= htmlspecialchars($open["subject"]) ?></div>
            <div class="rounded-xl bg-black/30 border border-white/5 p-4 text-white/90 whitespace-pre-wrap leading-relaxed"><?= htmlspecialchars($open["message"]) ?></div>

            <div class="mt-5 flex gap-2 flex-wrap">
                <a href="mailto:<?= htmlspecialchars($open["email"]) ?>?subject=Re: <?= rawurlencode($open["subject"]) ?>" class="btn btn-primary"><i class="fa-solid fa-reply"></i> Reply</a>
                <form method="POST" class="inline">
                    <input type="hidden" name="_csrf" value="<?= htmlspecialchars(Csrf::token(), ENT_QUOTES) ?>">
                    <input type="hidden" name="action" value="mark_unread">
                    <input type="hidden" name="id" value="<?= (int)$open["id"] ?>">
                    <input type="hidden" name="back" value="/admin/messages.php?id=<?= (int)$open["id"] ?>">
                    <button class="btn btn-ghost"><i class="fa-regular fa-envelope"></i> Mark unread</button>
                </form>
                <form method="POST" class="inline" onsubmit="return confirm('Delete this message?')">
                    <input type="hidden" name="_csrf" value="<?= htmlspecialchars(Csrf::token(), ENT_QUOTES) ?>">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="id" value="<?= (int)$open["id"] ?>">
                    <button class="btn btn-danger"><i class="fa-solid fa-trash"></i> Delete</button>
                </form>
            </div>
        <?php endif; ?>
    </div>
</div>
<?php admin_layout_end(); ?>
