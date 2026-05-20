<?php
declare(strict_types=1);

namespace App;

/**
 * Privacy-aware unique-visitor tracking.
 *
 * Deduplication model (v19+):
 *
 *   1. On the first visit we set an HttpOnly cookie `_av_id` with a 1-year
 *      expiration. The same browser always returns the same opaque token.
 *
 *   2. visitor_log has a UNIQUE index on visitor_token alone. The very first
 *      INSERT succeeds; every subsequent visit from the same browser is a
 *      silent no-op (INSERT OR IGNORE). One row = one unique person, ever.
 *
 *   3. day_key stores the date of the visitor's FIRST arrival. The daily /
 *      monthly / yearly charts therefore show "new unique arrivals" per period —
 *      not total hits.
 *
 *   4. If cookies are unavailable we fall back to a salted SHA-256 hash of the
 *      client IP (still one row, still permanent once inserted).
 *
 *   5. Common bot user-agents and non-page requests are skipped.
 */
class Visitor
{
    /** Cookie name carrying the per-browser opaque visitor token. */
    private const COOKIE = "_av_id";

    /** Cookie lifetime — 1 year so the same browser is always recognised. */
    private const COOKIE_TTL = 365 * 86400;

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

    /** Returns true if the request looks like a bot / automated probe. */
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
     * Returns the per-browser opaque token, setting the 1-year cookie on
     * the first visit. The same browser will carry the same token forever,
     * so repeat visits on any future day are never counted again.
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
        if (preg_match('#\.(css|js|map|png|jpe?g|gif|webp|svg|ico|woff2?|ttf|eot|mp4|webm|pdf)$#i', $path)) {
            return false;
        }
        return true;
    }

    /**
     * Record a visit. This is a complete no-op for:
     *   - non-page (asset / admin / API) requests
     *   - bots / automated probes
     *   - any browser that has already been counted (cookie matches a known token)
     *
     * The UNIQUE index on visitor_token ensures one row per browser, ever.
     * Tracking failures never break the public site — exceptions are swallowed.
     */
    public static function track(?string $page = null): void
    {
        try {
            $page   = $page ?? ($_SERVER["REQUEST_URI"] ?? "/");
            $method = strtoupper((string)($_SERVER["REQUEST_METHOD"] ?? "GET"));

            if ($method !== "GET") return;
            if (!self::isTrackablePath($page)) return;
            if (self::isBot()) return;

            $cookieToken  = self::visitorToken();
            $ipHash       = self::hashSecret(self::clientIp());
            $visitorToken = self::hashSecret("v:" . $cookieToken);
            $dayKey       = (new \DateTimeImmutable("now"))->format("Y-m-d");
            $ua           = mb_substr((string)($_SERVER["HTTP_USER_AGENT"] ?? ""), 0, 500);
            $pageVal      = mb_substr((string)$page, 0, 255);

            // UNIQUE on visitor_token: first visit inserts, all later visits
            // silently fail — the count never inflates.
            $sql = Database::isMysql()
                ? "INSERT IGNORE INTO visitor_log (visitor_token, ip_hash, day_key, user_agent, page) VALUES (?, ?, ?, ?, ?)"
                : "INSERT OR IGNORE INTO visitor_log (visitor_token, ip_hash, day_key, user_agent, page) VALUES (?, ?, ?, ?, ?)";

            Database::pdo()->prepare($sql)->execute([$visitorToken, $ipHash, $dayKey, $ua, $pageVal]);
        } catch (\Throwable $e) {
            // Tracking must never break the public site — fail silently.
        }
    }

    /**
     * Total unique visitors ever recorded.
     * Because UNIQUE is on visitor_token, COUNT(*) = number of distinct browsers.
     */
    public static function totalUnique(): int
    {
        return (int) Database::pdo()->query("SELECT COUNT(*) FROM visitor_log")->fetchColumn();
    }

    /**
     * Visitors whose very first visit was today (new arrivals today).
     */
    public static function todayUnique(): int
    {
        $today = (new \DateTimeImmutable("now"))->format("Y-m-d");
        $stmt  = Database::pdo()->prepare(
            "SELECT COUNT(*) FROM visitor_log WHERE day_key = :d"
        );
        $stmt->execute([":d" => $today]);
        return (int) $stmt->fetchColumn();
    }

    /**
     * Count of visitors whose first arrival fell between $fromDate and $toDate
     * (inclusive, "Y-m-d" format). A visitor who first came in week 1 and
     * revisited in week 2 appears only in week 1's count.
     */
    public static function distinctInRange(string $fromDate, string $toDate): int
    {
        $stmt = Database::pdo()->prepare(
            "SELECT COUNT(*) FROM visitor_log WHERE day_key BETWEEN :f AND :t"
        );
        $stmt->execute([":f" => $fromDate, ":t" => $toDate]);
        return (int) $stmt->fetchColumn();
    }

    /**
     * Daily new-visitor bar data for the last $days days.
     * Each bar = visitors whose FIRST visit was on that specific day.
     */
    public static function weekly(int $days = 7): array
    {
        $rows = self::countsBetween("-" . ($days - 1) . " days", "today");
        return self::fillDailyRange($days, "M j", $rows);
    }

    /**
     * Daily new-visitor bar data for the last $days days (default 30).
     */
    public static function monthly(int $days = 30): array
    {
        $rows = self::countsBetween("-" . ($days - 1) . " days", "today");
        return self::fillDailyRange($days, "M j", $rows);
    }

    /**
     * Monthly new-visitor bar data for the last $months months.
     * Each bar = visitors whose FIRST visit was in that calendar month.
     */
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

    /**
     * Internal: new visitor counts grouped per day_key in [from, to].
     * Since each visitor_token is stored exactly once, COUNT(*) per day_key
     * equals the number of brand-new visitors who arrived that day.
     */
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
