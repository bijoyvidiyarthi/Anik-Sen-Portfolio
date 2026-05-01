# Anik Sen — Portfolio CMS 🚀

A premium, glassmorphic, fully responsive single-page portfolio for **Anik Sen** (Graphic Designer & Video Editor). This project features a custom-built PHP CMS, an integrated admin dashboard, and flexible database support (SQLite/MySQL).

> **Developer / Maintainer:** [Aryaan Dhar Badhon](https://github.com/your-username)

---

## 📖 Table of Contents
1. [Live Preview](#-live-preview)
2. [Highlights](#-highlights)
3. [Tech Stack](#-tech-stack)
4. [Project Structure](#-project-structure)
5. [Local Development](#-local-development)
6. [Database Configuration](#-database-configuration)
7. [Admin Panel Guide](#-admin-panel-guide)
8. [Deployment Options](#-deployment-options)
9. [SEO & Optimization](#-seo--optimization)
10. [Troubleshooting](#-troubleshooting)
11. [Security Checklist](#-security-checklist)

---

## 🖼️ Live Preview
The hero section features a hand-rendered 3D creative workspace. The UI utilizes smooth animations, a pulsing glow halo, and a layout that adapts seamlessly to large displays.

---

## ✨ Highlights
* **Modern Glassmorphic UI:** Frosted cards, soft gradients, and subtle motion using CSS `@keyframes`.
* **Comprehensive CMS:** Every element—text, images, skills, and reviews—is editable via the `/admin/` dashboard.
* **Section Toggles:** Easily show/hide portfolio sections (Hero, Gallery, Clients, etc.) without touching code.
* **Built-in Analytics:** Privacy-focused unique visitor tracking with SHA-256 IP hashing and Chart.js visualization.
* **Secure File Management:** Strict PDF magic-byte validation and MIME checking for CV uploads.
* **Zero-Config Persistence:** Runs on SQLite by default; switch to MySQL with a single environment variable.

---

## 🛠️ Tech Stack
| Layer | Technology |
| :--- | :--- |
| **Language** | PHP 8.1+ (Object-Oriented) |
| **Database** | SQLite (Default) / MySQL 5.7+ / MariaDB 10+ |
| **Frontend** | HTML5, CSS3 (Custom), Vanilla JavaScript |
| **Admin UI** | Tailwind CSS (CDN-based for dashboard) |
| **Charts** | Chart.js |
| **Server** | Apache / Nginx / PHP Built-in Server |

*No heavy JS frameworks or Composer dependencies. Includes a custom PSR-style autoloader.*

---

## 📂 Project Structure
> **Deployment Tip:** Upload the contents of this folder directly to your root `public_html/`. `index.php` acts as the front controller.

```text
root/
├── admin/              # Gated CMS Dashboard & Logic
├── assets/             # CSS (Glassmorphic), JS, and Favicons
├── classes/            # OOP Domain Layer (App Namespace)
├── config/             # Environment-driven configuration
├── data/               # SQLite Database storage
├── includes/           # Reusable UI fragments (Header/Footer)
├── sections/           # Individual Portfolio sections (PHP Partials)
├── uploads/            # Dynamic media (Images, PDFs, Videos)
├── .htaccess           # Apache routing & security
├── bootstrap.php       # Autoloader & App Initialization
└── index.php           # Main Web Entry Point

Here’s your fully cleaned and properly structured `README.md`. You can copy-paste this directly:

````markdown
# 🚀 Local Development

## 📋 Requirements
- PHP 8.1 or newer  
- `pdo_sqlite` or `pdo_mysql` extension enabled  

## ⚡ Quick Start
```bash
# Start the built-in PHP server
php -S localhost:5000 router.php
````

Visit: [http://localhost:5000](http://localhost:5000)

> The first request automatically initializes the SQLite database.

---

## 🔐 Default Admin Credentials

* **URL:** `/admin/`
* **User:** `admin`
* **Pass:** `admin1234`

> ⚠️ Change these immediately in the Account settings!

---

# 💾 Database Configuration

## 🟢 SQLite (Standard)

Works out-of-the-box.
The database file is located at: `data/portfolio.sqlite`.

## 🔵 MySQL (Production)

Set the following environment variables in your server configuration or `.htaccess`:

```env
DB_DRIVER=mysql
DB_HOST=localhost
DB_NAME=your_db_name
DB_USER=your_db_user
DB_PASSWORD=your_password
```

---

# 🛡️ Admin Panel Guide

* **Dashboard:** Real-time KPIs and visitor analytics (Weekly/Monthly/Yearly)
* **Hero Section:** Manage typewriter text, avatar images, and CV PDF uploads
* **Content Modules:** CRUD operations for Skills, Projects, Gallery, and Reviews
* **Messages:** Built-in inbox for contact form submissions with search functionality
* **Settings:** Global SEO management, Social Links, and Metadata control

---

# 🌐 Deployment Options

## 📦 Shared Hosting (cPanel)

1. Upload all files to `public_html/`
2. Create a MySQL database and user
3. Set environment variables in cPanel or update `config/config.php`

## 🖥️ VPS (Ubuntu/Nginx)

Ensure directory permissions are correct for the web user:

```bash
sudo chown -R www-data:www-data uploads/ data/
sudo chmod -R 755 uploads/
```

---

# 📈 SEO & Optimization

* **Meta Tags:** Automated Open Graph and Twitter Card generation
* **Structured Data:** Includes JSON-LD for Person and WebSite schemas
* **Performance:** Optimized images, lazy loading, and minimal external requests
* **Analytics:** Integrated privacy-first tracker + Custom Head HTML slot for GA4

---

# 🔧 Troubleshooting

| Symptom           | Solution                                                   |
| ----------------- | ---------------------------------------------------------- |
| Blank Page        | Set `APP_DEBUG=true` in config to see PHP errors           |
| PDO Driver Error  | Ensure `php-sqlite3` or `php-mysql` is installed           |
| Permission Denied | Verify the `data/` and `uploads/` folders are writable     |
| Invalid PDF Error | Ensure the file is a true PDF (starts with `%PDF-` header) |

---

# 🔒 Security Checklist

* [ ] Rotate the default `admin1234` password
* [ ] Set `APP_DEBUG=false` in production
* [ ] Ensure SSL (HTTPS) is active
* [ ] Verify `.htaccess` blocks access to `/classes/` and `/config/`

---

# 💳 Credits

* **Site Owner:** Anik Sen
* **Developer:** Aryaan Dhar Badhon

© Aryaan Dhar Badhon. All rights reserved.

```
```
