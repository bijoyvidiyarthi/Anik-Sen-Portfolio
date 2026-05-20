<?php
/**
 * PHP built-in server router.
 * Serves static files directly; routes everything else through the
 * appropriate index.php (admin or public).
 */

$uri  = parse_url($_SERVER["REQUEST_URI"], PHP_URL_PATH);
$path = __DIR__ . $uri;

// Serve existing static files (images, css, js, etc.) directly.
if ($uri !== "/" && file_exists($path) && !is_dir($path)) {
    return false;
}

// Route .php files explicitly.
if (preg_match('#\.php$#', $uri) && file_exists($path)) {
    require $path;
    return true;
}

// Directory request → serve its index.php if present (e.g. /admin/, /admin).
if (is_dir($path)) {
    $indexFile = rtrim($path, "/") . "/index.php";
    if (file_exists($indexFile)) {
        // Normalise /admin → /admin/ so relative links inside the page resolve.
        if (substr($uri, -1) !== "/") {
            header("Location: " . $uri . "/");
            return true;
        }
        require $indexFile;
        return true;
    }
}

// Default to the public site's front controller.
require __DIR__ . "/index.php";
return true;
