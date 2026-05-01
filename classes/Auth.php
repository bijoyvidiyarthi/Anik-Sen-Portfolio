<?php
declare(strict_types=1);

namespace App;

use PDO;

/**
 * Admin authentication using PHP sessions and password_hash.
 *
 * Session payload (keys under $_SESSION["admin"]):
 *   - id, username, email, full_name, profile_pic, role, is_active
 */
class Auth
{
    public static function attempt(string $username, string $password): bool
    {
        $stmt = Database::pdo()->prepare(
            "SELECT id, username, email, password_hash, full_name, profile_pic, role, is_active
             FROM admin_users
             WHERE username = :u OR email = :u
             LIMIT 1"
        );
        $stmt->execute([":u" => $username]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user || !password_verify($password, $user["password_hash"])) {
            return false;
        }

        // Block inactive accounts.
        if ((int)($user["is_active"] ?? 1) !== 1) {
            return false;
        }

        // Refresh session id to prevent fixation.
        session_regenerate_id(true);
        self::storeSession($user);

        Database::pdo()->prepare(
            "UPDATE admin_users SET last_login_at = " . self::nowSql() . " WHERE id = :id"
        )->execute([":id" => $user["id"]]);

        return true;
    }

    public static function logout(): void
    {
        // Wipe in-memory session payload (admin, csrf, flash, etc.).
        $_SESSION = [];

        // Clear the session cookie on the browser.
        if (ini_get("session.use_cookies")) {
            $p = session_get_cookie_params();
            setcookie(
                session_name(),
                "",
                time() - 42000,
                $p["path"]   ?? "/",
                $p["domain"] ?? "",
                (bool)($p["secure"]   ?? false),
                (bool)($p["httponly"] ?? true),
            );
        }

        // Hard-destroy server-side session storage.
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_destroy();
        }
    }

    public static function check(): bool
    {
        return !empty($_SESSION["admin"]["id"]);
    }

    public static function user(): ?array
    {
        return $_SESSION["admin"] ?? null;
    }

    public static function id(): ?int
    {
        $u = self::user();
        return $u ? (int)$u["id"] : null;
    }

    public static function isMain(): bool
    {
        $u = self::user();
        return $u && (($u["role"] ?? "sub") === "main");
    }

    public static function require(): void
    {
        if (!self::check()) {
            header("Location: " . self::loginUrl());
            exit;
        }

        // Re-validate the session against the DB on every protected request:
        // if the row was deactivated or deleted while the session was alive,
        // log the user out immediately. Cheap query, runs once per request.
        try {
            $row = Database::pdo()->prepare(
                "SELECT is_active FROM admin_users WHERE id = :id LIMIT 1"
            );
            $row->execute([":id" => self::id()]);
            $active = $row->fetchColumn();
            if ($active === false || (int)$active !== 1) {
                self::logout();
                header("Location: " . self::loginUrl());
                exit;
            }
        } catch (\Throwable $e) {
            // If we can't verify, fall back to closed-mode (force logout).
            self::logout();
            header("Location: " . self::loginUrl());
            exit;
        }
    }

    public static function requireMain(): void
    {
        self::require();
        if (!self::isMain()) {
            http_response_code(403);
            exit("Forbidden — main administrator only.");
        }
    }

    public static function loginUrl(): string
    {
        return "/admin/";
    }

    public static function changePassword(int $userId, string $newPassword): void
    {
        $hash = password_hash($newPassword, PASSWORD_BCRYPT);
        Database::pdo()->prepare(
            "UPDATE admin_users SET password_hash = :h WHERE id = :id"
        )->execute([":h" => $hash, ":id" => $userId]);
    }

    /**
     * Reload the active session payload from the database (call after
     * profile / avatar updates so the sidebar reflects the change).
     */
    public static function refreshSession(): void
    {
        $id = self::id();
        if (!$id) return;

        $stmt = Database::pdo()->prepare(
            "SELECT id, username, email, full_name, profile_pic, role, is_active
             FROM admin_users WHERE id = :id LIMIT 1"
        );
        $stmt->execute([":id" => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row) self::storeSession($row);
    }

    private static function storeSession(array $row): void
    {
        $_SESSION["admin"] = [
            "id"          => (int)$row["id"],
            "username"    => (string)$row["username"],
            "email"       => (string)$row["email"],
            "full_name"   => (string)($row["full_name"]   ?? $row["username"]),
            "profile_pic" => $row["profile_pic"] !== null ? (string)$row["profile_pic"] : null,
            "role"        => (string)($row["role"]        ?? "sub"),
            "is_active"   => (int)   ($row["is_active"]   ?? 1),
        ];
    }

    private static function nowSql(): string
    {
        return Database::isMysql() ? "NOW()" : "CURRENT_TIMESTAMP";
    }
}
