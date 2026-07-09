<?php
/**
 * Site footer: link columns + copyright. Included by the main layout.
 */

use App\Core\Lang;
use App\Models\CategoryModel;
use App\Models\SettingModel;

$ftLang = Lang::getLang();
$siteName = SettingModel::get($ftLang === 'en' ? 'site_name_en' : 'site_name_tr', 'real.com.tr');
if ($siteName === null || $siteName === '') {
    $siteName = 'real.com.tr';
}

$roots = isset($categoryTree) && is_array($categoryTree)
    ? $categoryTree
    : (new CategoryModel())->getTree($ftLang);

$catBase = $ftLang === 'en' ? '/category/' : '/kategori/';
$aboutUrl = $ftLang === 'en' ? '/about' : '/hakkimizda';
$contactUrl = $ftLang === 'en' ? '/contact' : '/iletisim';
?>
<footer class="site-footer">
    <div class="container site-footer__inner">
        <div class="site-footer__col">
            <h3 class="site-footer__brand"><?= e($siteName) ?></h3>
            <p class="site-footer__tagline"><?= e(__('site_tagline')) ?></p>
        </div>

        <div class="site-footer__col">
            <h4 class="site-footer__heading"><?= e(__('label_category')) ?></h4>
            <ul class="site-footer__links">
                <?php foreach (array_slice($roots, 0, 6) as $root): ?>
                    <li>
                        <a href="<?= e($catBase . rawurlencode((string) ($root['slug'] ?? ''))) ?>">
                            <?= e((string) ($root['name'] ?? '')) ?>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>

        <div class="site-footer__col">
            <h4 class="site-footer__heading"><?= e(__('footer_pages')) ?></h4>
            <ul class="site-footer__links">
                <li><a href="<?= e($aboutUrl) ?>"><?= e(__('about_page_title')) ?></a></li>
                <li><a href="<?= e($contactUrl) ?>"><?= e(__('contact_page_title')) ?></a></li>
                <li><a href="<?= e($ftLang === 'en' ? '/search' : '/ara') ?>"><?= e(__('nav_search')) ?></a></li>
            </ul>
        </div>
    </div>
    <div class="site-footer__bottom">
        <div class="container">
            <p>&copy; <?= e((string) date('Y')) ?> <?= e($siteName) ?>. <?= e(__('footer_rights')) ?></p>
        </div>
    </div>
</footer>
