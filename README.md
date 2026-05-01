# Anik Sen — Portfolio CMS

A premium, glassmorphic, fully responsive single-page portfolio for **Anik Sen**
(Graphic Designer & Video Editor) backed by a self-hosted PHP **CMS**, an admin
dashboard, and a zero-config SQLite database (with optional MySQL).

The frontend is hand-crafted HTML, CSS and vanilla JavaScript — no JS
framework, no build step. The backend is plain PHP 8 with PDO, organised as a
small object-oriented application that you can deploy to any commodity host.

> **Developer / Maintainer:** **Aryaan Dhar Badhon**

---

## Table of contents

1. [Live preview](#live-preview)
2. [Highlights](#highlights)
3. [Tech stack](#tech-stack)
4. [Project structure](#project-structure)
5. [Local development](#local-development)
6. [Database](#database-sqlite-default--mysql-optional)
7. [Admin panel guide](#admin-panel-guide)
8. [Free hosting deployment](#free-hosting-deployment)
9. [Premium hosting deployment](#premium-hosting-deployment)
10. [SEO & ranking guide](#seo--ranking-guide)
11. [Troubleshooting](#troubleshooting)
12. [Security checklist](#security-checklist)
13. [Credits](#credits)

---

## Live preview

The hero section is built around a hand-rendered 3D creative workspace
illustration. The avatar floats slowly, the glow halo pulses, and the column
gap tightens on large displays so the name and the avatar feel as one
composition.

![Hero avatar — 3D creative workspace](docs/screenshots/hero-avatar.png)

---

## Highlights

- **Single-page portfolio** with hero, about, skills, projects, gallery,
  clients, reviews, education, and contact sections.
- **Glassmorphic UI** — frosted cards, soft purple/blue gradients, subtle
  motion (`@keyframes` floats, scroll reveals, animated typewriter title).
- **Full CMS** — every visible string, image, project, skill, review and
  menu item is editable from `/admin/`.
- **Section toggles** — show/hide any section without touching code.
- **Dynamic header & footer menus** — admin-driven `menu_items` table.
- **Contact inbox** with read/unread state, search and pagination.
- **Hero CV PDF upload** with strict `%PDF` magic-byte + finfo MIME validation.
- **Unique-visitor analytics** — salted SHA-256 IP hashes + Chart.js dashboard
  (weekly / monthly / yearly tabs) and a CSRF/session-protected JSON API.
- **Zero-config persistence** — SQLite by default; one env switch turns on
  MySQL for production.
- **Responsive** from 320 px mobile up to 4K, with `prefers-reduced-motion`
  honoured everywhere.

---

## Tech stack

| Layer        | Technology                                   |
|--------------|----------------------------------------------|
| Language     | PHP 8.1+                                     |
| Database     | SQLite (default) or MySQL 5.7+ / MariaDB 10+ |
| Frontend     | HTML5, CSS3 (custom), vanilla JS, Chart.js   |
| Admin UI     | Tailwind CSS (CDN, admin-only)               |
| Server       | Apache, Nginx + PHP-FPM, or PHP built-in     |
| Dev runtime  | `php -S` via the included router             |

No Composer dependencies — PSR-style autoloader is included in
`bootstrap.php`.

---

## Project structure

> **Hosting note:** Upload the **entire project folder** directly into
> `public_html/` or `www/` on your shared host. `index.php` now lives at the
> root — there is no nested `/public` sub-folder to configure.

```
your-project/                  <- upload this entire folder into public_html/
|
+-- index.php                  <- front controller (web entry point)
+-- contact.php                <- contact form POST handler
+-- cv.php                     <- streams the active CV PDF
+-- health.php                 <- returns {"status":"ok"} for uptime probes
+-- router.php                 <- PHP built-in dev server only (not needed in prod)
+-- opengraph.jpg              <- default OG share image
+-- .htaccess                  <- Apache URL rewriting + security rules
|
+-- bootstrap.php              <- autoloader, config, DB, session, migrations
+-- package.json               <- convenience dev script (php -S)
+-- README.md                  <- this file
|
+-- classes/                   <- OOP domain layer (PSR-4 "App\...")
|   +-- Database.php           <- PDO singleton (SQLite + MySQL)
|   +-- Migrator.php           <- versioned, idempotent migrations
|   +-- Auth.php               <- admin login / session / hashing
|   +-- Csrf.php               <- per-session CSRF tokens
|   +-- Upload.php             <- image + PDF uploads (mime + magic-byte)
|   +-- Hero.php               <- hero copy, avatar, CV PDF
|   +-- About.php
|   +-- Skill.php / Software.php
|   +-- Project.php / ProjectImage.php
|   +-- GalleryCategory.php / GalleryImage.php
|   +-- Client.php / Review.php
|   +-- Education.php
|   +-- Message.php            <- contact inbox
|   +-- Settings.php           <- site-wide settings (title, SEO, social)
|   +-- SiteSection.php        <- section visibility toggles
|   +-- MenuItem.php           <- header/footer nav CRUD
|   +-- Visitor.php            <- unique-visitor analytics
|   +-- FileLibrary.php        <- media library helper
|
+-- config/
|   +-- config.php             <- app + DB + admin defaults (env-driven)
|
+-- data/
|   +-- portfolio.sqlite       <- created automatically on first boot (SQLite)
|
+-- sql/
|   +-- schema_mysql.sql       <- MySQL schema + seed data (reference only)
|
+-- assets/                    <- static design assets (CSS, JS, images)
|   +-- css/style.css          <- all glassmorphic styling (~1,950 lines)
|   +-- js/main.js             <- nav, scroll reveals, typewriter, forms
|   +-- favicon.svg
|   +-- images/                <- static fallback avatar + project covers
|
+-- includes/
|   +-- header.php             <- <head> SEO tags + dynamic nav
|   +-- footer.php             <- dynamic footer nav + scripts
|
+-- sections/                  <- one PHP partial per visible section
|   +-- hero.php
|   +-- about.php
|   +-- skills.php
|   +-- projects.php
|   +-- clients.php
|   +-- reviews.php
|   +-- education.php
|   +-- contact.php
|
+-- uploads/                   <- runtime uploads - MUST be writable (chmod 755)
|   +-- images/                <- hero avatar, project covers, gallery images
|   +-- docs/                  <- CV / resume PDFs (streamed by cv.php)
|   +-- videos/                <- video uploads
|   +-- admins/                <- admin profile pictures
|
+-- admin/                     <- /admin/* gated CMS dashboard
    +-- index.php              <- gatekeeper (login check -> dashboard)
    +-- login.php / logout.php
    +-- dashboard.php          <- KPIs + Chart.js visitor analytics
    +-- hero.php               <- hero copy + avatar + CV upload
    +-- about.php
    +-- skills.php
    +-- projects.php
    +-- gallery.php
    +-- clients.php
    +-- reviews.php
    +-- education.php
    +-- messages.php           <- contact form inbox
    +-- sections.php           <- section toggles + menu CRUD
    +-- settings.php           <- site title, SEO, socials
    +-- files.php              <- file library + active CV flag
    +-- media.php              <- media scanner
    +-- users.php / account.php
    +-- partials/              <- shared admin layout fragments
    +-- api/
        +-- visitors.php       <- JSON analytics endpoint
```
---

## Local development

### Requirements

- PHP **8.1 or newer** with the `pdo_sqlite` (and optionally `pdo_mysql`)
  extension enabled.
- That’s it. No Node, no Composer, no build step.

### Run

```bash
# from the project folder (Anik_SEN/)
php -S 0.0.0.0:5000 router.php
```

Or using the bundled package script:

```bash
cd Anik_SEN
PORT=5000 php -S 0.0.0.0:5000 router.php
```

Open <http://localhost:5000>. The first request creates the SQLite database
and seeds the default admin account.

### Default admin credentials

```
URL:      /admin/
Username: admin
Password: admin1234
```

> **Change the password immediately** from *Admin → Account*.

---

## Database (SQLite default · MySQL optional)

Configuration lives in `portfolio/config/config.php` and is overridable via
environment variables.

### SQLite (default)

Nothing to configure. The DB file is created at
`portfolio/data/portfolio.sqlite` and migrations run automatically.

### MySQL

Set these environment variables (in your hosting panel, `.htaccess`,
systemd unit, or `/etc/php-fpm.d/*.conf`):

```bash
DB_DRIVER=mysql
DB_HOST=localhost
DB_PORT=3306
DB_NAME=anik_portfolio
DB_USER=anik_user
DB_PASSWORD=********
```

Optionally seed the schema using the reference dump:

```bash
mysql -u anik_user -p anik_portfolio < portfolio/sql/schema.sql
```

The migrator is idempotent — it’s safe to run on every boot for both engines.

### Other configurable env vars

| Variable          | Purpose                                  |
|-------------------|------------------------------------------|
| `APP_BASE_URL`    | Public origin (e.g. `https://aniksen.com`) |
| `APP_DEBUG`       | `true` to show PHP errors                |
| `APP_TIMEZONE`    | Default `Asia/Dhaka`                     |
| `ADMIN_USER`      | Default seeded admin username            |
| `ADMIN_PASS`      | Default seeded admin password            |
| `ADMIN_EMAIL`     | Default seeded admin email               |

---

## Admin panel guide

The admin lives at **`/admin/`** and is a frosted-glass dashboard backed by
Tailwind CDN.

### 1. Login

Enter your credentials at `/admin/login.php`. Sessions live for 4 hours.
Wrong password attempts are rate-limited.

### 2. Dashboard

- KPI tiles: visible projects, messages today, total visitors.
- Chart.js visitor chart with **Weekly / Monthly / Yearly** tabs.
- Powered by `/admin/api/visitors.php` — 401 if signed-out, 422 if range is
  invalid, 200 with a clean JSON payload otherwise.

### 3. Hero section (`/admin/hero.php`)

- Edit name, headline, dynamic typing words, intro paragraph and CTA labels.
- Upload **avatar** image (PNG/JPG/WebP). The 3D avatar shipped with this
  release lives at `public/uploads/images/hero-avatar-3d.png`.
- Upload **CV / résumé** as a PDF — the upload is rejected unless both the
  detected MIME type **and** the leading `%PDF` magic bytes match.

### 4. About / Skills / Projects / Gallery / Clients / Reviews / Education

Each module has the same pattern: list view + add / edit / delete buttons.
Drag-handles or sort fields control display order. Image uploads are routed
through `Upload::image()` which validates type, dimensions and file size.

### 5. Sections & Menus (`/admin/sections.php`)

- **Section visibility** — toggle hero, about, skills, projects, gallery,
  clients, reviews, education or contact on/off without editing code.
  `index.php` loops only visible sections.
- **Header menu** — CRUD items with label, target, sort order. Items are
  rendered into `includes/header.php`.
- **Footer menu** — same idea, rendered into `includes/footer.php`.

### 6. Messages (`/admin/messages.php`)

- Paginated inbox of contact-form submissions.
- Read/unread state, full-text search by name or email, hard-delete.

### 7. Settings (`/admin/settings.php`)

- Site title, tagline, default meta description and meta keywords.
- Social links (used by header & footer and as `og:` / Twitter card meta).
- Footer copyright string.

### 8. Files (`/admin/files.php`)

Browse every file under `public/uploads/`, copy URLs and delete unused
assets.

### 9. Users & Account

- `/admin/users.php` — manage administrators.
- `/admin/account.php` — change your own password and profile photo.

### 10. Logout

`/admin/logout.php` invalidates the session and rotates the CSRF token.

---

## Free hosting deployment

The project runs on any commodity PHP host. Below are step-by-step recipes
for the most popular **free** providers.

### Option A — InfinityFree (free, MySQL included)

1. Sign up at <https://infinityfree.net/> and create a hosting account.
2. From the control panel, create a MySQL database and note the
   `DB_HOST`, `DB_NAME`, `DB_USER`, `DB_PASSWORD`.
3. Upload the contents of `portfolio/` via the file manager or FTP, **but
   make `portfolio/` the document root**. The easiest approach is to
   upload everything into `htdocs/portfolio/`, then drop a one-line
   `.htaccess` into `htdocs/`:
   ```apache
   RewriteEngine On
   RewriteRule ^(.*)$ portfolio/$1 [L]
   ```
4. Edit `portfolio/config/config.php` (or set env vars in `.htaccess` with
   `SetEnv`) with your DB credentials and set `DB_DRIVER=mysql`.
5. Visit your domain. The migrator runs and creates all tables.
6. Open `/admin/`, log in with `admin` / `admin1234`, and **change the
   password**.

### Option B — 000webhost (free, MySQL included)

Identical to InfinityFree. The control panel exposes `Website Settings →
Document Root` so you can point straight at `portfolio/` without the
`.htaccess` trick.

### Option C — Replit Deployments (free hobby tier, SQLite)

1. Push the project to a Replit. The included `package.json` script
   `npm run dev` runs the dev server.
2. From the Replit workspace choose **Deploy → Reserved VM / Autoscale**
   and the free hobby tier.
3. The deployment serves on `https://<your-app>.replit.app` and persists the
   SQLite file between deploys.

### Option D — Awardspace / FreeHostia / ProFreeHost

Same flow as InfinityFree. Most free PHP hosts cap PHP memory at 64–128 MB
and disallow CRON; both are fine for this project.

### Free-tier caveats

- Free tiers usually disable `mail()`. The contact form still stores
  messages in the inbox, so admins can read them in `/admin/messages.php`.
- Free SSL is automatic on all four providers above.
- File upload size is often capped at **2 MB**; bump it via a `php.ini`
  upload (most providers allow this) or your hosting panel.

---

## Premium hosting deployment

### Option 1 — Shared hosting (Hostinger / Namecheap / SiteGround / Bluehost)

1. In cPanel, create a MySQL database and a user, and assign all privileges.
2. Upload the **entire project folder contents** via File Manager or SFTP
   **directly into `public_html/`** (or your domain's document root folder).
   `index.php` at the root handles all routing via `.htaccess`.
3. In **Domains → Manage → Document Root**, confirm it points to `public_html/`.
4. In **PHP → Environment Variables** set `DB_DRIVER=mysql`,
   `DB_HOST=localhost`, `DB_NAME=…`, `DB_USER=…`, `DB_PASSWORD=…`,
   `APP_BASE_URL=https://yourdomain.com`.
5. Hit your domain — migrations run and the site is live.
6. Force HTTPS in cPanel and enable HTTP/2 / gzip / Brotli.

### Option 2 — VPS / cloud (DigitalOcean, Linode, Hetzner, AWS Lightsail)

```bash
# Ubuntu 22.04+ example
sudo apt update
sudo apt install -y nginx php8.2-fpm php8.2-cli php8.2-mysql php8.2-sqlite3 \
                    php8.2-mbstring php8.2-xml php8.2-curl mariadb-server certbot \
                    python3-certbot-nginx git

# Clone the repo
sudo mkdir -p /var/www/aniksen
sudo chown -R $USER:www-data /var/www/aniksen
git clone <your-repo-url> /var/www/aniksen
sudo chown -R www-data:www-data /var/www/aniksen/portfolio/data
sudo chown -R www-data:www-data /var/www/aniksen/portfolio/uploads
sudo chmod -R 755 /var/www/aniksen/portfolio/uploads

# Create a MySQL DB
sudo mysql -e "CREATE DATABASE anik_portfolio CHARACTER SET utf8mb4;"
sudo mysql -e "CREATE USER 'anik'@'localhost' IDENTIFIED BY 'STRONG_PASSWORD';"
sudo mysql -e "GRANT ALL ON anik_portfolio.* TO 'anik'@'localhost';"
```

Nginx server block (`/etc/nginx/sites-available/aniksen`):

```nginx
server {
    listen 80;
    server_name aniksen.com www.aniksen.com;
    root /var/www/aniksen/portfolio;
    index index.php;

    # Pretty URLs / front controller
    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    # PHP-FPM
    location ~ \.php$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/run/php/php8.2-fpm.sock;
        fastcgi_param DB_DRIVER   "mysql";
        fastcgi_param DB_HOST     "localhost";
        fastcgi_param DB_NAME     "anik_portfolio";
        fastcgi_param DB_USER     "anik";
        fastcgi_param DB_PASSWORD "STRONG_PASSWORD";
        fastcgi_param APP_BASE_URL "https://aniksen.com";
    }

    # Block direct access to data + classes
    location ~* /(classes|config|data|sql)/ { deny all; return 404; }

    # Long-cache static assets
    location ~* \.(css|js|png|jpe?g|webp|svg|woff2?)$ {
        expires 30d;
        add_header Cache-Control "public, immutable";
    }
}
```

```bash
sudo ln -s /etc/nginx/sites-available/aniksen /etc/nginx/sites-enabled/
sudo nginx -t && sudo systemctl reload nginx
sudo certbot --nginx -d aniksen.com -d www.aniksen.com
```

### Option 3 — Apache + PHP-FPM

Drop this `.htaccess` into `portfolio/`:

```apache
RewriteEngine On
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^ index.php [QSA,L]

<FilesMatch "\.(env|sqlite|sql|md)$">
    Require all denied
</FilesMatch>
```

### Option 4 — Container (Docker)

```dockerfile
FROM php:8.2-apache
RUN docker-php-ext-install pdo_mysql && \
    a2enmod rewrite && \
    sed -ri 's!/var/www/html!/var/www/html/portfolio/public!g' /etc/apache2/sites-available/*.conf
COPY portfolio/ /var/www/html/portfolio/
RUN chown -R www-data:www-data /var/www/html/portfolio/data /var/www/html/portfolio/uploads
EXPOSE 80
```

Build and run:

```bash
docker build -t aniksen-portfolio .
docker run -d -p 80:80 \
  -e DB_DRIVER=mysql -e DB_HOST=db -e DB_NAME=anik \
  -e DB_USER=anik -e DB_PASSWORD=secret \
  -v aniksen_uploads:/var/www/html/portfolio/uploads \
  -v aniksen_db:/var/www/html/portfolio/data \
  aniksen-portfolio
```

---

## SEO & ranking guide

Everything is wired up for fast indexing — you only need to fill in content
and submit the sitemap.

### 1. Per-page meta

`includes/header.php` already emits:

- `<title>` — pulled from *Settings → Site title*.
- `<meta name="description">` — *Settings → Meta description*.
- `<meta name="keywords">` — *Settings → Meta keywords*.
- Canonical URL.
- **Open Graph** (`og:title`, `og:description`, `og:image`, `og:url`).
- **Twitter Card** (`summary_large_image`).
- `application/ld+json` Person / WebSite schema.

Update everything from `/admin/settings.php`.

### 2. robots.txt + sitemap.xml

`public/robots.txt` allows everything except `/admin/` and points at
`/sitemap.xml`. Edit `public/sitemap.xml` whenever you add content (or
re-generate it from the `projects` and `gallery_images` tables).

### 3. Search Console & Bing Webmaster

1. Verify ownership at <https://search.google.com/search-console> using the
   HTML meta tag method (paste it into *Settings → Custom head HTML*, or
   add it directly to `header.php`).
2. Submit `https://yourdomain.com/sitemap.xml`.
3. Request indexing of the home page.
4. Repeat at <https://www.bing.com/webmasters>.

### 4. Performance (Core Web Vitals)

- All hero images are served as already-optimised PNG/WebP. Use
  `cwebp -q 80` for new uploads to halve their size.
- Static assets get a 30-day immutable cache header on Nginx (see snippet).
- Enable HTTP/2 and Brotli on your host.
- Consider a free CDN (Cloudflare proxy mode) — flip the orange cloud on,
  enable *Auto Minify* for HTML/CSS/JS and *Brotli* compression.

### 5. Content tips for ranking

- The **hero h1** is the only `<h1>` on the page — keep it on-keyword
  (e.g. *“Anik Sen — Graphic Designer & Video Editor in Dhaka”*).
- Each **project** has its own title, category and description — write
  unique, descriptive copy (~60–120 words). Avoid duplicate text across
  projects.
- Use real, descriptive **alt text** when uploading images.
- Add a short **About** paragraph that mentions your city, niche and the
  software you use — these are real long-tail queries.
- Collect **reviews** (with client names) — Google rewards
  trust-building content.
- Keep page weight under ~1.5 MB and TTI under 3 s on a Moto G4 throttle.

### 6. Analytics

- Built-in privacy-friendly visitor counter (salted SHA-256 IPs, never
  stored in plaintext) — Dashboard → *Visitors*.
- For richer metrics, add a single line of Google Analytics 4 / Plausible
  / Umami in *Settings → Custom head HTML*.

---

## Troubleshooting

| Symptom                                         | Fix                                                                                  |
|-------------------------------------------------|--------------------------------------------------------------------------------------|
| **Blank page** after deploy                     | Set `APP_DEBUG=true` temporarily and reload to see the PHP error.                    |
| **`PDOException: could not find driver`**       | Install `php-sqlite3` (default) or `php-mysql` and reload PHP-FPM.                   |
| **`SQLSTATE[HY000] [14] unable to open db`**    | `chown www-data:www-data portfolio/data && chmod 775 portfolio/data`.                |
| **Avatar / CV upload fails**                    | Ensure `uploads/images/` and `uploads/docs/` are writable (chmod 755).         |
| **CV upload says “invalid PDF”**                | The file must start with `%PDF-` — that’s a deliberate magic-byte check.             |
| **Admin shows 401 from the visitor API**        | You’re signed out. Log back in.                                                      |
| **Tailwind CDN warning in the console**         | Expected — the warning only fires for the admin-only Tailwind CDN script.            |
| **`Mixed content` after switching to HTTPS**    | Set `APP_BASE_URL=https://yourdomain.com`.                                           |
| **Menus or sections didn’t update**             | Clear your browser cache or hard-reload — Nginx caches static HTML for 30 days.      |
| **MySQL migrations fail on shared hosting**     | Increase `max_allowed_packet` and ensure your user has `CREATE` + `ALTER` privileges.|

---

## Security checklist

Before going live:

- [ ] Change the default admin password.
- [ ] Set a strong `ADMIN_PASS` env var so the seeded user is rotated.
- [ ] Set `APP_DEBUG=false` (or unset).
- [ ] Force HTTPS at the host level.
- [ ] Deny direct HTTP access to `/classes/`, `/config/`, `/data/`, `/sql/`.
- [ ] Back up `portfolio/data/portfolio.sqlite` (or your MySQL dump) on a
      schedule.
- [ ] Keep PHP patched (`apt upgrade php8.2-*`).

---

## Credits

- **Site owner:** Anik Sen — *Graphic Designer & Video Editor*
- **Design, engineering & maintenance:** **Aryaan Dhar Badhon**
- **3D hero artwork:** generated specifically for this project, ships under
  `public/uploads/images/hero-avatar-3d.png`.

If you fork or reuse this CMS, please keep the **“Built by Aryaan Dhar
Badhon”** credit in the footer.

---

© Anik Sen. All rights reserved.
#   A n i k - S e n - P o r t f o l i o  
 