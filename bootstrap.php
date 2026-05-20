<?php
/**
 * Application bootstrap.
 * - Loads config
 * - Registers PSR-4-style autoloader for App\* classes (including sub-namespaces)
 * - Starts session
 * - Initialises database + runs migrations on first boot
 */

declare(strict_types=1);

if (defined("APP_BOOTSTRAPPED")) {
    return $GLOBALS["APP_CONFIG"];
}
define("APP_BOOTSTRAPPED", true);

$ROOT = __DIR__;

// Load .env file if present (used for shared hosting like InfinityFree).
// Environment variables already set by the server take priority.
$_envFile = $ROOT . "/.env";
if (is_file($_envFile)) {
    $lines = file($_envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $_line) {
        $_line = trim($_line);
        if ($_line === "" || $_line[0] === "#") {
            continue;
        }
        if (strpos($_line, "=") === false) {
            continue;
        }
        [$_key, $_val] = explode("=", $_line, 2);
        $_key = trim($_key);
        $_val = trim(trim($_val), "\"'");
        if ($_key !== "" && getenv($_key) === false) {
            putenv("$_key=$_val");
            $_ENV[$_key] = $_val;
        }
    }
    unset($_envFile, $lines, $_line, $_key, $_val);
}

/** @var array $config */
$config = require $ROOT . "/config/config.php";
$GLOBALS["APP_CONFIG"] = $config;

date_default_timezone_set($config["app"]["timezone"]);

if ($config["app"]["debug"]) {
    error_reporting(E_ALL);
    ini_set("display_errors", "1");
} else {
    ini_set("display_errors", "0");
}

// Increase upload limits at runtime (allows large video uploads via the admin panel).
@ini_set("upload_max_filesize", "100M");
@ini_set("post_max_size",       "110M");
@ini_set("memory_limit",        "256M");
@ini_set("max_execution_time",  "120");

// --- PSR-4 Autoloader for App\* classes ---
// Covers App\, App\Contracts\, App\Repositories\, App\Services\, etc.
// The namespace App\ maps to /classes/, so:
//   App\Project            -> /classes/Project.php
//   App\Contracts\FooInterface -> /classes/Contracts/FooInterface.php
//   App\Repositories\BaseRepository -> /classes/Repositories/BaseRepository.php
spl_autoload_register(static function (string $class) use ($ROOT): void {
    if (!str_starts_with($class, "App\\")) {
        return;
    }
    $rel  = substr($class, 4); // strip "App\" prefix
    $path = $ROOT . "/classes/" . str_replace("\\", "/", $rel) . ".php";
    if (is_file($path)) {
        require_once $path;
    }
});

// --- Session ---
if (session_status() === PHP_SESSION_NONE) {
    session_name($config["session"]["name"]);
    session_set_cookie_params([
        "lifetime" => $config["session"]["lifetime"],
        "path"     => "/",
        "secure"   => !empty($_SERVER["HTTPS"]),
        "httponly" => true,
        "samesite" => "Lax",
    ]);
    session_start();
}

// --- Database init + migrations ---
\App\Database::init($config);
\App\Migrator::ensure($config);

return $config;
