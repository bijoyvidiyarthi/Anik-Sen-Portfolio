<?php
/**
 * Secure JSON API for visitor analytics.
 * Requires an authenticated admin session and a same-origin XHR.
 */
declare(strict_types=1);

require __DIR__ . "/../../bootstrap.php";

use App\Auth;
use App\Visitor;

header("Content-Type: application/json; charset=utf-8");
header("Cache-Control: no-store");
header("X-Content-Type-Options: nosniff");

if (!Auth::check()) {
    http_response_code(401);
    echo json_encode(["success" => false, "error" => "Authentication required."]);
    exit;
}

$range = $_GET["range"] ?? "weekly";
$valid = ["weekly", "monthly", "yearly"];
if (!in_array($range, $valid, true)) {
    http_response_code(422);
    echo json_encode(["success" => false, "error" => "Unknown range."]);
    exit;
}

try {
    $series = match ($range) {
        "weekly"  => Visitor::weekly(7),
        "monthly" => Visitor::monthly(30),
        "yearly"  => Visitor::yearly(12),
    };

    echo json_encode([
        "success" => true,
        "range"   => $range,
        "labels"  => array_map(fn($r) => $r["label"], $series),
        "counts"  => array_map(fn($r) => $r["count"], $series),
        "total"   => array_sum(array_map(fn($r) => $r["count"], $series)),
    ]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(["success" => false, "error" => "Could not load analytics."]);
}
