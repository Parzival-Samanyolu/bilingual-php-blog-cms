# Bilingual PHP Blog & Encyclopedia CMS

A lightweight, framework‑free **bilingual (TR/EN) blog / knowledge‑base CMS** built with plain
PHP 8 and MySQL. No Composer, no build step, no JavaScript framework — just upload it to any
PHP host (it is designed for shared cPanel/LiteSpeed hosting) and go.

Use this repository as a **template** for content sites, wikis, encyclopedias or multilingual
blogs.

---

## Features

- **Bilingual content** — every article, category and tag has TR + EN translations, with a
  language switcher and `hreflang` alternates. Turkish routes (`/yazi`, `/kategori`, `/ara`)
  and mirrored English routes (`/article`, `/category`, `/search`).
- **Roles & workflow** — `admin`, `author` (requires approval) and `reader`. Draft → pending →
  published article review flow, plus comment moderation and author‑approval flow.
- **Admin panel** — dashboard, articles, categories (nested), users, comments, tags and site
  settings.
- **Author panel** — a Quill.js rich‑text editor with cover‑image upload and TR/EN meta fields.
- **Auth** — email + password (Argon2id) and Google OAuth 2.0, login rate‑limiting, password
  reset tokens.
- **Security‑first** — PDO prepared statements only, CSRF on every POST, output escaping,
  an allow‑list HTML sanitiser for rich‑text content (stops stored XSS), and hardened uploads.
- **Media** — uploads are converted to **WebP** (GD, Imagick fallback). Ships with an optional
  script that auto‑generates branded cover cards for articles that have no image.
- **SEO** — canonical + Open Graph + `hreflang` tags, `sitemap.xml`, `robots.txt`, optional
  Google Analytics and AdSense snippets (settings‑driven).
- **Performance** — file‑based HTML cache helper, lazy images, minimal hand‑written CSS/JS.
- **Subfolder aware** — set `APP_BASE` and the whole app runs cleanly under `/subfolder/`.

## Tech stack

| | |
|---|---|
| Language | PHP 8.0+ (tested on 8.2/8.3) |
| Database | MySQL 8.x / MariaDB 10.5+ |
| Front‑end | Vanilla HTML/CSS/JS, [Quill.js](https://quilljs.com/) via CDN |
| Dependencies | **none** (no Composer / npm) |
| Server | Apache or LiteSpeed with `mod_rewrite`; PHP `pdo_mysql` + `gd` |

## Requirements

- PHP **8.0 or newer** with the `pdo_mysql` and `gd` (or `imagick`) extensions
- MySQL 8.x or MariaDB 10.5+
- A web server with rewrite support (the app front‑controls through `public/index.php`)

## Quick start (local)

```bash
# 1. Clone
git clone https://github.com/<you>/<repo>.git && cd <repo>

# 2. Create a database
mysql -u root -e "CREATE DATABASE blog CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
mysql -u root blog < database/schema.sql
mysql -u root blog < database/seed.sql        # sample categories + 3 demo articles

# 3. Configure
cp .env.example .env      # then edit DB_* (and APP_URL / APP_ENV)

# 4. Run
php -S localhost:8000 -t public public/index.php
```

Open <http://localhost:8000>. Sign in at `/admin` with the seeded admin account:

> **admin@example.com** / **Admin123!** — ⚠️ change this immediately in production.

*(The seed file creates the admin; edit `database/seed.sql` to change the email/password hash.)*

## Configuration (`.env`)

| Key | Purpose |
|-----|---------|
| `APP_ENV` | `production`, `development` or `local` (controls error display) |
| `APP_URL` | Absolute base URL, no trailing slash |
| `APP_BASE` | Sub‑path when hosted under a subfolder, e.g. `/blog` (empty at root) |
| `DB_HOST` `DB_NAME` `DB_USER` `DB_PASS` | Database connection |
| `GOOGLE_CLIENT_ID` / `_SECRET` / `_REDIRECT_URI` | Google OAuth (optional) |
| `MAIL_FROM` | `From:` address for `mail()` |

## Project structure

```
app/
  Core/          Database, Router, Session, Auth, View, Image, SEO, Cache, HtmlSanitizer, …
  Models/        BaseModel + Article/Category/User/Tag/Comment/Like/Bookmark/Setting
  Controllers/   public, auth/author, and Admin/*
  routes.php     all route definitions
views/           plain PHP templates (layouts, partials, pages)
lang/            tr.php / en.php UI strings
public/          web root — index.php, .htaccess, css/, js/, img/, uploads/
database/        schema.sql, seed.sql, + optional data tooling
cache/           file HTML cache (writable)
```

## Optional tooling

- **`database/generate_covers.php`** — generates a unique branded WebP cover (title on a
  category‑coloured gradient) for every article with no image, plus a default OG image. Run once
  after importing content: `php database/generate_covers.php`.
- **`database/import_realblog.php`** — example bulk importer that migrates a legacy
  Node/SQLite blog export into this schema (sanitising HTML, preserving dates). Adapt it as a
  starting point for your own migrations.

## Deployment

1. Upload the project; point the web server's document root at **`public/`**.
   *Shared host without a per‑app doc root?* Put the project in a subfolder, set `APP_BASE` to
   that path (e.g. `/blog`), and add `RewriteBase /blog/` — the app rewrites all its URLs to match.
2. Create the MySQL database + user, import `schema.sql` then `seed.sql`.
3. `cp .env.example .env` and fill in real values (`APP_ENV=production`).
4. For Google login, create OAuth credentials and set the redirect URI to
   `{APP_URL}/auth/google/callback`.
5. Make `public/uploads/` and `cache/` writable (`755`).
6. Log in at `/admin` and change the default password.

**Notes:** `mail()` reliability depends on host config; WebP conversion needs GD built with
JPEG/PNG support; run behind HTTPS (session cookies are `Secure`, and Google OAuth requires it).

## Security

PDO prepared statements everywhere, CSRF validation on every POST handler, all output escaped,
a DOM allow‑list sanitiser for rich‑text article content, Argon2id password hashing, login
rate‑limiting, single‑use expiring reset tokens, and `.htaccess` rules that block direct access
to `app/`, `lang/`, `views/`, `cache/` and `.env`.

## License

Released under the [MIT License](LICENSE).
