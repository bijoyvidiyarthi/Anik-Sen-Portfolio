<?php
declare(strict_types=1);

require __DIR__ . "/../bootstrap.php";
require __DIR__ . "/partials/layout.php";

use App\Settings;
use App\Csrf;
use App\Upload;

$config = $GLOBALS["APP_CONFIG"];

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    Csrf::require();
    try {
        $kv = [
            "site_name"        => trim((string)($_POST["site_name"] ?? "")),
            "tagline"          => trim((string)($_POST["tagline"] ?? "")),
            "email"            => trim((string)($_POST["email"] ?? "")),
            "location"         => trim((string)($_POST["location"] ?? "")),
            "social_facebook"  => trim((string)($_POST["social_facebook"] ?? "")),
            "social_linkedin"  => trim((string)($_POST["social_linkedin"] ?? "")),
            "social_behance"   => trim((string)($_POST["social_behance"] ?? "")),
            "footer_about"     => trim((string)($_POST["footer_about"] ?? "")),
        ];

        if (!empty($_FILES["logo"]["name"])) {
            $name = Upload::image($_FILES["logo"], $config["paths"]["image_dir"]);
            Upload::delete($config["paths"]["image_dir"], Settings::get("logo"));
            $kv["logo"] = $name;
        }
        if (!empty($_FILES["favicon"]["name"])) {
            $name = Upload::image($_FILES["favicon"], $config["paths"]["image_dir"]);
            Upload::delete($config["paths"]["image_dir"], Settings::get("favicon"));
            $kv["favicon"] = $name;
        }

        if (!empty($_POST["clear_logo"]))    { Upload::delete($config["paths"]["image_dir"], Settings::get("logo")); $kv["logo"] = ""; }
        if (!empty($_POST["clear_favicon"])) { Upload::delete($config["paths"]["image_dir"], Settings::get("favicon")); $kv["favicon"] = ""; }

        Settings::setMany($kv);
        flash_set("success", "Site settings saved.");
    } catch (Throwable $e) {
        flash_set("error", $e->getMessage());
    }
    header("Location: /admin/settings.php"); exit;
}

$s = Settings::all();

admin_layout_start("Site Settings", "settings");
?>
<?= flash_render() ?>
<form method="POST" enctype="multipart/form-data" class="grid lg:grid-cols-3 gap-5">
    <input type="hidden" name="_csrf" value="<?= htmlspecialchars(Csrf::token(), ENT_QUOTES) ?>">

    <div class="lg:col-span-2 glass rounded-2xl p-6 space-y-4">
        <h2 class="text-lg font-semibold mb-2">General</h2>
        <div class="grid sm:grid-cols-2 gap-4">
            <div><label class="label">Site name</label><input class="input" name="site_name" value="<?= htmlspecialchars($s["site_name"] ?? "") ?>"></div>
            <div><label class="label">Tagline</label><input class="input" name="tagline" value="<?= htmlspecialchars($s["tagline"] ?? "") ?>"></div>
            <div><label class="label">Contact email</label><input class="input" type="email" name="email" value="<?= htmlspecialchars($s["email"] ?? "") ?>"></div>
            <div><label class="label">Location</label><input class="input" name="location" value="<?= htmlspecialchars($s["location"] ?? "") ?>"></div>
        </div>

        <h2 class="text-lg font-semibold mt-4">Footer</h2>
        <div><label class="label">Short footer description</label>
            <textarea class="textarea" name="footer_about" rows="2"><?= htmlspecialchars($s["footer_about"] ?? "") ?></textarea>
        </div>

        <h2 class="text-lg font-semibold mt-4">Social Links</h2>
        <div class="grid sm:grid-cols-3 gap-4">
            <div><label class="label">Facebook URL</label><input class="input" name="social_facebook" value="<?= htmlspecialchars($s["social_facebook"] ?? "") ?>"></div>
            <div><label class="label">LinkedIn URL</label><input class="input" name="social_linkedin" value="<?= htmlspecialchars($s["social_linkedin"] ?? "") ?>"></div>
            <div><label class="label">Behance URL</label><input class="input" name="social_behance" value="<?= htmlspecialchars($s["social_behance"] ?? "") ?>"></div>
        </div>
    </div>

    <div class="glass rounded-2xl p-6 space-y-5">
        <div>
            <h2 class="text-lg font-semibold mb-2">Logo</h2>
            <?php if (!empty($s["logo"])): ?>
                <div class="mb-2 p-3 rounded-lg bg-black/30 border border-white/10 flex items-center gap-3">
                    <img src="/uploads/images/<?= htmlspecialchars($s["logo"]) ?>" alt="" class="h-10 w-auto bg-white/10 p-1 rounded">
                    <label class="text-xs flex items-center gap-1 ml-auto"><input type="checkbox" name="clear_logo" value="1"> Remove</label>
                </div>
            <?php endif; ?>
            <input type="file" name="logo" accept="image/*" class="input">
        </div>

        <div>
            <h2 class="text-lg font-semibold mb-2">Favicon</h2>
            <?php if (!empty($s["favicon"])): ?>
                <div class="mb-2 p-3 rounded-lg bg-black/30 border border-white/10 flex items-center gap-3">
                    <img src="/uploads/images/<?= htmlspecialchars($s["favicon"]) ?>" alt="" class="h-8 w-8 bg-white/10 p-1 rounded">
                    <label class="text-xs flex items-center gap-1 ml-auto"><input type="checkbox" name="clear_favicon" value="1"> Remove</label>
                </div>
            <?php endif; ?>
            <input type="file" name="favicon" accept="image/*" class="input">
        </div>

        <button type="submit" class="btn btn-primary w-full justify-center">
            <i class="fa-solid fa-save"></i> Save Settings
        </button>
    </div>
</form>
<?php admin_layout_end(); ?>
