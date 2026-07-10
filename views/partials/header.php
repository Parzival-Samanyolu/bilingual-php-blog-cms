<?php
/**
 * Site header: logo / site name, search bar, TR|EN language switcher and the
 * authentication-aware user menu. Included by the main layout.
 */

use App\Core\Lang;
use App\Core\Session;
use App\Models\SettingModel;

$hdrLang = Lang::getLang();
$siteName = SettingModel::get($hdrLang === 'en' ? 'site_name_en' : 'site_name_tr', 'My Blog');
if ($siteName === null || $siteName === '') {
    $siteName = 'My Blog';
}

$searchAction = $hdrLang === 'en' ? '/search' : '/ara';
$homeUrl = '/';

$user = Session::getUser();
$isLoggedIn = $user !== null;
$role = $isLoggedIn ? (string) ($user['role'] ?? 'reader') : '';
$username = $isLoggedIn ? (string) ($user['username'] ?? '') : '';
$displayName = $isLoggedIn ? (string) ($user['name'] ?? $username) : '';
$avatar = $isLoggedIn ? (string) ($user['avatar'] ?? '') : '';
$isAuthor = in_array($role, ['author', 'admin'], true);
$isAdmin = $role === 'admin';
?>
<header class="site-header">
    <div class="container site-header__inner">
        <a class="site-header__logo" href="<?= e($homeUrl) ?>">
            <img class="site-header__logo-mark" src="/img/logo-mark.svg" alt="" width="34" height="34">
            <span class="site-header__logo-text"><?= e($siteName) ?></span>
        </a>

        <form class="site-search js-search" action="<?= e($searchAction) ?>" method="get" role="search" autocomplete="off">
            <input type="search"
                   class="site-search__input js-search-input"
                   name="q"
                   value="<?= e((string) ($_GET['q'] ?? '')) ?>"
                   placeholder="<?= e(__('search_placeholder')) ?>"
                   aria-label="<?= e(__('nav_search')) ?>">
            <button type="submit" class="site-search__btn" aria-label="<?= e(__('nav_search')) ?>">&#128269;</button>
            <div class="site-search__suggest js-search-suggest" hidden></div>
        </form>

        <button type="button" class="site-header__nav-toggle js-nav-toggle" aria-label="<?= e(__('aria_menu')) ?>" aria-expanded="false">
            <span></span><span></span><span></span>
        </button>

        <nav class="site-nav js-nav" aria-label="<?= e(__('nav_home')) ?>">
            <div class="lang-switch" role="group" aria-label="<?= e(__('aria_language')) ?>">
                <button type="button"
                        class="lang-switch__pill<?= $hdrLang === 'tr' ? ' is-active' : '' ?>"
                        data-lang="tr"<?= $hdrLang === 'tr' ? ' aria-current="true"' : '' ?>>TR</button>
                <button type="button"
                        class="lang-switch__pill<?= $hdrLang === 'en' ? ' is-active' : '' ?>"
                        data-lang="en"<?= $hdrLang === 'en' ? ' aria-current="true"' : '' ?>>EN</button>
            </div>

            <?php if ($isLoggedIn): ?>
                <div class="user-menu js-user-menu">
                    <button type="button" class="user-menu__trigger js-user-menu-trigger" aria-haspopup="true" aria-expanded="false">
                        <?php if ($avatar !== ''): ?>
                            <img class="user-menu__avatar" src="<?= e($avatar) ?>" alt="" width="32" height="32">
                        <?php else: ?>
                            <span class="user-menu__avatar user-menu__avatar--placeholder" aria-hidden="true"><?= e(mb_strtoupper(mb_substr($displayName !== '' ? $displayName : 'U', 0, 1))) ?></span>
                        <?php endif; ?>
                        <span class="user-menu__name"><?= e($displayName) ?></span>
                    </button>
                    <ul class="user-menu__dropdown js-user-menu-dropdown">
                        <?php if ($isAuthor && $username !== ''): ?>
                            <li><a href="<?= e('/yazar/' . rawurlencode($username)) ?>"><?= e(__('nav_profile')) ?></a></li>
                            <li><a href="/yazar-paneli"><?= e(__('nav_dashboard')) ?></a></li>
                        <?php endif; ?>
                        <?php if ($isAdmin): ?>
                            <li><a href="/admin"><?= e(__('admin_dashboard')) ?></a></li>
                        <?php endif; ?>
                        <li><a href="/cikis"><?= e(__('nav_logout')) ?></a></li>
                    </ul>
                </div>
            <?php else: ?>
                <div class="auth-links">
                    <a class="auth-links__login" href="/giris"><?= e(__('nav_login')) ?></a>
                    <a class="auth-links__register btn btn--primary" href="/kayit"><?= e(__('nav_register')) ?></a>
                </div>
            <?php endif; ?>
        </nav>
    </div>
</header>
