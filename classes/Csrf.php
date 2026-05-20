<?php
declare(strict_types=1);

namespace App;

/**
 * CSRF token helper. Stores per-session token, validates on form posts.
 */
class Csrf
{
    public static function token(): string
    {
        if (empty($_SESSION["_csrf"])) {
            $_SESSION["_csrf"] = bin2hex(random_bytes(32));
        }
        return $_SESSION["_csrf"];
    }

    public static function field(): string
    {
        $t = htmlspecialchars(self::token(), ENT_QUOTES);
        return '<input type="hidden" name="_csrf" value="' . $t . '">';
    }

    public static function check(?string $token): bool
    {
        $stored = $_SESSION["_csrf"] ?? "";
        return is_string($token) && $stored !== "" && hash_equals($stored, $token);
    }

    public static function require(): void
    {
        $tok = $_POST["_csrf"] ?? null;
        if (!self::check($tok)) {
            http_response_code(419);
            exit("CSRF token mismatch. Please reload and try again.");
        }
    }
}
