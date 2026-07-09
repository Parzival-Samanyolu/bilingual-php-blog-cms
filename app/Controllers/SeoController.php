<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\SEO;

/**
 * XML sitemap and robots.txt endpoints. Both bypass the HTML layout and stream
 * their body directly with the appropriate content type.
 */
final class SeoController extends BaseController
{
    /**
     * GET /sitemap.xml — emit the generated URL set.
     */
    public function sitemap(): void
    {
        if (!headers_sent()) {
            header('Content-Type: application/xml; charset=utf-8');
        }

        echo SEO::generateSitemap();
        exit;
    }

    /**
     * GET /robots.txt — emit the robots policy.
     */
    public function robots(): void
    {
        if (!headers_sent()) {
            header('Content-Type: text/plain; charset=utf-8');
        }

        echo SEO::generateRobotsTxt();
        exit;
    }
}
