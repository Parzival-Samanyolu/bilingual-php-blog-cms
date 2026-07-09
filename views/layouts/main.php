<?php
/**
 * Public site layout (main) — full HTML document.
 *
 * Receives from View::render():
 *   - string $content : the rendered template output.
 *   - array  $seo     : optional SEO config for App\Core\SEO::buildMeta().
 *   - plus any other data keys passed to the template.
 *
 * File ownership: this layout is maintained ONLY by the i18n/SEO agent.
 *
 * @var string $content
 */

use App\Core\Lang;
use App\Core\SEO;
use App\Core\Session;

$lang    = Lang::getLang();
$dir     = 'ltr';
$seoData = (isset($seo) && is_array($seo)) ? $seo
    : ((isset($meta) && is_array($meta)) ? $meta : [
        'title'       => __('site_tagline'),
        'description' => __('footer_about'),
        'og_type'     => 'website',
    ]);

$flashes  = Session::getFlash();
$partials = __DIR__ . '/../partials';
?>
<!doctype html>
<html lang="<?= e($lang) ?>" dir="<?= e($dir) ?>">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="<?= e(Session::getToken()) ?>">
    <meta name="base-path" content="<?= defined('APP_BASE') ? e(APP_BASE) : '' ?>">
    <meta name="theme-color" content="#2563eb">
    <link rel="icon" href="/img/logo-mark.svg" type="image/svg+xml">
    <link rel="apple-touch-icon" href="/img/logo-mark.svg">
    <?php SEO::buildMeta($seoData); ?>

    <link rel="preconnect" href="https://cdn.quilljs.com" crossorigin>
    <link rel="stylesheet" href="/css/main.css">
    <link rel="stylesheet" href="https://cdn.quilljs.com/1.3.7/quill.snow.css" crossorigin="anonymous">
    <script defer src="https://cdn.quilljs.com/1.3.7/quill.min.js" crossorigin="anonymous"></script>
    <script defer src="/js/main.js"></script>
</head>
<body class="site-body" data-lang="<?= e($lang) ?>">
    <a class="skip-link" href="#main-content"><?= e(__('nav_home')) ?></a>

    <?php
    // --- Header -----------------------------------------------------------
    if (is_file($partials . '/header.php')) {
        include $partials . '/header.php';
    }
    ?>

    <main id="main-content" class="site-main">
        <?php if ($flashes !== []): ?>
            <div class="flash-container" role="status" aria-live="polite">
                <?php foreach ($flashes as $flash): ?>
                    <?php
                    $type = (string) ($flash['type'] ?? 'info');
                    if (!in_array($type, ['success', 'error', 'info', 'warning'], true)) {
                        $type = 'info';
                    }
                    ?>
                    <div class="flash flash--<?= e($type) ?>">
                        <span class="flash__message"><?= e((string) ($flash['message'] ?? '')) ?></span>
                        <button type="button" class="flash__close" aria-label="<?= e(__('btn_cancel')) ?>" data-flash-close>&times;</button>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <?= $content ?>
    </main>

    <?php
    // --- Footer -----------------------------------------------------------
    if (is_file($partials . '/footer.php')) {
        include $partials . '/footer.php';
    }
    ?>
</body>
</html>
