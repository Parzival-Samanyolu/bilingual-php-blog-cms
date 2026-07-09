<?php

declare(strict_types=1);

namespace App\Core;

use App\Models\SettingModel;

/**
 * Search-engine & social metadata, sitemap and robots.txt generation.
 *
 * buildMeta() ECHOES a block of <title>, <meta>, Open Graph, hreflang and
 * (conditionally) Google Analytics 4 + AdSense auto-ads tags directly into the
 * document <head>. generateSitemap() / generateRobotsTxt() return strings to be
 * echoed by the SeoController with the appropriate content-type header.
 */
final class SEO
{
    /** Canonical fallback base URL when APP_URL is not configured (no trailing slash). */
    private const DEFAULT_BASE_URL = 'https://www.example.com';

    /**
     * Echo all <head> SEO / social / analytics markup for the current page.
     *
     * Recognised $config keys (all optional):
     *   - title        string  page title (site name is appended automatically)
     *   - description  string  meta description / og:description
     *   - og_image     string  absolute or root-relative image URL (falls back
     *                          to the og_default_image setting)
     *   - og_type      string  Open Graph type (default "website")
     *   - url          string  canonical URL for this page (default: current URL)
     *   - url_tr       string  Turkish alternate URL (hreflang="tr" / x-default)
     *   - url_en       string  English alternate URL (hreflang="en")
     *   - robots       string  robots meta directive (default "index, follow")
     *   - keywords     string  optional meta keywords
     *
     * @param array<string,mixed> $config
     */
    public static function buildMeta(array $config = []): void
    {
        $settings = new SettingModel();

        $siteName = $settings->get('site_name_' . Lang::getLang(), 'My Blog');
        $siteName = ($siteName === null || $siteName === '') ? 'My Blog' : $siteName;

        $rawTitle    = trim((string) ($config['title'] ?? ''));
        $title       = $rawTitle !== ''
            ? $rawTitle . ' | ' . $siteName
            : $siteName . ' — ' . __('site_tagline');

        $description = self::normalizeText((string) ($config['description'] ?? __('site_tagline')), 320);
        $ogType      = (string) ($config['og_type'] ?? 'website');
        $robots      = (string) ($config['robots'] ?? 'index, follow');
        $canonical   = self::absoluteUrl((string) ($config['url'] ?? self::currentUrl()));

        $ogImageRaw  = (string) ($config['og_image'] ?? '');
        if ($ogImageRaw === '') {
            $ogImageRaw = (string) ($settings->get('og_default_image', '/img/og-default.jpg') ?? '');
        }
        $ogImage = self::absoluteUrl($ogImageRaw);

        $lines = [];

        $lines[] = '<title>' . self::esc($title) . '</title>';
        $lines[] = '<meta name="description" content="' . self::esc($description) . '">';

        if (!empty($config['keywords'])) {
            $lines[] = '<meta name="keywords" content="' . self::esc((string) $config['keywords']) . '">';
        }

        $lines[] = '<meta name="robots" content="' . self::esc($robots) . '">';
        $lines[] = '<link rel="canonical" href="' . self::esc($canonical) . '">';

        // Open Graph
        $lines[] = '<meta property="og:site_name" content="' . self::esc((string) $siteName) . '">';
        $lines[] = '<meta property="og:title" content="' . self::esc($title) . '">';
        $lines[] = '<meta property="og:description" content="' . self::esc($description) . '">';
        $lines[] = '<meta property="og:type" content="' . self::esc($ogType) . '">';
        $lines[] = '<meta property="og:url" content="' . self::esc($canonical) . '">';
        if ($ogImage !== '') {
            $lines[] = '<meta property="og:image" content="' . self::esc($ogImage) . '">';
        }
        $lines[] = '<meta property="og:locale" content="' . (Lang::getLang() === 'en' ? 'en_US' : 'tr_TR') . '">';

        // Twitter card
        $lines[] = '<meta name="twitter:card" content="summary_large_image">';
        $lines[] = '<meta name="twitter:title" content="' . self::esc($title) . '">';
        $lines[] = '<meta name="twitter:description" content="' . self::esc($description) . '">';
        if ($ogImage !== '') {
            $lines[] = '<meta name="twitter:image" content="' . self::esc($ogImage) . '">';
        }

        // hreflang alternates
        $urlTr = isset($config['url_tr']) ? self::absoluteUrl((string) $config['url_tr']) : '';
        $urlEn = isset($config['url_en']) ? self::absoluteUrl((string) $config['url_en']) : '';

        if ($urlTr !== '') {
            $lines[] = '<link rel="alternate" hreflang="tr" href="' . self::esc($urlTr) . '">';
        }
        if ($urlEn !== '') {
            $lines[] = '<link rel="alternate" hreflang="en" href="' . self::esc($urlEn) . '">';
        }
        // x-default points at the Turkish (default language) URL when known.
        $xDefault = $urlTr !== '' ? $urlTr : ($urlEn !== '' ? $urlEn : '');
        if ($xDefault !== '') {
            $lines[] = '<link rel="alternate" hreflang="x-default" href="' . self::esc($xDefault) . '">';
        }

        // Google Analytics 4 (only when a measurement id is configured)
        $ga = trim((string) ($settings->get('ga_measurement_id', '') ?? ''));
        if ($ga !== '') {
            $gaJs = self::escJs($ga);
            $lines[] = '<script async src="https://www.googletagmanager.com/gtag/js?id=' . rawurlencode($ga) . '"></script>';
            $lines[] = '<script>window.dataLayer=window.dataLayer||[];function gtag(){dataLayer.push(arguments);}'
                . 'gtag(\'js\',new Date());gtag(\'config\',\'' . $gaJs . '\');</script>';
        }

        // Google AdSense auto ads (only when a client id is configured)
        $adsense = trim((string) ($settings->get('adsense_client_id', '') ?? ''));
        if ($adsense !== '') {
            $lines[] = '<script async src="https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js?client='
                . rawurlencode($adsense) . '" crossorigin="anonymous"></script>';
        }

        echo implode("\n    ", $lines) . "\n";
    }

