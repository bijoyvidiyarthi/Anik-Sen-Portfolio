<?php
/**
 * Contact form handler — persists into messages table via PDO with CSRF.
 */
declare(strict_types=1);

require __DIR__ . "/bootstrap.php";

use App\Csrf;
use App\Message;

header("Content-Type: application/json; charset=utf-8");

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    http_response_code(405);
    echo json_encode(["success" => false, "error" => "Method not allowed"]);
    exit;
}

if (!Csrf::check($_POST["_csrf"] ?? null)) {
    http_response_code(419);
    echo json_encode(["success" => false, "error" => "Security token expired. Please reload."]);
    exit;
}

$name    = trim((string) ($_POST["name"]    ?? ""));
$email   = trim((string) ($_POST["email"]   ?? ""));
$subject = trim((string) ($_POST["subject"] ?? ""));
$message = trim((string) ($_POST["message"] ?? ""));

$errors = [];
if (mb_strlen($name) < 2)            $errors["name"]    = "Name must be at least 2 characters.";
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors["email"]   = "Please enter a valid email.";
if (mb_strlen($subject) < 5)         $errors["subject"] = "Subject must be at least 5 characters.";
if (mb_strlen($message) < 10)        $errors["message"] = "Message must be at least 10 characters.";

if ($errors) {
    http_response_code(422);
    echo json_encode([
        "success" => false,
        "error"   => "Please correct the highlighted fields.",
        "fields"  => $errors,
    ]);
    exit;
}

try {
    Message::create([
        "name" => $name, "email" => $email,
        "subject" => $subject, "message" => $message,
    ]);
    echo json_encode([
        "success" => true,
        "message" => "Thanks for reaching out! I'll get back to you soon.",
    ]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "error"   => "Sorry, your message could not be saved right now.",
    ]);
}
