<?php

declare(strict_types=1);

/**
 * One-off importer: RealBlog (Node/SQLite export) -> My Blog (MySQL).
 *
 * Reads the exported db/blog.sqlite3 and loads its categories + published posts
 * into our schema (categories/category_translations, articles/article_translations),
 * sanitising the HTML content and preserving original publish dates. Cover and
 * in-content images are referenced by /uploads/<file> paths — copy the export's
 * public/uploads/* into this project's public/uploads/ before running.
 *
 * Usage:
 *   php database/import_realblog.php /path/to/blog.sqlite3
 *
 * WARNING: this REPLACES all article/category/tag/comment/like/bookmark data.
 * Users and settings are preserved.
 */

if (PHP_SAPI !== 'cli') {
    fwrite(STDERR, "Run from the command line.\n");
    exit(1);
}

$root = dirname(__DIR__);
require $root . '/app/Core/HtmlSanitizer.php';

use App\Core\HtmlSanitizer;

$sqlitePath = $argv[1] ?? '';
if ($sqlitePath === '' || !is_file($sqlitePath)) {
    fwrite(STDERR, "Usage: php database/import_realblog.php /path/to/blog.sqlite3\n");
    exit(1);
}

/* --- Load .env for the MySQL connection ---------------------------------- */
$env = [];
foreach (@file($root . '/.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [] as $line) {
    $line = trim($line);
    if ($line === '' || $line[0] === '#' || !str_contains($line, '=')) {
        continue;
    }
    [$k, $v] = explode('=', $line, 2);
    $env[trim($k)] = trim($v, " \t\"'");
}

$dbHost = $env['DB_HOST'] ?? '127.0.0.1';
$dbName = $env['DB_NAME'] ?? '';
$dbUser = $env['DB_USER'] ?? 'root';
$dbPass = $env['DB_PASS'] ?? '';
if ($dbName === '') {
    fwrite(STDERR, "DB_NAME missing from .env\n");
    exit(1);
}

$mysql = new PDO(
    "mysql:host={$dbHost};dbname={$dbName};charset=utf8mb4",
    $dbUser,
    $dbPass,
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
);
$sqlite = new PDO("sqlite:{$sqlitePath}", null, null, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);

/* --- Resolve the author (first admin, else first user) ------------------- */
$authorId = (int) ($mysql->query(
    "SELECT id FROM users ORDER BY (role='admin') DESC, id ASC LIMIT 1"
)->fetchColumn() ?: 0);
if ($authorId === 0) {
    fwrite(STDERR, "No users found — import the seed (admin) first.\n");
    exit(1);
}

/* --- English names for the source categories ----------------------------- */
$catEn = [
    'egitim' => 'Education', 'yasam' => 'Life', 'genel-kultur' => 'General Culture',
    'doviz' => 'Currency', 'internet' => 'Internet', 'otomotiv' => 'Automotive',
    'canlilar' => 'Wildlife', 'saglik' => 'Health', 'emtialar' => 'Commodities',
    'teknoloji' => 'Technology', 'genel' => 'General', 'ekonomi' => 'Economy',
    'kadinca' => 'Women', 'bankalar' => 'Banks',
];

/* --- Wipe existing content (keep users + settings) ----------------------- */
echo "Clearing existing content...\n";
$mysql->exec('SET FOREIGN_KEY_CHECKS = 0');
foreach (['article_tags', 'tag_translations', 'tags', 'likes', 'bookmarks', 'comments',
          'article_translations', 'articles', 'category_translations', 'categories'] as $t) {
    $mysql->exec("TRUNCATE TABLE `{$t}`");
}
$mysql->exec('SET FOREIGN_KEY_CHECKS = 1');

/* --- Categories ---------------------------------------------------------- */
echo "Importing categories...\n";
$insCat = $mysql->prepare("INSERT INTO categories (slug, sort_order, created_at) VALUES (?, ?, NOW())");
$insCatT = $mysql->prepare(
    "INSERT INTO category_translations (category_id, lang, name) VALUES (?, ?, ?)"
);
// 'genel' holds only WordPress utility pages (About/Contact/Privacy/…), not
// articles — skip the category entirely.
$skipCats = ['genel' => true];

$catId = [];
$sort = 0;
foreach ($sqlite->query("SELECT slug, name FROM categories ORDER BY name") as $c) {
    $slug = (string) $c['slug'];
    if (isset($skipCats[$slug])) {
        continue;
    }
    $insCat->execute([$slug, $sort++]);
    $id = (int) $mysql->lastInsertId();
    $catId[$slug] = $id;
    $insCatT->execute([$id, 'tr', (string) $c['name']]);
    $insCatT->execute([$id, 'en', $catEn[$slug] ?? (string) $c['name']]);
}
// Fallback category for any post whose category is missing.
$fallbackCat = $catId['genel-kultur'] ?? (int) reset($catId);
echo '  ' . count($catId) . " categories\n";

/* --- Posts --------------------------------------------------------------- */
echo "Importing articles (sanitising HTML)...\n";
$insArt = $mysql->prepare(
    "INSERT INTO articles
        (author_id, category_id, slug, cover_image, status, view_count, lang, created_at, updated_at)
     VALUES (?, ?, ?, ?, 'published', ?, 'tr', ?, ?)"
);
$insArtT = $mysql->prepare(
    "INSERT INTO article_translations (article_id, lang, title, content, excerpt, meta_title, meta_description)
     VALUES (?, 'tr', ?, ?, ?, ?, ?)"
);

// Remove leftover WordPress shortcodes ([renkbox …][/renkbox], [email], …) —
// including any [renkbox]…[/renkbox] promo-box body, which links to the old site.
$stripShortcodes = static function (string $html): string {
    $html = preg_replace('/\[renkbox\b[^\]]*\].*?\[\/renkbox\]/is', '', $html) ?? $html;
    return preg_replace('/\[\/?[a-zA-Z][a-zA-Z0-9_-]*(?:\s[^\]]*)?\]/', '', $html) ?? $html;
};

$makeExcerpt = static function (string $html): string {
    $text = trim(preg_replace('/\s+/', ' ', strip_tags($html)) ?? '');
    return mb_substr($text, 0, 300);
};

$rows = $sqlite->query(
    "SELECT slug, title, excerpt, content_html, category_slug, featured_image, published_at
     FROM posts WHERE status = 'published' ORDER BY id"
);

$mysql->beginTransaction();
$n = 0; $skipped = 0; $seen = [];
foreach ($rows as $p) {
    $slug = trim((string) $p['slug']);
    if ($slug === '' || isset($seen[$slug])) { $skipped++; continue; }
    $seen[$slug] = true;

    $title = trim((string) $p['title']);
    if ($title === '') { $skipped++; continue; }

    // Skip WordPress utility pages (the undated 'genel' entries).
    if ((string) ($p['category_slug'] ?? '') === 'genel' || trim((string) ($p['published_at'] ?? '')) === '') {
        $skipped++;
        continue;
    }

    $rawContent = $stripShortcodes((string) ($p['content_html'] ?? ''));
    $content = HtmlSanitizer::clean($rawContent);
    $excerpt = trim((string) ($p['excerpt'] ?? ''));
    if ($excerpt === '') {
        $excerpt = $makeExcerpt($rawContent);
    } else {
        $excerpt = mb_substr($excerpt, 0, 300);
    }

    $catSlug = (string) ($p['category_slug'] ?? '');
    $categoryId = $catId[$catSlug] ?? $fallbackCat;

    $cover = trim((string) ($p['featured_image'] ?? ''));
    $cover = $cover !== '' ? $cover : null;

    $pub = (string) ($p['published_at'] ?? '');
    $ts = $pub !== '' ? strtotime($pub) : false;
    $when = date('Y-m-d H:i:s', $ts !== false ? $ts : time());

    // Real view counts start at 0 and accumulate from actual visits — the source
    // export carried no engagement data, so anything else would be fabricated.
    $views = 0;

    $insArt->execute([$authorId, $categoryId, mb_substr($slug, 0, 300), $cover, $views, $when, $when]);
    $articleId = (int) $mysql->lastInsertId();
    $insArtT->execute([
        $articleId,
        mb_substr($title, 0, 500),
        $content,
        $excerpt,
        mb_substr($title, 0, 160),
        mb_substr($excerpt, 0, 320),
    ]);

    if (++$n % 200 === 0) {
        echo "  {$n} articles...\n";
    }
}
$mysql->commit();

echo "Done. Imported {$n} articles, skipped {$skipped}.\n";
echo "Categories: " . count($catId) . ", author_id: {$authorId}\n";
