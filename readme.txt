================================================================================
ANIK SEN — PORTFOLIO CMS (PHP 8.2 + SQLite / MySQL)
COMPLETE INSTRUCTION GUIDE & DEPLOYMENT MANUAL
Last updated: 2026-05-01
================================================================================

A self-contained, CMS-driven personal portfolio. Entirely server-rendered PHP
(no build step, no Composer, no Node required in production), with an admin
dashboard for editing every section without touching code. Ships with SQLite by
default (zero-config) and supports MySQL for shared/cPanel production hosts.

================================================================================
TABLE OF CONTENTS
================================================================================

  0.  Quick Start (Local / Replit Development)
  1.  Project Overview & Architecture
  2.  Technology Stack
  3.  STRUCTURE A — Full Development File Map  [KEEP] / [REMOVE] labels
  4.  STRUCTURE B — Clean Production File Map  (upload-ready)
  5.  Pre-Upload Checklist
  6.  Deployment Guide
        6A. Shared Hosting — cPanel / DirectAdmin
        6B. VPS / Cloud Server (Nginx + PHP-FPM)
        6C. Free Hosting Options
        6D. Replit Deployment (one-click)
  7.  Database Setup
        7A. SQLite (default, zero-config)
        7B. MySQL / MariaDB
        7C. Updating Credentials for Production
  8.  Uploads Directory — Permissions Guide
  9.  Admin Panel Reference
 10.  Environment Variables Reference
 11.  Post-Deployment Checklist
 12.  Troubleshooting


================================================================================
0. QUICK START (LOCAL / REPLIT DEVELOPMENT)
================================================================================

Requirements: PHP 8.2+. No Composer, no Node, no build step.

  1. Open a terminal in the Anik_SEN/ directory.
  2. Start the dev server:

         php -S 0.0.0.0:5000 router.php

  3. Open http://localhost:5000 in your browser.
  4. Admin URL: http://localhost:5000/admin/
     Default username: admin
     Default password: admin1234
     -> CHANGE THE PASSWORD immediately via /admin/account.php after first login.

The first request auto-creates data/portfolio.sqlite and seeds all demo content.
No manual SQL import needed for SQLite.


================================================================================
1. PROJECT OVERVIEW & ARCHITECTURE
================================================================================

This is a PHP 8.2 OOP portfolio CMS with:
  - Dynamic front-end sections: Hero, About, Skills, Projects, Education,
    Reviews, Clients, Contact
  - Secure admin panel (/admin/) with CSRF protection, session management,
    login/logout, and multi-user support
  - Dual-database support: SQLite (zero-config, file-based) and MySQL
  - File upload manager for images, videos, and PDF documents
  - Media scanner and hierarchical gallery management
  - Visitor analytics API (weekly / monthly / yearly JSON endpoint)
  - Auto-migration system — no manual SQL required for SQLite
  - Contact form with admin inbox (message.php)
  - CV download via /cv.php (streams the active PDF uploaded in admin)
  - All content editable through the admin panel — no code edits needed