    /**
     * Build a valid <urlset> sitemap XML string for all published articles and
     * categories. The Turkish URL is always emitted; an English alternate URL is
     * added for entities that have an English translation.
     */
    public static function generateSitemap(): string
    {
        $db   = Database::getInstance();
        $base = self::baseUrl();

        $xml  = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" '
              . 'xmlns:xhtml="http://www.w3.org/1999/xhtml">' . "\n";

        // Home page (both languages).
        $xml .= self::urlEntry(
            $base . '/',
            null,
            'daily',
            '1.0',
            [
                'tr' => $base . '/',
                'en' => $base . '/en',
            ]
        );

        // Categories.
        $categories = $db->fetchAll(
            "SELECT c.slug,
                    MAX(CASE WHEN ct.lang = 'en' THEN 1 ELSE 0 END) AS has_en
             FROM `categories` c
             LEFT JOIN `category_translations` ct ON ct.category_id = c.id
             GROUP BY c.id, c.slug
             ORDER BY c.sort_order ASC"
        );

        foreach ($categories as $cat) {
            $slug  = (string) $cat['slug'];
            $trUrl = $base . '/kategori/' . self::encodePath($slug);
            $enUrl = ((int) ($cat['has_en'] ?? 0) === 1) ? $base . '/category/' . self::encodePath($slug) : null;

            $alts = ['tr' => $trUrl];
            if ($enUrl !== null) {
                $alts['en'] = $enUrl;
            }

            $xml .= self::urlEntry($trUrl, null, 'weekly', '0.6', $alts);
        }

        // Published articles.
        $articles = $db->fetchAll(
            "SELECT a.slug,
                    a.updated_at,
                    c.slug AS category_slug,
                    MAX(CASE WHEN t.lang = 'en' THEN 1 ELSE 0 END) AS has_en
             FROM `articles` a
             INNER JOIN `categories` c ON c.id = a.category_id
             LEFT JOIN `article_translations` t ON t.article_id = a.id
             WHERE a.status = 'published'
             GROUP BY a.id, a.slug, a.updated_at, c.slug
             ORDER BY a.updated_at DESC"
        );

        foreach ($articles as $art) {
            $slug    = (string) $art['slug'];
            $catSlug = (string) $art['category_slug'];
            $lastmod = self::formatLastmod($art['updated_at'] ?? null);

            $trUrl = $base . '/yazi/' . self::encodePath($catSlug) . '/' . self::encodePath($slug);
            $enUrl = ((int) ($art['has_en'] ?? 0) === 1)
                ? $base . '/article/' . self::encodePath($catSlug) . '/' . self::encodePath($slug)
                : null;

            $alts = ['tr' => $trUrl];
            if ($enUrl !== null) {
                $alts['en'] = $enUrl;
            }

            $xml .= self::urlEntry($trUrl, $lastmod, 'weekly', '0.8', $alts);
        }

        $xml .= '</urlset>' . "\n";

        return $xml;
    }

    /**
     * Return the robots.txt body.
     */
    public static function generateRobotsTxt(): string
    {
        $sitemap = self::baseUrl() . '/sitemap.xml';

        $lines = [
            'User-agent: *',
            'Disallow: /admin/',
            'Disallow: /yazar-paneli/',
            'Disallow: /cache/',
            'Allow: /',
            '',
            'Sitemap: ' . $sitemap,
        ];

        return implode("\n", $lines) . "\n";
    }

