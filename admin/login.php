<?php
declare(strict_types=1);

require __DIR__ . "/../bootstrap.php";

use App\Auth;
use App\Csrf;

if (Auth::check()) {
    header("Location: /admin/");
    exit;
}

$error = "";
$user  = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    if (!Csrf::check($_POST["_csrf"] ?? null)) {
        $error = "Security token expired. Please try again.";
    } else {
        $user = trim((string)($_POST["username"] ?? ""));
        $pass = (string)($_POST["password"] ?? "");
        if ($user === "" || $pass === "") {
            $error = "Please enter both username and password.";
        } elseif (Auth::attempt($user, $pass)) {
            header("Location: /admin/");
            exit;
        } else {
            $error = "Invalid credentials.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Login</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body{
            font-family:'Inter',sans-serif;
            min-height:100vh;
            background:
              radial-gradient(1100px 700px at 10% -10%, rgba(99,102,241,.30), transparent 60%),
              radial-gradient(900px 600px at 100% 20%, rgba(236,72,153,.30), transparent 60%),
              radial-gradient(900px 700px at 50% 110%, rgba(34,211,238,.25), transparent 60%),
              #06070d;
            color:#e6e8f1;
            display:flex;align-items:center;justify-content:center;padding:2rem;
        }
        .glass{
            background:rgba(20,22,38,.55);
            backdrop-filter: blur(22px) saturate(160%);
            border:1px solid rgba(255,255,255,.10);
            box-shadow:0 30px 80px -20px rgba(2,4,18,.7);
        }
        .input{
            width:100%;padding:.7rem .9rem;border-radius:.6rem;
            background:rgba(8,10,24,.65);border:1px solid rgba(255,255,255,.10);
            color:#fff;outline:none;
        }
        .input:focus{border-color:rgba(168,85,247,.55);box-shadow:0 0 0 3px rgba(168,85,247,.20);}
        .btn{
            background:linear-gradient(135deg,#6366f1,#ec4899);
            padding:.75rem 1rem;border-radius:.65rem;font-weight:600;color:#fff;
            box-shadow:0 12px 30px -8px rgba(168,85,247,.55);
            transition:transform .15s,box-shadow .2s;
        }
        .btn:hover{transform:translateY(-1px);}
    </style>
</head>
<body>
<div class="glass w-full max-w-md rounded-2xl p-8">
    <div class="flex items-center gap-3 mb-6">
        <div class="w-12 h-12 rounded-2xl bg-gradient-to-br from-indigo-500 via-purple-500 to-pink-500 flex items-center justify-center">
            <i class="fa-solid fa-wand-magic-sparkles text-white text-xl"></i>
        </div>
        <div>
            <div class="text-lg font-bold text-white">Admin Console</div>
            <div class="text-xs text-white/50">Sign in to manage your portfolio</div>
        </div>
    </div>

    <?php if ($error): ?>
        <div class="mb-4 p-3 rounded-lg bg-rose-500/15 border border-rose-500/30 text-rose-200 text-sm">
            <i class="fa-solid fa-circle-exclamation mr-1"></i> <?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>

    <form method="POST" action="/admin/login.php" class="space-y-4" novalidate>
        <input type="hidden" name="_csrf" value="<?= htmlspecialchars(Csrf::token(), ENT_QUOTES) ?>">

        <div>
            <label class="text-xs font-semibold text-white/70 mb-1 block">Username or Email</label>
            <input type="text" name="username" class="input" autocomplete="username"
                   value="<?= htmlspecialchars($user) ?>" required autofocus>
        </div>

        <div>
            <label class="text-xs font-semibold text-white/70 mb-1 block">Password</label>
            <input type="password" name="password" class="input" autocomplete="current-password" required>
        </div>

        <button type="submit" class="btn w-full mt-2">
            <i class="fa-solid fa-right-to-bracket mr-1"></i> Sign In
        </button>
    </form>

    <div class="mt-6 text-xs text-white/50 text-center">
        Default: <code class="text-white/70">admin</code> / <code class="text-white/70">admin1234</code> · Change after first login.
    </div>
</div>
</body>
</html>
