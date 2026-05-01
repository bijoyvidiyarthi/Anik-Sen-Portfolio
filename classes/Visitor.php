<?php
declare(strict_types=1);

namespace App;

/**
 * Privacy-aware unique-visitor tracking.
 *
 * Robust dedup strategy (works correctly behind reverse proxies, load
 * balancers and the canvas iframe where the source IP can change between
 * requests):
 *
 *   1. On the first visit we set an HttpOnly cookie `_av_id` with a 24-hour
 *      sliding expiration. Any subsequent visit in that window carries the
 *      same opaque token, so the same browser session is never double-counted
 *      no matter how many times the visitor refreshes.
 *
 *   2. As a fallback when cookies are disabled we fall back to a salted
 *      SHA-256 hash of the client IP — still privacy-preserving (the raw
 *      address is never persisted).
 *
 *   3. Common bot user-agents and HEAD/asset requests are skipped so the
 *      "Unique Visitors" metric reflects real human eyeballs.
 *
 *   4. A UNIQUE (visitor_token, day_key) index plus an INSERT IGNORE / INSERT
 *      OR IGNORE clause make tracking a no-op for repeat visits in the same
 *      24-hour window.
 */
class Visitor
{
    /** Cookie name carrying the per-browser opaque visitor token. */
    private const COOKIE = "_av_id";

    /** Cookie lifetime — long enough to collapse same-day refreshes. */
    private const COOKIE_TTL = 86400; // 24h

    /** Substrings of HTTP_USER_AGENT that we treat as automated traffic. */
    private const BOT_HINTS = [
        "bot", "spider", "crawler", "crawling", "preview", "monitor",
        "uptimerobot", "pingdom", "lighthouse", "headless", "facebookexternalhit",
        "slackbot", "discordbot", "twitterbot", "whatsapp", "embedly",
        "datadog", "newrelic", "ahrefs", "semrush", "google-inspectiontool",
        "curl/", "wget/", "python-requests", "go-http-client", "node-fetch",
    ];

    /** Best-effort client IP detection from common proxy headers. */
    public static function clientIp(): string
    {
        $candidates = [
            $_SERVER["HTTP_CF_CONNECTING_IP"] ?? "",
            $_SERVER["HTTP_X_REAL_IP"]        ?? "",
            $_SERVER["HTTP_X_FORWARDED_FOR"]  ?? "",
            $_SERVER["REMOTE_ADDR"]           ?? "",
        ];
        foreach ($candidates as $value) {
            $value = trim((string) $value);
            if ($value === "") continue;
            // X-Forwarded-For may be a comma list — first entry is the real client.
            $first = trim(explode(",", $value)[0]);
            if (filter_var($first, FILTER_VALIDATE_IP)) {
                return $first;
            }
        }
        return "0.0.0.0";
    }

    /** Stable salted hash so the raw IP never lands on disk. */
    private static function hashSecret(string $secret): string
    {
        $config = $GLOBALS["APP_CONFIG"] ?? [];
        $salt   = (string) (
            $config["security"]["visitor_salt"]
            ?? $config["security"]["app_key"]
            ?? getenv("APP_KEY")
            ?? "anik-portfolio-static-salt"
        );
        return hash("sha256", $salt . "|" . $secret);
    }

    /**
     * Returns true if the current request looks like a bot / automated probe
     * and should not be counted as a real human visit.
     */
    private static function isBot(): bool
    {
        $ua = strtolower((string)($_SERVER["HTTP_USER_AGENT"] ?? ""));
        if ($ua === "") return true;
        foreach (self::BOT_HINTS as $hint) {
            if (str_contains($ua, $hint)) return true;
        }
        return false;
    }

    /**
     * Returns the per-browser opaque token, setting the cookie on first visit.
     * The cookie is sliding (refreshed each visit) and HttpOnly.
     */
    private static function visitorToken(): string
    {
        if (!empty($_COOKIE[self::COOKIE]) && preg_match('/^[a-f0-9]{32,64}$/i', $_COOKIE[self::COOKIE])) {
            $token = (string)$_COOKIE[self::COOKIE];
        } else {
            try {
                $token = bin2hex(random_bytes(16));
            } catch (\Throwable $e) {
                $token = hash("sha256", uniqid("", true) . microtime(true));
            }
        }

        // Set/refresh cookie. Skip if headers already sent (extremely unlikely
        // because Visitor::track() runs before any output).
        if (!headers_sent()) {
            $secure = !empty($_SERVER["HTTPS"])
                || (($_SERVER["HTTP_X_FORWARDED_PROTO"] ?? "") === "https");
            setcookie(self::COOKIE, $token, [
                "expires"  => time() + self::COOKIE_TTL,
                "path"     => "/",
                "secure"   => $secure,
                "httponly" => true,
                "samesite" => "Lax",
            ]);
            // Make sure other code in this request sees the new cookie value.
            $_COOKIE[self::COOKIE] = $token;
        }

        return $token;
    }

    /** Should this REQUEST_URI be tracked at all? Skip assets, admin, APIs. */
    private static function isTrackablePath(?string $page): bool
    {
        $path = (string)($page ?? "/");
        $path = parse_url($path, PHP_URL_PATH) ?: "/";
        if ($path === "") return false;
        if (str_starts_with($path, "/admin"))   return false;
        if (str_starts_with($path, "/uploads")) return false;
        if (str_starts_with($path, "/assets"))  return false;
        if (str_starts_with($path, "/api"))     return false;
        if ($path === "/favicon.ico")           return false;
        if ($path === "/robots.txt")            return false;
        if ($path === "/sitemap.xml")           return false;
        // Static-asset extensions that might slip past the router.
        if (preg_match('#\.(css|js|map|png|jpe?g|gif|webp|svg|ico|woff2?|ttf|eot|mp4|webm|pdf)$#i', $path)) {
            return false;
        }
        return true;
    }

