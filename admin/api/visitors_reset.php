<?php
declare(strict_types=1);

require __DIR__ . "/../../bootstrap.php";

use App\Auth;
use App\Database;

header("Content-Type: application/json; charset=utf-8");
header("Cache-Control: no-store");

// Must be logged-in admin.
if (!Auth::check()) {
    http_response_code(401);
    echo json_encode(["success" => false, "error" => "Unauthorised"]);
    exit;
}

// Only accept POST.
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    http_response_code(405);
    echo json_encode(["success" => false, "error" => "Method not allowed"]);
    exit;
}

try {
    Database::pdo()->exec("DELETE FROM visitor_log");
    echo json_encode(["success" => true]);
} catch (\Throwable $e) {
    http_response_code(500);
    echo json_encode(["success" => false, "error" => $e->getMessage()]);
}