    // ---------------------------------------------------------------------
    // Internal helpers
    // ---------------------------------------------------------------------

    /**
     * Render a single <url> entry, optionally with xhtml:link alternates.
     *
     * @param array<string,string> $alternates lang => absolute URL
     */
    private static function urlEntry(
        string $loc,
        ?string $lastmod,
        string $changefreq,
        string $priority,
        array $alternates = []
    ): string {
        $out  = "  <url>\n";
        $out .= '    <loc>' . self::escXml($loc) . "</loc>\n";
        if ($lastmod !== null && $lastmod !== '') {
            $out .= '    <lastmod>' . self::escXml($lastmod) . "</lastmod>\n";
        }
        $out .= '    <changefreq>' . $changefreq . "</changefreq>\n";
        $out .= '    <priority>' . $priority . "</priority>\n";

        foreach ($alternates as $lang => $href) {
            $out .= '    <xhtml:link rel="alternate" hreflang="' . self::escXml((string) $lang)
                  . '" href="' . self::escXml($href) . "\"/>\n";
        }
        if (isset($alternates['tr'])) {
            $out .= '    <xhtml:link rel="alternate" hreflang="x-default" href="'
                  . self::escXml($alternates['tr']) . "\"/>\n";
        }

        $out .= "  </url>\n";

        return $out;
    }

    /**
     * Configured site base URL (APP_URL) without a trailing slash.
     */
    private static function baseUrl(): string
    {
        $appUrl = $_ENV['APP_URL'] ?? getenv('APP_URL') ?: '';
        $appUrl = is_string($appUrl) ? trim($appUrl) : '';

        if ($appUrl === '') {
            $appUrl = self::currentOrigin() ?? self::DEFAULT_BASE_URL;
        }

        return rtrim($appUrl, '/');
    }

    /**
     * Resolve a possibly root-relative URL to an absolute one using baseUrl().
     */
    private static function absoluteUrl(string $url): string
    {
        $url = trim($url);
        if ($url === '') {
            return '';
        }
        if (preg_match('#^https?://#i', $url) === 1) {
            return $url;
        }

        return self::baseUrl() . '/' . ltrim($url, '/');
    }

    /**
     * Best-effort current request URL (absolute).
     */
    private static function currentUrl(): string
    {
        $origin = self::currentOrigin() ?? self::baseUrl();
        $uri    = $_SERVER['REQUEST_URI'] ?? '/';
        $uri    = is_string($uri) ? $uri : '/';

        // Strip query string from canonical URL to avoid duplicate-content signals.
        $pos = strpos($uri, '?');
        if ($pos !== false) {
            $uri = substr($uri, 0, $pos);
        }

        return rtrim($origin, '/') . '/' . ltrim($uri, '/');
    }

    /**
     * Scheme + host of the current request, or null when unavailable (CLI).
     */
    private static function currentOrigin(): ?string
    {
        $host = $_SERVER['HTTP_HOST'] ?? '';
        if (!is_string($host) || $host === '') {
            return null;
        }

        $https = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
            || (($_SERVER['SERVER_PORT'] ?? null) == 443)
            || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');

        return ($https ? 'https' : 'http') . '://' . $host;
    }

    /**
     * URL-encode each path segment while keeping the slashes.
     */
    private static function encodePath(string $path): string
    {
        $segments = array_map('rawurlencode', explode('/', $path));

        return implode('/', $segments);
    }

    /**
     * Format a DB datetime as a W3C date (YYYY-MM-DD) for <lastmod>.
     */
    private static function formatLastmod(mixed $value): ?string
    {
        if (!is_string($value) || $value === '') {
            return null;
        }
        $ts = strtotime($value);
        if ($ts === false) {
            return null;
        }

        return date('Y-m-d', $ts);
    }

    /**
     * Collapse whitespace and hard-truncate free text for meta tags.
     */
    private static function normalizeText(string $text, int $max): string
    {
        $text = trim((string) preg_replace('/\s+/u', ' ', strip_tags($text)));
        if (function_exists('mb_strlen') && mb_strlen($text, 'UTF-8') > $max) {
            $text = rtrim(mb_substr($text, 0, $max - 1, 'UTF-8')) . '…';
        }

        return $text;
    }

    private static function esc(string $s): string
    {
        return htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
    }

    private static function escXml(string $s): string
    {
        return htmlspecialchars($s, ENT_QUOTES | ENT_XML1, 'UTF-8');
    }

    private static function escJs(string $s): string
    {
        return str_replace(["\\", "'", "\n", "\r", '<'], ['\\\\', "\\'", '', '', '\\u003C'], $s);
    }
}