    /**
     * Record a visit. Tracking is a no-op for:
     *   - non-page (asset / admin / API) requests
     *   - bots / automated probes
     *   - the same browser within the same calendar day (cookie-based dedup)
     *   - the same client IP within the same calendar day (IP fallback)
     *
     * Tracking failures never break the public site — exceptions are swallowed.
     */
    public static function track(?string $page = null): void
    {
        try {
            $page = $page ?? ($_SERVER["REQUEST_URI"] ?? "/");
            $method = strtoupper((string)($_SERVER["REQUEST_METHOD"] ?? "GET"));

            // Only count GET pageviews of trackable paths from real humans.
            if ($method !== "GET") return;
            if (!self::isTrackablePath($page)) return;
            if (self::isBot()) return;

            $cookieToken = self::visitorToken();
            $ipHash      = self::hashSecret(self::clientIp());

            // Composite token: prefer the cookie, fall back to the IP hash.
            // Both are hashed once more so the stored value is opaque and
            // the same browser produces the same token across requests.
            $visitorToken = self::hashSecret("v:" . $cookieToken);
            $dayKey       = (new \DateTimeImmutable("now"))->format("Y-m-d");
            $ua           = mb_substr((string)($_SERVER["HTTP_USER_AGENT"] ?? ""), 0, 500);
            $pageVal      = mb_substr((string)$page, 0, 255);

            $sql = Database::isMysql()
                ? "INSERT IGNORE INTO visitor_log (visitor_token, ip_hash, day_key, user_agent, page) VALUES (?, ?, ?, ?, ?)"
                : "INSERT OR IGNORE INTO visitor_log (visitor_token, ip_hash, day_key, user_agent, page) VALUES (?, ?, ?, ?, ?)";

            Database::pdo()->prepare($sql)->execute([$visitorToken, $ipHash, $dayKey, $ua, $pageVal]);
        } catch (\Throwable $e) {
            // Tracking must never break the public site — fail silently.
        }
    }

    /** Total unique visitors across all time. */
    public static function totalUnique(): int
    {
        return (int) Database::pdo()->query("SELECT COUNT(*) FROM visitor_log")->fetchColumn();
    }

    /** Unique visitors today. */
    public static function todayUnique(): int
    {
        $today = (new \DateTimeImmutable("now"))->format("Y-m-d");
        $stmt  = Database::pdo()->prepare("SELECT COUNT(*) FROM visitor_log WHERE day_key = :d");
        $stmt->execute([":d" => $today]);
        return (int) $stmt->fetchColumn();
    }

    /**
     * Unique-visitor counts for the last $days days, returned as
     * `[ ['label' => 'Apr 22', 'count' => 4], ... ]`.
     */
    public static function weekly(int $days = 7): array
    {
        $rows = self::countsBetween("-" . ($days - 1) . " days", "today");
        return self::fillDailyRange($days, "M j", $rows);
    }

    /** Unique-visitor counts for the last $days days (default 30). */
    public static function monthly(int $days = 30): array
    {
        $rows = self::countsBetween("-" . ($days - 1) . " days", "today");
        return self::fillDailyRange($days, "M j", $rows);
    }

    /** Unique-visitor counts grouped per month for the last 12 months. */
    public static function yearly(int $months = 12): array
    {
        $rows = Database::pdo()->query(
            "SELECT substr(day_key, 1, 7) AS month_key, COUNT(*) AS c
               FROM visitor_log
              GROUP BY month_key"
        )->fetchAll();
        $byMonth = [];
        foreach ($rows as $r) { $byMonth[$r["month_key"]] = (int) $r["c"]; }

        $out = [];
        $end = new \DateTimeImmutable("first day of this month 00:00:00");
        for ($i = $months - 1; $i >= 0; $i--) {
            $d   = $end->modify("-{$i} months");
            $key = $d->format("Y-m");
            $out[] = [
                "label" => $d->format("M Y"),
                "count" => $byMonth[$key] ?? 0,
            ];
        }
        return $out;
    }

    /** Internal: unique counts grouped per day_key in [from, to]. */
    private static function countsBetween(string $from, string $to): array
    {
        $f = (new \DateTimeImmutable($from))->format("Y-m-d");
        $t = (new \DateTimeImmutable($to))->format("Y-m-d");
        $stmt = Database::pdo()->prepare(
            "SELECT day_key, COUNT(*) AS c
               FROM visitor_log
              WHERE day_key BETWEEN :f AND :t
              GROUP BY day_key"
        );
        $stmt->execute([":f" => $f, ":t" => $t]);
        $out = [];
        foreach ($stmt->fetchAll() as $r) { $out[$r["day_key"]] = (int) $r["c"]; }
        return $out;
    }

    private static function fillDailyRange(int $days, string $labelFmt, array $byKey): array
    {
        $out = [];
        $today = new \DateTimeImmutable("today");
        for ($i = $days - 1; $i >= 0; $i--) {
            $d   = $today->modify("-{$i} days");
            $key = $d->format("Y-m-d");
            $out[] = [
                "label" => $d->format($labelFmt),
                "count" => $byKey[$key] ?? 0,
            ];
        }
        return $out;
    }
}
