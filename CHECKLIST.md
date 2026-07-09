# Integration & QA checklist — My Blog

Verified against the files on disk during the integration pass. `[x]` = confirmed
true; `[ ]` = not satisfied / only partially satisfied (see note).

- [x] **All routes registered** — `app/routes.php` wires 67 named routes covering every action of every controller (public, auth, author, admin, SEO, `/lang`); reverse-routing (`route()`) confirmed for the names used in views.
- [x] **CSRF on every POST handler** — auth/author/admin actions call `BaseController::validateCsrf()`; `ArticleController` validates via `X-CSRF-Token` header **or** `_csrf` body; `PageController::sendContact` and the `/lang` endpoint validate inline.
- [x] **No raw SQL interpolation** — all values pass through bound parameters; only integer-cast `LIMIT/OFFSET` and static column `WHERE` fragments are string-built. No superglobal is ever concatenated into SQL.
- [x] **All user output escaped** — templates wrap output in `e()`; the only raw echo is approved-author article HTML rendered inside `.ql-editor` (trusted Quill content, by design).
- [x] **Login rate limit enforced** — `AuthController::login()` gates on `Auth::checkLoginRateLimit()` (< 5 failed attempts / 15 min) and records each failure.
- [x] **Author approval flow present** — author sign-up stores `is_approved = 0`; `AdminUserController::approveUser()` approves; `requireApprovedAuthor()` guard + route middleware protect `/yazar-paneli/*`.
- [x] **Article review flow present** — `AuthorDashboardController::submitForReview()` sets `pending`; `AdminArticleController::publish()/reject()` moderate; `ArticleModel::getPending()` feeds the queue.
- [x] **Comment moderation present** — comments insert with `is_approved = 0`; `AdminCommentController::approve()/reject()`; article pages show approved comments only.
- [x] **WebP conversion present** — `App\Core\Image::upload()` converts uploads to WebP (quality 82) via GD, falling back to Imagick.
- [x] **sitemap.xml valid XML** — `SEO::generateSitemap()` emits a well-formed `<urlset>` with XML-escaped values and hreflang alternates; `GET /sitemap.xml` registered (runtime body requires the DB).
- [x] **robots.txt blocks /admin/ and /yazar-paneli/** — `SEO::generateRobotsTxt()` emits `Disallow: /admin/` and `Disallow: /yazar-paneli/`; `GET /robots.txt` registered.
- [x] **TR/EN switch persists in session + cookie** — `Lang::setLang()` writes both the session and a cookie; `POST /lang` endpoint added (accepts JSON body or form, header/body CSRF). ⚠️ See *Known issue 2* — the header pills won't trigger it until a JS selector mismatch is fixed.
- [ ] **File cache writes .html** — `App\Core\Cache::set()` correctly writes `cache/{md5}.html` (atomic, TTL-aware), **but no controller or the bootstrap calls it**, so full-page caching is currently inert. Wire `Cache::get()/set()` into `HomeController`/`ArticleController` (or clear `cache/*.html` manually) to activate it.
- [x] **Mobile layout at 375px** — `public/css/main.css` includes responsive breakpoints (`@media (max-width: 900px | 768px | 400px)`); the 400px rule covers 375px, and `admin.css` collapses the sidebar at 820px.
- [x] **GA fires when id set** — `SEO::buildMeta()` outputs the gtag.js loader + `config` **only** when the `ga_measurement_id` setting is non-empty.
- [x] **AdSense loads when id set** — `SEO::buildMeta()` outputs the adsbygoogle.js loader **only** when the `adsense_client_id` setting is non-empty.

---

## Build health

- `php -l` on all **73** PHP files: **73 pass / 0 fail**.
- Route registration + reverse routing: verified via harness (67 routes, `route('category', …)` etc. resolve correctly).
- All 11 QA-created templates render without error under sample data (layout-less smoke test).

## Security fixes from code review (verified end-to-end)

Applied after a senior code review and confirmed against a running instance
(login + CSRF + real payloads through the live admin endpoints):

- **[CRITICAL] Stored XSS via Quill article content — FIXED.** Author/admin HTML was
  stored and rendered raw, executing in readers' and (via the moderation editor)
  the admin's browser. Added `app/Core/HtmlSanitizer.php`, a dependency-free
  DOMDocument allowlist sanitizer, applied on write in
  `AuthorDashboardController` + `AdminArticleController` and on render in
  `ArticleController` (defense-in-depth). Verified: `<script>`, `onerror`,
  `javascript:`, `<iframe>`, `<svg>`, non-image `data:` URIs are all stripped
  while Quill formatting (`ql-*` classes) and Turkish text survive.
- **[IMPORTANT] Banned users could still log in — FIXED.** `Auth::login()` now
  rejects `is_approved = -1`. Verified: banned account is denied and cannot reach
  `/admin`; restoring approval restores access.
- **[IMPORTANT] Google OAuth linked accounts by unverified email — FIXED.**
  `Auth::googleCallback()` reads the `email_verified`/`verified_email` claim and
  `upsertGoogleUser()` refuses to link or create an account by email unless
  verified (blocks local-account takeover via a spoofed Google email).
- **[IMPORTANT] Dev `.env` was present in the tree — FIXED.** The local test `.env`
  (`APP_ENV=development`, which enables `display_errors` + full stack traces) has
  been removed; only `.env.example` ships. Keep `APP_ENV=production` in the real
  deployment and never upload a dev `.env`.
- **[IMPORTANT] Category pages ignored subcategory articles — FIXED.**
  `ArticleModel::getByCategory()`/`countByCategory()` gained an `includeSubtree`
  flag (recursive CTE over descendants); `CategoryController` now uses it, so a
  parent category lists articles from its children.

Remaining review notes (Minor, not blocking): comment `parent_id` isn't verified
to belong to the article; no rate limit on password-reset/registration;
`login_attempts` is never pruned; FULLTEXT misses tokens shorter than
`ft_min_token_size` (default 3); view counts increment on every render/reload.

## Integration issues found & fixed during live testing

All items below were verified against a running instance (PHP built-in server + a
seeded MariaDB database); every public, auth, admin and author route returned 200.

1. **`meta` vs `seo` layout contract mismatch (SEO regression) — FIXED.**
   Front controllers pass per-page SEO under the `meta` key; `views/layouts/main.php`
   read `$seo`. Fixed in `main.php`: config now sourced from
   `$seo ?? $meta ?? [defaults]`, so per-page `<title>`, description and hreflang
   are emitted again.

2. **Language-switch JS selector mismatch — FIXED.**
   `public/js/main.js` bound `.lang-pill` / `.active`; the header renders
   `.lang-switch__pill` / `.is-active`. `main.js` was aligned to the header markup,
   so the TR|EN pills now POST to `/lang` and reload.

3. **`SettingModel` static/instance mismatch — FIXED (found via live testing).**
   8 call sites (controllers, header, footer) call `SettingModel::get()` statically,
   but the model defined `get/set/getAll/setMultiple` as instance methods — every
   public page threw `Non-static method … cannot be called statically` (HTTP 500).
   The four methods (and `loadCache`) were converted to `static`, sourcing the DB via
   `Database::getInstance()`. Instance call sites (`SEO`, `Cache`, `adsense`,
   `AdminSettingController`) still work (PHP permits calling static methods via `->`).

4. **File cache is intentionally NOT wired into controllers.**
   `App\Core\Cache` (get/set/bust) is complete and writes `cache/{md5}.html`, but no
   controller calls it. This is deliberate: public pages embed a per-request CSRF
   token (`<meta name="csrf-token">`) and the logged-in user menu, so serving cached
   full-page HTML would leak one visitor's CSRF token / session state to another. If
   you want full-page caching, gate it to anonymous visitors only and strip the CSRF
   meta from cached output.

5. **Missing templates + author layout were created during QA.**
   Agents 4/5/6 did not deliver `views/layouts/author.php`, `views/author/*`,
   `views/auth/*`, `views/profile/show.php`, and the admin
   `users/comments/tags/settings` list views. Minimal, correct, CSRF-safe,
   fully-escaped versions were created so every `View::render()` target resolves.
   Replace with the final designed versions when available.

6. **Router dispatch fix (applied).**
   `App\Core\Router` runs under `declare(strict_types=1)` and dispatches handlers
   via the spread operator; every admin handler typed `int $id` would have thrown
   a `TypeError` (path captures are strings). `Router::invoke()` was updated to
   reflection-coerce numeric string path params to the handler's declared scalar
   type. String handlers are unaffected.
