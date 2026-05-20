<?php
declare(strict_types=1);

require __DIR__ . "/../bootstrap.php";
require __DIR__ . "/partials/layout.php";

use App\Auth;
use App\Csrf;
use App\Database;
use App\Upload;

Auth::require();

$config       = $GLOBALS["APP_CONFIG"];
$avatarDir    = $config["paths"]["uploads"] . "/admins";
$avatarUrlDir = "/uploads/admins";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    Csrf::require();
    $action = $_POST["action"] ?? "";
    try {
        $user = Auth::user();
        if (!$user) throw new RuntimeException("Not signed in.");

        if ($action === "profile") {
            $fullName = trim((string)($_POST["full_name"] ?? ""));
            $username = trim((string)($_POST["username"]  ?? ""));
            $email    = trim((string)($_POST["email"]     ?? ""));

            if ($fullName === "" || $username === "" || $email === "") {
                throw new RuntimeException("Full name, username and email are all required.");
            }
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                throw new RuntimeException("Please enter a valid email address.");
            }

            // Uniqueness check (excluding self).
            $dup = Database::pdo()->prepare(
                "SELECT id FROM admin_users
                 WHERE (username = :u OR email = :e) AND id <> :id LIMIT 1"
            );
            $dup->execute([":u" => $username, ":e" => $email, ":id" => $user["id"]]);
            if ($dup->fetchColumn()) {
                throw new RuntimeException("That username or email is already in use.");
            }

            $newAvatar = $user["profile_pic"] ?? null;

            // Optional avatar upload.
            if (!empty($_FILES["profile_pic"]["name"])) {
                $newAvatar = Upload::image($_FILES["profile_pic"], $avatarDir);
                // Best-effort cleanup of the previous avatar.
                if (!empty($user["profile_pic"])) {
                    Upload::delete($avatarDir, (string)$user["profile_pic"]);
                }
            } elseif (!empty($_POST["remove_avatar"])) {
                if (!empty($user["profile_pic"])) {
                    Upload::delete($avatarDir, (string)$user["profile_pic"]);
                }
                $newAvatar = null;
            }

            Database::pdo()->prepare(
                "UPDATE admin_users
                    SET full_name = :fn, username = :u, email = :e, profile_pic = :pic
                  WHERE id = :id"
            )->execute([
                ":fn"  => $fullName,
                ":u"   => $username,
                ":e"   => $email,
                ":pic" => $newAvatar,
                ":id"  => $user["id"],
            ]);

            Auth::refreshSession();
            flash_set("success", "Profile updated successfully.");
        } elseif ($action === "password") {
            $cur = (string)($_POST["current"] ?? "");
            $new = (string)($_POST["new"]     ?? "");
            $cnf = (string)($_POST["confirm"] ?? "");

            if ($cur === "" || $new === "" || $cnf === "") {
                throw new RuntimeException("All password fields are required.");
            }
            if (mb_strlen($new) < 8) {
                throw new RuntimeException("New password must be at least 8 characters.");
            }
            if ($new !== $cnf) {
                throw new RuntimeException("New password and confirmation do not match.");
            }

            $row = Database::pdo()->prepare("SELECT password_hash FROM admin_users WHERE id = :id");
            $row->execute([":id" => $user["id"]]);
            $hash = (string)$row->fetchColumn();

            if (!password_verify($cur, $hash)) {
                throw new RuntimeException("Your current password is incorrect.");
            }
            if (password_verify($new, $hash)) {
                throw new RuntimeException("New password must be different from your current password.");
            }

            Auth::changePassword((int)$user["id"], $new);
            flash_set("success", "Password changed successfully.");
        }
    } catch (Throwable $e) {
        flash_set("error", $e->getMessage());
    }
    header("Location: /admin/account.php");
    exit;
}

$user = Auth::user();
$initial = strtoupper(mb_substr((string)($user["full_name"] ?: $user["username"]), 0, 1));
$avatarUrl = !empty($user["profile_pic"])
    ? $avatarUrlDir . "/" . rawurlencode((string)$user["profile_pic"])
    : null;

