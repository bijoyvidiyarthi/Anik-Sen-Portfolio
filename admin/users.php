<?php
declare(strict_types=1);

require __DIR__ . "/../bootstrap.php";
require __DIR__ . "/partials/layout.php";

use App\Auth;
use App\Csrf;
use App\Database;
use App\Upload;

// Only the main administrator may access this page.
Auth::requireMain();

$config       = $GLOBALS["APP_CONFIG"];
$avatarDir    = $config["paths"]["uploads"] . "/admins";
$avatarUrlDir = "/uploads/admins";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    Csrf::require();
    $action = $_POST["action"] ?? "";
    $self   = (int)Auth::id();

    try {
        if ($action === "create") {
            $fullName = trim((string)($_POST["full_name"] ?? ""));
            $username = trim((string)($_POST["username"]  ?? ""));
            $email    = trim((string)($_POST["email"]     ?? ""));
            $password = (string)($_POST["password"]       ?? "");
            $role     = ($_POST["role"] ?? "sub") === "main" ? "main" : "sub";
            $isActive = !empty($_POST["is_active"]) ? 1 : 0;

            if ($fullName === "" || $username === "" || $email === "" || $password === "") {
                throw new RuntimeException("Full name, username, email and password are required.");
            }
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                throw new RuntimeException("Please enter a valid email address.");
            }
            if (mb_strlen($password) < 8) {
                throw new RuntimeException("Password must be at least 8 characters.");
            }

            $dup = Database::pdo()->prepare(
                "SELECT id FROM admin_users WHERE username = :u OR email = :e LIMIT 1"
            );
            $dup->execute([":u" => $username, ":e" => $email]);
            if ($dup->fetchColumn()) {
                throw new RuntimeException("That username or email is already in use.");
            }

            $picture = null;
            if (!empty($_FILES["profile_pic"]["name"])) {
                $picture = Upload::image($_FILES["profile_pic"], $avatarDir);
            }

            Database::pdo()->prepare(
                "INSERT INTO admin_users
                    (full_name, username, email, password_hash, profile_pic, role, is_active)
                 VALUES (:fn, :u, :e, :p, :pic, :r, :a)"
            )->execute([
                ":fn"  => $fullName,
                ":u"   => $username,
                ":e"   => $email,
                ":p"   => password_hash($password, PASSWORD_BCRYPT),
                ":pic" => $picture,
                ":r"   => $role,
                ":a"   => $isActive,
            ]);

            flash_set("success", "User “{$fullName}” added successfully.");
        } elseif ($action === "update") {
            $id = (int)($_POST["id"] ?? 0);
            if ($id <= 0) throw new RuntimeException("Invalid user.");

            $fullName = trim((string)($_POST["full_name"] ?? ""));
            $username = trim((string)($_POST["username"]  ?? ""));
            $email    = trim((string)($_POST["email"]     ?? ""));
            $password = (string)($_POST["password"]       ?? "");
            $role     = ($_POST["role"] ?? "sub") === "main" ? "main" : "sub";
            $isActive = !empty($_POST["is_active"]) ? 1 : 0;

            if ($fullName === "" || $username === "" || $email === "") {
                throw new RuntimeException("Full name, username and email are required.");
            }
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                throw new RuntimeException("Please enter a valid email address.");
            }

            // Prevent the main admin from locking themselves out.
            if ($id === $self) {
                $role     = "main";
                $isActive = 1;
            }

            $dup = Database::pdo()->prepare(
                "SELECT id FROM admin_users
                 WHERE (username = :u OR email = :e) AND id <> :id LIMIT 1"
            );
            $dup->execute([":u" => $username, ":e" => $email, ":id" => $id]);
            if ($dup->fetchColumn()) {
                throw new RuntimeException("That username or email is already in use.");
            }

            // Look up current avatar so we can replace/remove it.
            $cur = Database::pdo()->prepare("SELECT profile_pic FROM admin_users WHERE id = :id");
            $cur->execute([":id" => $id]);
            $currentPic = $cur->fetchColumn();
            $newPic = $currentPic ?: null;

            if (!empty($_FILES["profile_pic"]["name"])) {
                $newPic = Upload::image($_FILES["profile_pic"], $avatarDir);
                if ($currentPic) Upload::delete($avatarDir, (string)$currentPic);
            } elseif (!empty($_POST["remove_avatar"])) {
                if ($currentPic) Upload::delete($avatarDir, (string)$currentPic);
                $newPic = null;
            }

            Database::pdo()->prepare(
                "UPDATE admin_users
                    SET full_name = :fn, username = :u, email = :e,
                        profile_pic = :pic, role = :r, is_active = :a
                  WHERE id = :id"
            )->execute([
                ":fn"  => $fullName,
                ":u"   => $username,
                ":e"   => $email,
                ":pic" => $newPic,
                ":r"   => $role,
                ":a"   => $isActive,
                ":id"  => $id,
            ]);

            // Optional password reset by main admin.
            if ($password !== "") {
                if (mb_strlen($password) < 8) {
                    throw new RuntimeException("New password must be at least 8 characters.");
                }
                Auth::changePassword($id, $password);
            }

            // If editing self, refresh the active session payload.
            if ($id === $self) Auth::refreshSession();

            flash_set("success", "User updated.");
        } elseif ($action === "toggle") {
            $id = (int)($_POST["id"] ?? 0);
            if ($id <= 0) throw new RuntimeException("Invalid user.");
            if ($id === $self) {
                throw new RuntimeException("You cannot deactivate your own account.");
            }
            Database::pdo()->prepare(
                "UPDATE admin_users SET is_active = 1 - is_active WHERE id = :id"
            )->execute([":id" => $id]);
            flash_set("success", "User status updated.");
        } elseif ($action === "delete") {
            $id = (int)($_POST["id"] ?? 0);
            if ($id <= 0) throw new RuntimeException("Invalid user.");
            if ($id === $self) {
                throw new RuntimeException("You cannot delete your own account.");
            }
            // Don't allow deleting the very last main admin.
            if (isLastMainAdmin($id)) {
                throw new RuntimeException("You cannot delete the last main administrator.");
            }

            $cur = Database::pdo()->prepare("SELECT profile_pic FROM admin_users WHERE id = :id");
            $cur->execute([":id" => $id]);
            $pic = $cur->fetchColumn();

            Database::pdo()->prepare("DELETE FROM admin_users WHERE id = :id")
                ->execute([":id" => $id]);

            if ($pic) Upload::delete($avatarDir, (string)$pic);
            flash_set("success", "User deleted.");
        }
    } catch (Throwable $e) {
        flash_set("error", $e->getMessage());
    }
    header("Location: /admin/users.php");
    exit;
}