REQUEST LIFECYCLE
-----------------
  Browser → public/index.php  (or public/admin/index.php for /admin/)
              ↓
              require ../bootstrap.php
              ↓
              Loads config/config.php → PSR-4 autoloader → starts session
              ↓
              App\Database::init()     (opens PDO; creates SQLite file if missing)
              ↓
              App\Migrator::ensure()   (idempotent schema + seed; runs every request)
              ↓
              Renders sections/*.php   (public) OR admin/*.php


================================================================================
2. TECHNOLOGY STACK
================================================================================

  Backend      : PHP 8.2 (OOP, PSR-4 autoloading, no framework)
  Database     : SQLite 3 (default) OR MySQL 5.7+ / MariaDB 10.3+
  Frontend     : Vanilla HTML5, CSS3 (~1,950 lines), Vanilla JavaScript
  Admin UI     : Tailwind CSS (via CDN), Chart.js (visitor analytics)
  Server (dev) : PHP built-in server with router.php
  Server (prod): Apache 2.4+ with mod_rewrite  OR  Nginx + PHP-FPM
  Security     : CSRF tokens, password_hash (bcrypt), HttpOnly + SameSite cookies,
                 salted SHA-256 visitor tracking, MIME-validated uploads


================================================================================
3. STRUCTURE A — FULL DEVELOPMENT FILE MAP
================================================================================

  KEY:
    [KEEP]   = Required in production. Must be uploaded to the live server.
    [REMOVE] = Development / Replit-specific artifact. Delete before deploying.

  Anik_SEN/
  │
  ├── attached_assets/                              [REMOVE]
  │   │   (AI-generated prompt images and dev notes — not part of the app)
  │   ├── Gemini_Generated_Image_*.png              [REMOVE]
  │   ├── Pasted-*.txt                              [REMOVE]
  │   └── screenshots/                              [REMOVE]
  │       ├── *_pike_replit_dev_admin.png           [REMOVE]
  │       └── *_pike_replit_dev.png                 [REMOVE]
  │
  ├── classes/                                      [KEEP]
  │   │   (Core OOP layer — App\* namespace, PSR-4 autoloaded from bootstrap.php)
  │   ├── About.php                                 [KEEP]
  │   ├── Auth.php                                  [KEEP]   (login, logout, bcrypt)
  │   ├── Client.php                                [KEEP]   (trusted-clients marquee)
  │   ├── Csrf.php                                  [KEEP]   (per-session CSRF tokens)
  │   ├── Database.php                              [KEEP]   (PDO singleton, sqlite|mysql)
  │   ├── Education.php                             [KEEP]   (timeline items)
  │   ├── FileLibrary.php                           [KEEP]   (file manager + active CV flag)
  │   ├── GalleryCategory.php                       [KEEP]   (hierarchical gallery)
  │   ├── GalleryImage.php                          [KEEP]
  │   ├── Hero.php                                  [KEEP]   (hero section content model)
  │   ├── MediaScanner.php                          [KEEP]   (scans uploads/ for media)
  │   ├── MenuItem.php                              [KEEP]   (header/footer menu CRUD)
  │   ├── Message.php                               [KEEP]   (contact-form inbox)
  │   ├── Migrator.php                              [KEEP]   (idempotent schema + seed)
  │   ├── Project.php                               [KEEP]   (portfolio projects)
  │   ├── ProjectImage.php                          [KEEP]   (per-project gallery images)
  │   ├── Review.php                                [KEEP]   (testimonial cards)
  │   ├── Settings.php                              [KEEP]   (site identity, socials)
  │   ├── SiteSection.php                           [KEEP]   (section ON/OFF toggles)
  │   ├── Skill.php                                 [KEEP]   (creative skill tags)
  │   ├── Software.php                              [KEEP]   (software tile chips)
  │   ├── Upload.php                                [KEEP]   (file upload validator)
  │   └── Visitor.php                               [KEEP]   (anonymous visitor tracking)
  │
  ├── config/
  │   └── config.php                                [KEEP]   (update DB credentials here)
  │
  ├── data/
  │   └── portfolio.sqlite                          [KEEP]   (keep if using SQLite driver)
  │                                                           (skip if switching to MySQL)
  │
  ├── docs/                                         [REMOVE]
  │   └── screenshots/                              [REMOVE]  (documentation images only)
  │       └── hero-avatar.png                       [REMOVE]
  │
  ├── public/                                       [KEEP]   (point web root HERE)
  │   │
  │   ├── admin/                                    [KEEP]
  │   │   ├── api/
  │   │   │   └── visitors.php                      [KEEP]   (JSON analytics endpoint)
  │   │   ├── partials/
  │   │   │   ├── layout.php                        [KEEP]
  │   │   │   └── sidebar.php                       [KEEP]
  │   │   ├── about.php                             [KEEP]
  │   │   ├── account.php                           [KEEP]
  │   │   ├── clients.php                           [KEEP]
  │   │   ├── dashboard.php                         [KEEP]
  │   │   ├── education.php                         [KEEP]
  │   │   ├── files.php                             [KEEP]
  │   │   ├── gallery.php                           [KEEP]
  │   │   ├── hero.php                              [KEEP]
  │   │   ├── index.php                             [KEEP]
  │   │   ├── login.php                             [KEEP]
  │   │   ├── logout.php                            [KEEP]
  │   │   ├── media.php                             [KEEP]
  │   │   ├── messages.php                          [KEEP]
  │   │   ├── projects.php                          [KEEP]
  │   │   ├── reviews.php                           [KEEP]
  │   │   ├── sections.php                          [KEEP]
  │   │   ├── settings.php                          [KEEP]
  │   │   ├── skills.php                            [KEEP]
  │   │   ├── uitext.php                            [KEEP]
  │   │   └── users.php                             [KEEP]
  │   │
  │   ├── assets/                                   [KEEP]   (static design assets)
  │   │   ├── css/
  │   │   │   └── style.css                         [KEEP]
  │   │   ├── images/                               [KEEP]   (static fallback images)
  │   │   │   ├── avatar.png                        [KEEP]
  │   │   │   ├── hero-avatar.png                   [KEEP]
  │   │   │   ├── project-1.png                     [KEEP]
  │   │   │   ├── project-2.png                     [KEEP]
  │   │   │   ├── project-3.png                     [KEEP]
  │   │   │   ├── project-4.png                     [KEEP]
  │   │   │   ├── project-5.png                     [KEEP]
  │   │   │   ├── project-6.png                     [KEEP]
  │   │   │   ├── project-7.png                     [KEEP]
  │   │   │   └── project-8.png                     [KEEP]
  │   │   ├── js/
  │   │   │   └── main.js                           [KEEP]
  │   │   └── favicon.svg                           [KEEP]
  │   │
  │   ├── includes/                                 [KEEP]
  │   │   ├── footer.php                            [KEEP]
  │   │   └── header.php                            [KEEP]
  │   │
  │   ├── sections/                                 [KEEP]   (rendered by index.php)
  │   │   ├── about.php                             [KEEP]
  │   │   ├── clients.php                           [KEEP]
  │   │   ├── contact.php                           [KEEP]
  │   │   ├── education.php                         [KEEP]
  │   │   ├── hero.php                              [KEEP]
  │   │   ├── projects.php                          [KEEP]
  │   │   ├── reviews.php                           [KEEP]
  │   │   └── skills.php                            [KEEP]
  │   │
  │   ├── uploads/                                  [KEEP]   (MUST be writable — see §8)
  │   │   ├── admins/                               [KEEP]   (admin profile pictures)
  │   │   ├── docs/                                 [KEEP]   (CV PDFs)
  │   │   ├── images/                               [KEEP]   (hero, projects, gallery)
  │   │   └── videos/                               [KEEP]   (video uploads)
  │   │
  │   ├── contact.php                               [KEEP]   (form POST handler)
  │   ├── cv.php                                    [KEEP]   (streams active CV PDF)
  │   ├── health.php                                [KEEP]   (returns {"status":"ok"})
  │   ├── index.php                                 [KEEP]   (public front controller)
  │   ├── opengraph.jpg                             [KEEP]   (OG share image)
  │   └── router.php                               [KEEP*]  (dev server only — see note)
  │
  ├── sql/
  │   └── schema_mysql.sql                          [KEEP]   (import if using MySQL)
  │
  ├── index.php                                     [KEEP]   (PUBLIC front controller — web entry point)
  ├── contact.php                                   [KEEP]   (contact form POST handler)
  ├── cv.php                                        [KEEP]   (streams active CV PDF)
  ├── health.php                                    [KEEP]   (uptime probe endpoint)
  ├── router.php                                   [KEEP*]  (PHP dev server only — not needed in prod)
  ├── opengraph.jpg                                 [KEEP]   (OG share image)
  ├── bootstrap.php                                 [KEEP]   (autoloader + session + DB init)
  ├── .env.example                                  [KEEP]   (copy to .env, fill credentials)
  ├── .htaccess                                     [KEEP]   (Apache URL rewriting — critical)
  ├── .gitattributes                                [REMOVE]  (Git metadata, unneeded on server)
  ├── package.json                                  [REMOVE]  (dev convenience only)
  ├── package-lock.json                             [REMOVE]  (dev convenience only)
  ├── replit.nix                                    [REMOVE]  (Replit environment config)
  ├── replit.md                                     [REMOVE]  (Replit-specific notes)
  ├── README.md                                     [REMOVE]  (developer docs, not needed live)
  └── readme.txt                                    [REMOVE]  (this file — optional)

  * router.php is used ONLY by the PHP built-in dev server command.
    On Apache, URL routing is handled by .htaccess (included).
    On Nginx, routing is handled via the try_files directive in nginx.conf.
    You may keep or remove router.php on production — it causes no harm either way.


================================================================================
4. STRUCTURE B — CLEAN PRODUCTION FILE MAP
================================================================================

  Upload ONLY the following to your live server (after running the
  Pre-Upload Checklist in Section 5).

  IMPORTANT: The entire project folder is uploaded directly to public_html/
  or www/ — index.php now lives at the root level, not inside /public.

  public_html/               <-- upload everything here (this IS your web root)
  │
  ├── index.php              (PUBLIC entry point — must be at this level)
  ├── contact.php
  ├── cv.php
  ├── health.php
  ├── opengraph.jpg
  ├── .htaccess              (URL rewriting — must be at this level)
  │
  ├── bootstrap.php
  ├── .env.example           (rename to .env and fill values on the server)
  │
  ├── classes/               (all 23 .php class files — keep every file)
  │
  ├── config/
  │   └── config.php         (update DB credentials before uploading — see §7C)
  │
  ├── data/
  │   └── portfolio.sqlite   (include only if using SQLite driver)
  │
  ├── sql/
  │   └── schema_mysql.sql   (include only if using MySQL driver)
  │
  ├── assets/                (full folder — css/, js/, images/, favicon.svg)
  ├── includes/              (header.php, footer.php)
  ├── sections/              (all 8 section files)
  │
  ├── uploads/               (directory structure — must be writable)
  │   ├── admins/            (must exist — create if missing)
  │   ├── docs/              (must exist — create if missing)
  │   ├── images/            (must exist — upload your images here)
  │   └── videos/            (must exist — create if missing)
  │
  └── admin/                 (full folder, all files)

  TOTAL FILES TO UPLOAD: ~90 PHP files + CSS + JS + images
  EXCLUDED FROM UPLOAD:  attached_assets/, docs/, .gitattributes,
                         package.json, package-lock.json, replit.nix,
                         replit.md, README.md, readme.txt


================================================================================
5. PRE-UPLOAD CHECKLIST
================================================================================

  Step 1 — Delete these items from your local copy BEFORE zipping:

    [ ] attached_assets/            (all AI prompt images and dev notes)
    [ ] docs/                       (documentation screenshots)
    [ ] .gitattributes              (Git metadata)
    [ ] package.json                (dev convenience script)
    [ ] package-lock.json           (dev lock file)
    [ ] replit.nix                  (Replit environment config)
    [ ] replit.md                   (Replit-specific notes)
    [ ] README.md                   (developer markdown docs)
    [ ] readme.txt                  (this file — optional to keep)

  Step 2 — Confirm these are correctly configured before upload:

    [ ] config/config.php           Updated with correct DB driver and credentials
    [ ] .htaccess                   Present in project root (Apache URL rewriting)
    [ ] public/uploads/             All 4 sub-folders exist: admins/, docs/, images/, videos/
    [ ] data/portfolio.sqlite       Present and intact (if using SQLite)
    [ ] sql/schema_mysql.sql        Present (if using MySQL)
    [ ] .env.example                Renamed to .env with real values (or set via host panel)

  Step 3 — Zip and upload:

    [ ] Zip the cleaned folder
    [ ] Upload via cPanel File Manager (Upload → Extract) or FTP client
    [ ] Set your domain's Document Root to point to the public/ sub-folder
    [ ] Set directory permissions (see Section 8)
    [ ] Visit the site once to trigger auto-migration
    [ ] Log into /admin/ and change the default password


================================================================================
6. DEPLOYMENT GUIDE
================================================================================

────────────────────────────────────────────────────────────────────────────────
6A. SHARED HOSTING — cPanel / DirectAdmin (Hostinger, Namecheap, A2, etc.)
────────────────────────────────────────────────────────────────────────────────

Requirements: PHP 8.0+ (8.2 recommended), MySQL 5.7+ or MariaDB 10.3+.

IMPORTANT: The Document Root (web root) MUST point to the public/ sub-folder.
This keeps classes/, config/, data/, and bootstrap.php hidden from the web.

  1. Buy hosting + domain. In cPanel → MultiPHP Manager → set PHP 8.2.

  2. Create a MySQL database:
       cPanel → MySQL Databases
       - Create database:  aniksen_portfolio
       - Create user:      aniksen_user  (strong password)
       - Add user to DB with ALL PRIVILEGES
       Note: cPanel prepends your account name (e.g. cpanelacc_aniksen_portfolio).

  3. Upload your cleaned project folder to your server, e.g.:
         /home/YOUR_USER/aniksen_app/

  4. Set your domain's Document Root to:
         /home/YOUR_USER/aniksen_app/public
     (In cPanel → Domains → edit document root for your addon domain/subdomain)

  5. Set DB credentials — easiest via .htaccess in the public/ folder:
       Add these lines to public/.htaccess (or set via cPanel env manager):

         SetEnv DB_DRIVER    mysql
         SetEnv DB_HOST      localhost
         SetEnv DB_NAME      cpanelacc_aniksen_portfolio
         SetEnv DB_USER      cpanelacc_aniksen_user
         SetEnv DB_PASSWORD  YourStrongPassword!
         SetEnv ADMIN_USER   admin
         SetEnv ADMIN_PASS   ChangeThisNow!
         SetEnv APP_DEBUG    false

  6. Set write permissions on uploads and data (see Section 8).

  7. Visit https://yourdomain.com — migrator auto-creates tables and seed data.

  8. Login at /admin/ with your set credentials, then change password immediately.

  9. Enable HTTPS (cPanel → SSL/TLS Status → Run AutoSSL), then add to .htaccess:

         RewriteCond %{HTTPS} off
         RewriteRule ^ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]

────────────────────────────────────────────────────────────────────────────────
6B. VPS / CLOUD SERVER (Ubuntu 22.04 + Nginx + PHP-FPM)
────────────────────────────────────────────────────────────────────────────────

  1. Install dependencies:
       sudo apt update && sudo apt install -y nginx php8.2-fpm \
         php8.2-mysql php8.2-sqlite3 php8.2-mbstring php8.2-xml \
         php8.2-curl mysql-server certbot python3-certbot-nginx unzip

  2. Upload the project to /var/www/aniksen/

  3. Set ownership and permissions:
       sudo chown -R www-data:www-data /var/www/aniksen
       sudo find /var/www/aniksen -type d -exec chmod 755 {} \;
       sudo find /var/www/aniksen -type f -exec chmod 644 {} \;
       sudo chmod -R 755 /var/www/aniksen/uploads
       sudo chmod -R 755 /var/www/aniksen/data

  4. Create Nginx server block (/etc/nginx/sites-available/aniksen):

       server {
           listen 80;
           server_name yourdomain.com www.yourdomain.com;
           root /var/www/aniksen;
           index index.php;

           location / {
               try_files $uri $uri/ /index.php?$query_string;
           }
           location ~ \.php$ {
               include snippets/fastcgi-php.conf;
               fastcgi_pass unix:/run/php/php8.2-fpm.sock;
               fastcgi_param DB_DRIVER    mysql;
               fastcgi_param DB_HOST      localhost;
               fastcgi_param DB_NAME      aniksen_portfolio;
               fastcgi_param DB_USER      anik;
               fastcgi_param DB_PASSWORD  StrongPass!;
               fastcgi_param ADMIN_USER   admin;
               fastcgi_param ADMIN_PASS   ChangeThisNow!;
               fastcgi_param APP_DEBUG    false;
           }
           location ~ /\. { deny all; }
           client_max_body_size 25M;
       }

  5. Enable and reload:
       sudo ln -s /etc/nginx/sites-available/aniksen /etc/nginx/sites-enabled/
       sudo nginx -t && sudo systemctl reload nginx

  6. Free HTTPS via Let's Encrypt:
       sudo certbot --nginx -d yourdomain.com -d www.yourdomain.com

  7. Visit the site → log into /admin/ → change password.

────────────────────────────────────────────────────────────────────────────────
6C. FREE HOSTING — INFINITYFREE (DETAILED STEP-BY-STEP)
────────────────────────────────────────────────────────────────────────────────

  InfinityFree URL : https://infinityfree.com
  What you get     : Free PHP 8.x + MySQL hosting, free subdomain (*.epizy.com
                     or *.rf.gd), 5 GB storage, unlimited bandwidth.
  What you DON'T get: SQLite support, SSH access, SetEnv in .htaccess.

  ── BEFORE YOU UPLOAD ──────────────────────────────────────────────────────

  STEP 1 — Decide what files to upload
    Upload ONLY these files/folders to the server (skip the rest):
      admin/
      assets/
      classes/
      config/
      includes/
      sections/
      sql/
      uploads/
      .env            ← you create this (see Step 4)
      .htaccess
      bootstrap.php
      contact.php
      cv.php
      health.php
      index.php
      opengraph.jpg

    DO NOT upload:
      .git/           (version control history — security risk)
      .agents/        (Replit internal)
      .local/         (Replit internal)
      .cache/         (Replit cache)
      .replit         (Replit config)
      replit.nix      (Replit config)
      router.php      (only needed for PHP's built-in dev server)
      test.php        (temporary diagnostic file — delete it)
      data/           (do NOT upload — MySQL replaces SQLite entirely)
      zipFile.zip     (source archive — not needed)
      readme.txt      (dev documentation — not needed on server)
      README.md       (dev documentation — not needed on server)
      .env.example    (template file — not needed on server)

  ── STEP 2: SIGN UP & CREATE YOUR ACCOUNT ──────────────────────────────────

    1. Go to https://infinityfree.com and click "Get Free Hosting".
    2. Create an account with your email and verify it.
    3. In the Client Area, click "New Account".
    4. Choose a free subdomain (e.g. aniksen → aniksen.epizy.com) OR
       enter a domain you own. Click "Create Account".
    5. Wait 1–2 minutes for the account to activate.

  ── STEP 3: CREATE YOUR MYSQL DATABASE ─────────────────────────────────────

    1. In the InfinityFree Client Area, click your hosting account.
    2. Click "MySQL Databases" (or go to Control Panel → MySQL Databases).
    3. Under "Create a Database", type a name (e.g. portfolio) → click Create.
       InfinityFree will prefix it with your account ID automatically.
       Example result: epiz_12345678_portfolio
    4. Write down all four values — you will need them in Step 4:
         Database Host : shown on the MySQL Databases page (e.g. sql200.infinityfree.com)
         Database Name : e.g. epiz_12345678_portfolio
         Database User : same as Database Name  (e.g. epiz_12345678_portfolio)
         Database Pass : the password you set when creating the database

  ── STEP 4: CREATE YOUR .env FILE ──────────────────────────────────────────

    On your computer (NOT on the server), open a plain text editor (Notepad,
    VS Code, etc.) and create a new file called exactly:  .env
    (dot-env, no other extension)

    Paste the following content and fill in YOUR values from Step 3:

    ─────────────────────────────────────────────────────────────────────────
    APP_BASE_URL=
    APP_DEBUG=false
    APP_TIMEZONE=Asia/Dhaka

    DB_DRIVER=mysql
    DB_HOST=sql200.infinityfree.com
    DB_PORT=3306
    DB_NAME=epiz_12345678_portfolio
    DB_USER=epiz_12345678_portfolio
    DB_PASSWORD=YourDatabasePasswordHere

    ADMIN_USER=admin
    ADMIN_PASS=ChangeThisNow2024!
    ADMIN_EMAIL=your@email.com
    ─────────────────────────────────────────────────────────────────────────

    Replace:
      sql200.infinityfree.com → your actual DB host from Step 3
      epiz_12345678_portfolio → your actual database name from Step 3
      YourDatabasePasswordHere → your actual database password
      ChangeThisNow2024! → a strong admin password of your choice
      your@email.com → your real email address

    IMPORTANT: APP_TIMEZONE — change to your local timezone if needed.
    Full list: https://www.php.net/manual/en/timezones.php
    (Examples: America/New_York, Europe/London, Asia/Kolkata)

    Save the file as .env (if your editor adds .txt, rename it to remove it).

  ── STEP 5: UPLOAD YOUR FILES ───────────────────────────────────────────────

    METHOD A — File Manager (easier, no FTP software needed):
      1. In your InfinityFree Control Panel → click "Online File Manager".
      2. Open the htdocs/ folder (this is your web root).
      3. Delete the default "index2.html" file if it exists.
      4. Upload all your project files (from Step 1's "Upload these" list)
         directly into htdocs/.
         IMPORTANT: Make sure the structure inside htdocs/ looks like:
           htdocs/
             admin/
             assets/
             classes/
             config/
             includes/
             sections/
             sql/
             uploads/
             .env
             .htaccess
             bootstrap.php
             index.php
             ... (all other PHP files)

      NOTE: Some file managers hide files starting with a dot (.env, .htaccess).
      Look for a "Show Hidden Files" toggle or upload them via FTP (Method B).

    METHOD B — FTP (recommended for uploading hidden files like .env):
      1. In Control Panel → FTP Accounts → note your FTP host, username, password.
      2. Download FileZilla (free): https://filezilla-project.org
      3. Open FileZilla → File → Site Manager → New Site:
           Host     : your FTP host (e.g. ftpupload.net)
           Protocol : FTP
           Logon    : Normal
           User     : your FTP username
           Password : your FTP password
      4. Connect → navigate to htdocs/ on the right panel.
      5. Drag and drop all project files from Step 1 into htdocs/.

  ── STEP 6: SET FOLDER PERMISSIONS ─────────────────────────────────────────

    The uploads/ sub-folders must be writable so the admin panel can save images.

    In the File Manager:
      1. Right-click the uploads/ folder → "Change Permissions" → set to 755.
      2. Repeat for each sub-folder inside uploads/:
           uploads/admins/   → 755
           uploads/docs/     → 755
           uploads/images/   → 755
           uploads/videos/   → 755

    If the File Manager doesn't show a permissions option, use FTP:
      In FileZilla, right-click each folder → "File Permissions" → type 755.

  ── STEP 7: FIRST PAGE LOAD (AUTO-SETUP) ────────────────────────────────────

    1. Open your browser and visit your site:
         http://youraccount.epizy.com   (or your custom domain)
    2. The app will:
       a. Read your .env file and connect to MySQL.
       b. Automatically create ALL database tables on first load.
       c. Seed your admin account with the credentials from your .env file.
    3. If you see your portfolio homepage — everything worked!
    4. If you see a blank page or error, see Troubleshooting at the end of
       this file. Temporarily set APP_DEBUG=true in your .env, re-upload it,
       and reload the page to see the exact error message.

  ── STEP 8: LOG IN TO THE ADMIN PANEL ───────────────────────────────────────

    1. Visit: http://youraccount.epizy.com/admin/
    2. Log in with the ADMIN_USER and ADMIN_PASS you set in your .env file.
    3. IMMEDIATELY go to Admin → Account and change your password.
    4. After changing password, update ADMIN_PASS in your .env to match
       (not strictly required after first boot, but good practice).

  ── STEP 9: CUSTOMISE YOUR PORTFOLIO ────────────────────────────────────────

    Now that you're logged in, fill in your real content:
      Admin → Hero         : Your name, title, avatar image, stats
      Admin → About        : Your bio and expertise cards
      Admin → Skills       : Add your creative and software skills
      Admin → Projects     : Upload your portfolio work
      Admin → Education    : Your degrees / courses / experience
      Admin → Reviews      : Client testimonials
      Admin → Settings     : Site name, email, social links

  ── STEP 10: CONNECT A CUSTOM DOMAIN (OPTIONAL) ─────────────────────────────

    If you own a domain (e.g. aniksen.com from Namecheap / GoDaddy):
      1. In InfinityFree Client Area → Addon Domains → Add your domain.
      2. In your domain registrar's DNS settings → point the nameservers to:
           ns1.byet.org
           ns2.byet.org
           ns3.byet.org
           ns4.byet.org
           ns5.byet.org
         OR add an A record pointing to InfinityFree's IP (shown in the panel).
      3. Wait 24–48 hours for DNS to propagate.
      4. Update APP_BASE_URL in your .env to your domain (e.g. https://aniksen.com).

  ── INFINITYFREE KNOWN LIMITATIONS ──────────────────────────────────────────

    - No SQLite: data/ folder is read-only. Always use MySQL (covered above).
    - No SSH: use File Manager or FTP to manage files.
    - No SetEnv in .htaccess: use the .env file method above instead.
    - CPU limits: 50,000 hits/day before temporary throttle. Fine for a portfolio.
    - PHP mail() may be blocked: contact form saves to the admin inbox but
      email delivery may not work. Use admin → messages.php to read submissions.
    - Free plan shows no ads (unlike some other free hosts) but has an inode limit.

  Option C2 — Render.com (free web service with Docker)
    1. Push project to GitHub
    2. Render → New Web Service → connect repo → Environment: Docker
    3. Add Dockerfile to project root:
         FROM php:8.2-cli
         RUN docker-php-ext-install pdo pdo_mysql
         COPY . /app
         WORKDIR /app
         CMD ["php", "-S", "0.0.0.0:10000", "-t", "public", "public/router.php"]
    4. Set env var PORT=10000
    5. Deploy → free *.onrender.com HTTPS URL

  Option C3 — Railway / Fly.io
    Same as Render: push to GitHub, connect repo, use the Dockerfile above.

  NOT SUPPORTED: Netlify / Vercel / GitHub Pages
    These are static-only hosts. PHP will NOT execute. The admin panel and
    contact form will not work. This CMS cannot be deployed there.

────────────────────────────────────────────────────────────────────────────────
6D. REPLIT DEPLOYMENT (one-click, easiest)
────────────────────────────────────────────────────────────────────────────────

  1. Open the Deployments tab in this Replit project.
  2. Choose "Reserved VM" or "Autoscale".
  3. Run command:
       cd Anik_SEN && php -S 0.0.0.0:$PORT -t public public/router.php
  4. Click Publish → get a free *.replit.app subdomain with HTTPS.
  5. (Optional) Attach a custom domain from Deployments → Custom Domain.


================================================================================
7. DATABASE SETUP
================================================================================

────────────────────────────────────────────────────────────────────────────────
7A. SQLite (Default — Recommended for Shared Hosting & Quick Setup)
────────────────────────────────────────────────────────────────────────────────

  - No setup required. Database auto-created at data/portfolio.sqlite.
  - Tables and seed data are auto-migrated on the very first page load.
  - The data/ directory MUST have write permission (chmod 755 or 777).
  - Use SQLite for low to moderate traffic (< 1,000 daily visitors is fine).
  - For high concurrency, switch to MySQL to avoid "Database is locked" errors.

────────────────────────────────────────────────────────────────────────────────
7B. MySQL / MariaDB
────────────────────────────────────────────────────────────────────────────────

  Via cPanel:
    cPanel → MySQL Databases → Create DB + user → grant ALL PRIVILEGES

  Via SSH / CLI:
    mysql -u root -p
    > CREATE DATABASE anik_portfolio CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
    > CREATE USER 'anik'@'localhost' IDENTIFIED BY 'StrongPassword!';
    > GRANT ALL PRIVILEGES ON anik_portfolio.* TO 'anik'@'localhost';
    > FLUSH PRIVILEGES; EXIT;

  Then EITHER:
    (a) Import the schema manually:
          mysql -u anik -p anik_portfolio < sql/schema_mysql.sql
    (b) Let the auto-migrator create everything on first page load (no import needed).

────────────────────────────────────────────────────────────────────────────────
7C. Updating Credentials for Production
────────────────────────────────────────────────────────────────────────────────

  Open config/config.php and update the relevant sections:

  For SQLite (change only if the file lives at a non-default path):
    "sqlite" => [
        "path" => "/absolute/path/to/data/portfolio.sqlite",
    ],

  For MySQL — change the driver AND fill in your credentials:
    "driver" => "mysql",            // was "sqlite" — change this line

    "mysql" => [
        "host"     => "localhost",              // your database host
        "port"     => "3306",                   // usually 3306
        "name"     => "aniksen_portfolio",      // your DB name
        "user"     => "aniksen_user",           // your DB username
        "password" => "YourStrongPassword!",    // your DB password
        "charset"  => "utf8mb4",
    ],

  Default admin credentials (seeded once on first boot):
    "default_username" => "admin",
    "default_password" => "admin1234",   // CHANGE THIS after first login!

  RECOMMENDED ALTERNATIVE: Set all values as environment variables in your
  hosting control panel. The application reads these automatically:
    DB_DRIVER, DB_HOST, DB_PORT, DB_NAME, DB_USER, DB_PASSWORD,
    ADMIN_USER, ADMIN_PASS, ADMIN_EMAIL, APP_DEBUG, APP_BASE_URL


================================================================================
8. UPLOADS DIRECTORY — PERMISSIONS GUIDE
================================================================================

  These directories MUST be writable by the web server process.
  Start with 755. If uploads still fail, try 775, then 777.

  Directories requiring write permission:
    public/uploads/            (parent directory)
    public/uploads/admins/     (admin profile pictures)
    public/uploads/docs/       (CV PDFs — streamed by cv.php)
    public/uploads/images/     (hero avatar, project covers, gallery images)
    public/uploads/videos/     (video uploads)
    data/                      (SQLite database file — write required)

  Via cPanel File Manager:
    1. Select folder → Right-click → Change Permissions
    2. Set to 755. If file uploads fail, try 775 or 777.

  Via SSH:
    chmod 755 uploads \
              uploads/admins \
              uploads/docs \
              uploads/images \
              uploads/videos \
              data/

  Or recursively:
    find uploads -type d -exec chmod 755 {} \;
    chmod 755 data/

  Also confirm in php.ini (or via cPanel PHP settings):
    upload_max_filesize = 25M
    post_max_size       = 30M

  SECURITY NOTE:
    - NEVER set PHP class files or config.php to 777.
    - NEVER set the SQLite file itself to 777 — the data/ directory needs 755,
      and the sqlite file inside needs 644 (the server will handle writes).
    - Block directory listing: ensure your .htaccess includes "Options -Indexes"
      (already included in the project's .htaccess).


================================================================================
9. ADMIN PANEL REFERENCE
================================================================================

  URL:      https://yourdomain.com/admin/
  Default:  username: admin  /  password: admin1234
  IMPORTANT: Change the password immediately via /admin/account.php

  ┌──────────────────────┬──────────────────────────────────────────────────────┐
  │ Admin Page           │ Purpose                                              │
  ├──────────────────────┼──────────────────────────────────────────────────────┤
  │ dashboard.php        │ Stats overview, visitor analytics chart, quick links │
  │ hero.php             │ Hero section: name, title, subtitle, avatar image    │
  │ about.php            │ About section: bio text, expertise cards, stats      │
  │ skills.php           │ Creative skill tags + software tool tile chips       │
  │ projects.php         │ Portfolio projects, images, video URL, category      │
  │ education.php        │ Education and experience timeline items              │
  │ reviews.php          │ Client testimonial cards (add/edit/delete/reorder)   │
  │ clients.php          │ Trusted-client logo marquee (logo upload or URL)     │
  │ gallery.php          │ Hierarchical image gallery with category tree        │
  │ files.php            │ File library + set which PDF is the active CV        │
  │ media.php            │ Auto-scan uploads/ and import media to gallery       │
  │ messages.php         │ Contact-form inbox (read/unread, mailto reply)       │
  │ sections.php         │ Toggle site sections on/off + menu item CRUD         │
  │ uitext.php           │ Edit all UI text labels and strings                  │
  │ settings.php         │ Site name, email, phone, social links, footer text   │
  │ account.php          │ Change your admin username, email, and password      │
  │ users.php            │ Manage multiple admin user accounts                  │
  │ api/visitors.php     │ JSON endpoint: weekly / monthly / yearly analytics   │
  └──────────────────────┴──────────────────────────────────────────────────────┘


================================================================================
10. ENVIRONMENT VARIABLES REFERENCE
================================================================================

  Variable           Default Value                  Description
  ─────────────────  ─────────────────────────────  ───────────────────────────
  APP_BASE_URL       (empty)                        Full base URL if behind proxy
  APP_DEBUG          false                          true only in development
  APP_TIMEZONE       Asia/Dhaka                     PHP timezone string
  DB_DRIVER          sqlite                         "sqlite" or "mysql"
  DB_SQLITE_PATH     ./data/portfolio.sqlite        Absolute path to SQLite file
  DB_HOST            localhost                      MySQL host
  DB_PORT            3306                           MySQL port
  DB_NAME            anik_portfolio                 MySQL database name
  DB_USER            root                           MySQL username
  DB_PASSWORD        (empty)                        MySQL password
  ADMIN_USER         admin                          Default admin (first boot only)
  ADMIN_PASS         admin1234                      Default password (first boot only)
  ADMIN_EMAIL        admin@aniksen.local            Default email (first boot only)

  How to set them:
    - Copy .env.example to .env and fill in your values, OR
    - Set directly in cPanel's environment variables manager, OR
    - Add SetEnv lines to your .htaccess file (Apache), OR
    - Add fastcgi_param entries to your nginx.conf (Nginx)


================================================================================
11. POST-DEPLOYMENT CHECKLIST
================================================================================

  [ ] Site loads at https://yourdomain.com without errors
  [ ] Admin panel accessible at https://yourdomain.com/admin/
  [ ] Successfully logged in with configured credentials
  [ ] Admin password changed from default (account.php)
  [ ] APP_DEBUG is set to false
  [ ] Hero section avatar image displays correctly
  [ ] Project images display correctly
  [ ] Contact form sends a message (check admin → messages.php)
  [ ] File upload works (upload a test image via admin → files.php)
  [ ] /cv.php streams a PDF (upload a CV via admin → files.php → set as active)
  [ ] /health.php returns {"status":"ok"}
  [ ] public/uploads/ sub-folders have correct write permissions
  [ ] data/ directory is writable (SQLite) or MySQL connection is confirmed
  [ ] .htaccess is present and URL rewriting works (no 404 errors on /admin/)
  [ ] HTTPS is active and http:// redirects to https://
  [ ] OG image (public/opengraph.jpg) replaced with your branded image
  [ ] Favicon (public/assets/favicon.svg) updated with your brand


================================================================================
12. TROUBLESHOOTING
================================================================================

  500 error / blank white page on first load
    → Set APP_DEBUG=true temporarily, refresh, read the error, then turn it off.

  Admin login keeps redirecting back to login page
    → Session cookies are being blocked. Ensure HTTPS is active (cookies use
      SameSite=Lax). Check that your host writes PHP sessions (check session
      save path in phpinfo() and ensure it is writable).

  Uploads fail — "Failed to move uploaded file"
    → chmod 775 on public/uploads/ and all 4 sub-folders (admins/, docs/,
      images/, videos/). Also check php.ini: upload_max_filesize and
      post_max_size must be >= 25M.

  "Database is locked" errors under traffic (SQLite)
    → Switch to MySQL using the env vars in Section 7B. SQLite suits low to
      moderate traffic; high concurrency requires MySQL.

  Videos in project modal show "No video URL set"
    → Admin → projects.php → edit the project → paste a YouTube, Vimeo,
      or direct .mp4 URL into the Video URL field.

  404 errors on /admin/ or clean URLs
    → .htaccess is missing or mod_rewrite is not enabled. On Apache, run:
         sudo a2enmod rewrite && sudo systemctl restart apache2
      On Nginx, ensure your server block has the try_files directive (see §6B).

  Emails from contact form never arrive
    → Most shared hosts disable PHP mail() by default. Configure SMTP via your
      host's Email panel, or integrate a transactional service (SendGrid,
      Mailgun, Resend) inside classes/Message.php.

  "Class not found" errors
    → bootstrap.php cannot find your classes/ folder. Verify the path in
      bootstrap.php's spl_autoload_register matches your server directory layout.
      Paths use __DIR__ which is always the folder containing bootstrap.php.

  Visitor analytics chart is empty
    → The /admin/api/visitors.php endpoint requires an authenticated session.
      Log in to /admin/ first. The chart loads data via fetch() after login.


================================================================================
DOMAIN NAME SUGGESTIONS FOR ANIK SEN
================================================================================

  Personal brand (cleanest):
    aniksen.com    aniksen.co     aniksen.me
    aniksen.design aniksen.studio aniksen.media

  Role-anchored:
    aniksencreates.com   aniksenvisuals.com   aniksenedits.com
    aniksen.gallery      aniksen.video        aniksen.art

  Recommendation:
    Primary: aniksen.com         (long-term personal brand)
    Backup:  aniksen.design      (creative-industry signal)
    Redirect both to whichever you choose as primary.


================================================================================
SECURITY BUILT INTO THE APP
================================================================================

  - Sessions: HttpOnly, SameSite=Lax, regenerated on every login
  - Passwords: hashed with password_hash() using bcrypt
  - All admin writes: protected by per-session CSRF tokens
  - Uploads: extension + MIME type validated, filenames sanitized + randomized
  - Visitor IPs: salted SHA-256 — never stored in plaintext
  - Config files: live outside the public web root by design
  - SQLite database: lives in data/ (above public/) — not web-accessible

================================================================================
Anik Sen Portfolio CMS — PHP 8.2 OOP | SQLite / MySQL | No Framework
================================================================================