admin_layout_start("Account", "account");
?>
<?= flash_render() ?>

<div class="grid lg:grid-cols-2 gap-5">

    <!-- Profile -->
    <form method="POST" enctype="multipart/form-data" class="glass rounded-2xl p-6 space-y-4">
        <input type="hidden" name="_csrf" value="<?= htmlspecialchars(Csrf::token(), ENT_QUOTES) ?>">
        <input type="hidden" name="action" value="profile">

        <div class="flex items-center justify-between">
            <h2 class="text-lg font-semibold">Profile</h2>
            <span class="badge <?= ($user['role'] ?? 'sub') === 'main' ? 'badge-info' : 'badge-warn' ?>">
                <i class="fa-solid fa-user-shield"></i>
                <?= ($user['role'] ?? 'sub') === 'main' ? 'Main administrator' : 'Sub-admin' ?>
            </span>
        </div>

        <div class="flex items-center gap-4">
            <div class="relative">
                <?php if ($avatarUrl): ?>
                    <img src="<?= htmlspecialchars($avatarUrl) ?>"
                         alt="Profile picture"
                         class="w-20 h-20 rounded-2xl object-cover border border-white/10 shadow-lg">
                <?php else: ?>
                    <div class="w-20 h-20 rounded-2xl bg-gradient-to-br from-indigo-500 via-purple-500 to-pink-500 flex items-center justify-center text-2xl font-bold text-white shadow-lg">
                        <?= htmlspecialchars($initial) ?>
                    </div>
                <?php endif; ?>
            </div>
            <div class="flex-1">
                <label class="label">Profile picture</label>
                <input class="input" type="file" name="profile_pic" accept="image/*">
                <div class="text-xs text-white/50 mt-1">JPG, PNG, WEBP — up to 8 MB.</div>
                <?php if ($avatarUrl): ?>
                    <label class="text-xs text-white/60 mt-2 inline-flex items-center gap-1.5 cursor-pointer">
                        <input type="checkbox" name="remove_avatar" value="1" class="accent-pink-500">
                        Remove current picture
                    </label>
                <?php endif; ?>
            </div>
        </div>

        <div>
            <label class="label">Full name</label>
            <input class="input" name="full_name"
                   value="<?= htmlspecialchars((string)($user["full_name"] ?? "")) ?>" required>
        </div>

        <div class="grid sm:grid-cols-2 gap-3">
            <div>
                <label class="label">Username</label>
                <input class="input" name="username"
                       value="<?= htmlspecialchars((string)($user["username"] ?? "")) ?>" required>
            </div>
            <div>
                <label class="label">Email</label>
                <input class="input" type="email" name="email"
                       value="<?= htmlspecialchars((string)($user["email"] ?? "")) ?>" required>
            </div>
        </div>

        <button class="btn btn-primary"><i class="fa-solid fa-save"></i> Save profile</button>
    </form>

    <!-- Password -->
    <form method="POST" class="glass rounded-2xl p-6 space-y-3">
        <input type="hidden" name="_csrf" value="<?= htmlspecialchars(Csrf::token(), ENT_QUOTES) ?>">
        <input type="hidden" name="action" value="password">

        <h2 class="text-lg font-semibold mb-2">
            <i class="fa-solid fa-key text-pink-400 mr-1"></i> Change password
        </h2>
        <p class="text-xs text-white/50 -mt-1 mb-2">
            For your security, we re-verify your current password before applying any change.
        </p>

        <div>
            <label class="label">Current password</label>
            <input class="input" type="password" name="current" required autocomplete="current-password">
        </div>
        <div>
            <label class="label">New password</label>
            <input class="input" type="password" name="new" required minlength="8" autocomplete="new-password">
            <div class="text-xs text-white/50 mt-1">Minimum 8 characters.</div>
        </div>
        <div>
            <label class="label">Confirm new password</label>
            <input class="input" type="password" name="confirm" required minlength="8" autocomplete="new-password">
        </div>

        <button class="btn btn-primary"><i class="fa-solid fa-shield-halved"></i> Change password</button>
    </form>
</div>
<?php admin_layout_end(); ?>