/** Helper: is this id the last user with role='main'? */
function isLastMainAdmin(int $id): bool
{
    $cur = Database::pdo()->prepare("SELECT role FROM admin_users WHERE id = :id");
    $cur->execute([":id" => $id]);
    if ($cur->fetchColumn() !== "main") return false;
    $count = (int)Database::pdo()->query(
        "SELECT COUNT(*) FROM admin_users WHERE role = 'main'"
    )->fetchColumn();
    return $count <= 1;
}

$users = Database::pdo()->query(
    "SELECT id, full_name, username, email, profile_pic, role, is_active, last_login_at, created_at
     FROM admin_users ORDER BY (role = 'main') DESC, full_name ASC"
)->fetchAll(\PDO::FETCH_ASSOC);

$selfId = (int)Auth::id();

admin_layout_start("Manage Users", "users");
?>
<?= flash_render() ?>

<div class="grid lg:grid-cols-3 gap-5">

    <!-- Add user -->
    <form method="POST" enctype="multipart/form-data"
          class="glass rounded-2xl p-6 space-y-3 lg:col-span-1">
        <input type="hidden" name="_csrf" value="<?= htmlspecialchars(Csrf::token(), ENT_QUOTES) ?>">
        <input type="hidden" name="action" value="create">

        <h2 class="text-lg font-semibold flex items-center gap-2">
            <i class="fa-solid fa-user-plus text-pink-400"></i> Add new user
        </h2>

        <div><label class="label">Full name</label>
            <input class="input" name="full_name" required></div>

        <div class="grid grid-cols-2 gap-3">
            <div><label class="label">Username</label>
                <input class="input" name="username" required></div>
            <div><label class="label">Role</label>
                <select class="select" name="role">
                    <option value="sub">Sub-admin</option>
                    <option value="main">Main admin</option>
                </select></div>
        </div>

        <div><label class="label">Email</label>
            <input class="input" type="email" name="email" required></div>

        <div><label class="label">Password</label>
            <input class="input" type="password" name="password" required minlength="8" autocomplete="new-password">
            <div class="text-xs text-white/50 mt-1">Minimum 8 characters.</div>
        </div>

        <div><label class="label">Profile picture (optional)</label>
            <input class="input" type="file" name="profile_pic" accept="image/*"></div>

        <label class="inline-flex items-center gap-2 text-sm text-white/80 cursor-pointer">
            <input type="checkbox" name="is_active" value="1" checked class="accent-pink-500">
            Active (can sign in)
        </label>

        <button class="btn btn-primary w-full justify-center">
            <i class="fa-solid fa-user-plus"></i> Create user
        </button>
    </form>

    <!-- Users list -->
    <div class="glass rounded-2xl p-6 lg:col-span-2 overflow-x-auto">
        <div class="flex items-center justify-between mb-3">
            <h2 class="text-lg font-semibold flex items-center gap-2">
                <i class="fa-solid fa-users-gear text-indigo-400"></i> All users
            </h2>
            <span class="text-xs text-white/50"><?= count($users) ?> total</span>
        </div>

        <table class="data">
            <thead>
                <tr>
                    <th>User</th>
                    <th>Role</th>
                    <th>Status</th>
                    <th>Last login</th>
                    <th class="text-right">Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($users as $u):
                $isSelf  = ((int)$u["id"] === $selfId);
                $isMain  = ($u["role"] === "main");
                $initial = strtoupper(mb_substr((string)($u["full_name"] ?: $u["username"]), 0, 1));
                $avatar  = !empty($u["profile_pic"])
                    ? $avatarUrlDir . "/" . rawurlencode((string)$u["profile_pic"])
                    : null;
            ?>
                <tr>
                    <td>
                        <div class="flex items-center gap-3">
                            <?php if ($avatar): ?>
                                <img src="<?= htmlspecialchars($avatar) ?>"
                                     class="w-10 h-10 rounded-xl object-cover border border-white/10"
                                     alt="">
                            <?php else: ?>
                                <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-indigo-500 via-purple-500 to-pink-500 flex items-center justify-center font-bold text-white">
                                    <?= htmlspecialchars($initial) ?>
                                </div>
                            <?php endif; ?>
                            <div class="min-w-0">
                                <div class="font-semibold text-white truncate">
                                    <?= htmlspecialchars((string)$u["full_name"]) ?>
                                    <?php if ($isSelf): ?>
                                        <span class="badge badge-info ml-1">You</span>
                                    <?php endif; ?>
                                </div>
                                <div class="text-xs text-white/50 truncate">
                                    @<?= htmlspecialchars((string)$u["username"]) ?> · <?= htmlspecialchars((string)$u["email"]) ?>
                                </div>
                            </div>
                        </div>
                    </td>
                    <td>
                        <?php if ($isMain): ?>
                            <span class="badge badge-info"><i class="fa-solid fa-crown"></i> Main</span>
                        <?php else: ?>
                            <span class="badge badge-warn"><i class="fa-solid fa-user"></i> Sub</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if ((int)$u["is_active"] === 1): ?>
                            <span class="badge badge-success"><i class="fa-solid fa-circle-check"></i> Active</span>
                        <?php else: ?>
                            <span class="badge badge-danger"><i class="fa-solid fa-ban"></i> Inactive</span>
                        <?php endif; ?>
                    </td>
                    <td class="text-xs text-white/60">
                        <?= $u["last_login_at"] ? htmlspecialchars((string)$u["last_login_at"]) : "—" ?>
                    </td>
                    <td>
                        <div class="flex items-center justify-end gap-2">
                            <button type="button"
                                    onclick="openEditUser(<?= (int)$u['id'] ?>)"
                                    class="btn btn-ghost text-xs" title="Edit">
                                <i class="fa-solid fa-pen-to-square"></i>
                            </button>

                            <?php if (!$isSelf): ?>
                                <form method="POST" class="inline">
                                    <input type="hidden" name="_csrf" value="<?= htmlspecialchars(Csrf::token(), ENT_QUOTES) ?>">
                                    <input type="hidden" name="action" value="toggle">
                                    <input type="hidden" name="id" value="<?= (int)$u["id"] ?>">
                                    <button class="btn btn-ghost text-xs"
                                            title="<?= (int)$u['is_active'] === 1 ? 'Deactivate' : 'Activate' ?>">
                                        <i class="fa-solid <?= (int)$u['is_active'] === 1 ? 'fa-toggle-on text-emerald-400' : 'fa-toggle-off text-white/50' ?>"></i>
                                    </button>
                                </form>

                                <form method="POST" class="inline">
                                    <input type="hidden" name="_csrf" value="<?= htmlspecialchars(Csrf::token(), ENT_QUOTES) ?>">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id" value="<?= (int)$u["id"] ?>">
                                    <button class="btn btn-danger text-xs"
                                            data-confirm="Delete user <?= htmlspecialchars((string)$u['full_name'], ENT_QUOTES) ?>? This cannot be undone."
                                            title="Delete">
                                        <i class="fa-solid fa-trash"></i>
                                    </button>
                                </form>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>

                <!-- Edit drawer (hidden by default; toggled with JS) -->
                <tr class="hidden" id="edit-row-<?= (int)$u['id'] ?>">
                    <td colspan="5" class="bg-white/[.02]">
                        <form method="POST" enctype="multipart/form-data"
                              class="grid md:grid-cols-2 gap-3 p-4 rounded-xl border border-white/5">
                            <input type="hidden" name="_csrf" value="<?= htmlspecialchars(Csrf::token(), ENT_QUOTES) ?>">
                            <input type="hidden" name="action" value="update">
                            <input type="hidden" name="id" value="<?= (int)$u["id"] ?>">

                            <div><label class="label">Full name</label>
                                <input class="input" name="full_name"
                                       value="<?= htmlspecialchars((string)$u["full_name"]) ?>" required></div>

                            <div><label class="label">Email</label>
                                <input class="input" type="email" name="email"
                                       value="<?= htmlspecialchars((string)$u["email"]) ?>" required></div>

                            <div><label class="label">Username</label>
                                <input class="input" name="username"
                                       value="<?= htmlspecialchars((string)$u["username"]) ?>" required></div>

                            <div><label class="label">Role</label>
                                <select class="select" name="role" <?= $isSelf ? "disabled" : "" ?>>
                                    <option value="sub"  <?= $u["role"] === "sub"  ? "selected" : "" ?>>Sub-admin</option>
                                    <option value="main" <?= $u["role"] === "main" ? "selected" : "" ?>>Main admin</option>
                                </select>
                                <?php if ($isSelf): ?>
                                    <input type="hidden" name="role" value="main">
                                    <div class="text-xs text-white/40 mt-1">You can't change your own role.</div>
                                <?php endif; ?>
                            </div>

                            <div><label class="label">New password (leave blank to keep)</label>
                                <input class="input" type="password" name="password" minlength="8" autocomplete="new-password"></div>

                            <div><label class="label">Profile picture</label>
                                <input class="input" type="file" name="profile_pic" accept="image/*">
                                <?php if (!empty($u["profile_pic"])): ?>
                                    <label class="text-xs text-white/60 mt-1 inline-flex items-center gap-1.5 cursor-pointer">
                                        <input type="checkbox" name="remove_avatar" value="1" class="accent-pink-500">
                                        Remove current picture
                                    </label>
                                <?php endif; ?>
                            </div>

                            <label class="inline-flex items-center gap-2 text-sm text-white/80 cursor-pointer md:col-span-2">
                                <input type="checkbox" name="is_active" value="1"
                                       <?= (int)$u['is_active'] === 1 ? 'checked' : '' ?>
                                       <?= $isSelf ? 'disabled' : '' ?>
                                       class="accent-pink-500">
                                Active (can sign in)
                                <?php if ($isSelf): ?>
                                    <input type="hidden" name="is_active" value="1">
                                    <span class="text-xs text-white/40">— locked for your own account</span>
                                <?php endif; ?>
                            </label>

                            <div class="md:col-span-2 flex items-center gap-2">
                                <button class="btn btn-primary"><i class="fa-solid fa-save"></i> Save changes</button>
                                <button type="button" class="btn btn-ghost"
                                        onclick="document.getElementById('edit-row-<?= (int)$u['id'] ?>').classList.add('hidden')">
                                    Cancel
                                </button>
                            </div>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
function openEditUser(id) {
    document.querySelectorAll('[id^="edit-row-"]').forEach(r => {
        if (r.id !== `edit-row-${id}`) r.classList.add('hidden');
    });
    const row = document.getElementById('edit-row-' + id);
    if (row) row.classList.toggle('hidden');
}
</script>
<?php admin_layout_end(); ?>
