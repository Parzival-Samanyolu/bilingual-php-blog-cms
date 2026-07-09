<?php
/**
 * Admin layout. Owned by the Admin Panel agent.
 *
 * Receives: $content (rendered template) and all data keys from the controller.
 * Computes pending-moderation badge counts directly so every admin page shows them.
 *
 * @var string $content
 */

use App\Core\Session;
use App\Models\ArticleModel;
use App\Models\CommentModel;
use App\Models\UserModel;

$adminUser = Session::getUser();

$pendingArticles = 0;
$pendingComments = 0;
$pendingUsers = 0;
try {
    $pendingArticles = count((new ArticleModel())->getPending());
    $pendingComments = (new CommentModel())->countPending();
    $pendingUsers = count((new UserModel())->getPendingAuthors());
} catch (\Throwable $e) {
    // Badges are non-critical; degrade gracefully if DB is unavailable.
}

$currentPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$isActive = static function (string $prefix) use ($currentPath): string {
    if ($prefix === '/admin') {
        return $currentPath === '/admin' ? ' active' : '';
    }
    return str_starts_with($currentPath, $prefix) ? ' active' : '';
};

$flashes = Session::getFlash();
$pageTitle = isset($pageTitle) ? (string) $pageTitle : (function_exists('__') ? __('admin_panel') : 'Admin');
?>
<!doctype html>
<html lang="<?= e(\App\Core\Lang::getLang()) ?>">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="csrf-token" content="<?= e(Session::getToken()) ?>">
<meta name="robots" content="noindex, nofollow">
<title><?= e($pageTitle) ?> — <?= e(__('admin_panel')) ?></title>
<link rel="stylesheet" href="/css/admin.css">
<style>
:root{--admin-bg:#1e293b;--admin-hover:#334155;--admin-accent:#2563eb;--admin-text:#e2e8f0;
--admin-muted:#94a3b8;--page-bg:#f1f5f9;--card:#ffffff;--border:#e2e8f0;--ink:#1a1a1a;}
*{box-sizing:border-box;}
body{margin:0;font-family:system-ui,-apple-system,"Segoe UI",Roboto,sans-serif;background:var(--page-bg);color:var(--ink);}
a{color:var(--admin-accent);text-decoration:none;}
.admin-shell{display:flex;min-height:100vh;}
.admin-sidebar{position:fixed;top:0;left:0;width:240px;height:100vh;background:var(--admin-bg);
color:var(--admin-text);display:flex;flex-direction:column;overflow-y:auto;z-index:40;}
.admin-sidebar .brand{padding:1.25rem 1.25rem;font-size:1.15rem;font-weight:700;color:#fff;
border-bottom:1px solid var(--admin-hover);display:flex;align-items:center;gap:.5rem;}
.admin-nav{list-style:none;margin:0;padding:.5rem 0;flex:1;}
.admin-nav a{display:flex;align-items:center;justify-content:space-between;gap:.5rem;
padding:.7rem 1.25rem;color:var(--admin-text);font-size:.95rem;transition:background .15s;}
.admin-nav a .ico{margin-right:.6rem;}
.admin-nav a:hover{background:var(--admin-hover);}
.admin-nav a.active{background:var(--admin-accent);color:#fff;}
.admin-nav .badge{background:#ef4444;color:#fff;border-radius:999px;font-size:.72rem;
padding:.05rem .45rem;min-width:1.2rem;text-align:center;line-height:1.4;}
.admin-nav a.active .badge{background:rgba(255,255,255,.25);}
.admin-main{margin-left:240px;flex:1;display:flex;flex-direction:column;min-width:0;}
.admin-topbar{background:var(--card);border-bottom:1px solid var(--border);
padding:.75rem 1.5rem;display:flex;align-items:center;justify-content:space-between;position:sticky;top:0;z-index:30;}
.admin-topbar .menu-toggle{display:none;background:none;border:0;font-size:1.4rem;cursor:pointer;color:var(--ink);}
.admin-topbar .user{display:flex;align-items:center;gap:.9rem;font-size:.9rem;}
.admin-topbar .user .uname{font-weight:600;}
.admin-topbar .logout{color:#ef4444;font-weight:600;}
.admin-content{padding:1.75rem 1.5rem;flex:1;}
.admin-content h1{font-size:1.5rem;margin:0 0 1.25rem;}
.flash{padding:.75rem 1rem;border-radius:8px;margin-bottom:1rem;font-size:.92rem;}
.flash.success{background:#dcfce7;color:#166534;border:1px solid #86efac;}
.flash.error{background:#fee2e2;color:#991b1b;border:1px solid #fca5a5;}
.flash.info{background:#dbeafe;color:#1e40af;border:1px solid #93c5fd;}
.card{background:var(--card);border:1px solid var(--border);border-radius:10px;padding:1.25rem;margin-bottom:1.5rem;}
.card h2{font-size:1.1rem;margin:0 0 1rem;}
.stat-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:1rem;margin-bottom:1.75rem;}
.stat-card{background:var(--card);border:1px solid var(--border);border-radius:10px;padding:1.1rem 1.25rem;}
.stat-card .num{font-size:1.9rem;font-weight:700;line-height:1;}
.stat-card .label{color:#64748b;font-size:.85rem;margin-top:.4rem;}
.stat-card .ico{font-size:1.3rem;}
table.data{width:100%;border-collapse:collapse;background:var(--card);font-size:.9rem;}
table.data th,table.data td{padding:.65rem .75rem;text-align:left;border-bottom:1px solid var(--border);vertical-align:middle;}
table.data thead th{background:#f8fafc;font-weight:600;color:#475569;font-size:.8rem;text-transform:uppercase;letter-spacing:.02em;}
table.data tbody tr:nth-child(even){background:#f8fafc;}
.table-wrap{overflow-x:auto;border:1px solid var(--border);border-radius:10px;}
.badge-status{display:inline-block;padding:.15rem .55rem;border-radius:999px;font-size:.75rem;font-weight:600;}
.badge-status.draft{background:#e2e8f0;color:#475569;}
.badge-status.pending{background:#fef9c3;color:#854d0e;}
.badge-status.published{background:#dcfce7;color:#166534;}
.badge-status.rejected{background:#fee2e2;color:#991b1b;}
.badge-role{display:inline-block;padding:.15rem .55rem;border-radius:6px;font-size:.75rem;font-weight:600;}
.badge-role.admin{background:#ede9fe;color:#5b21b6;}
.badge-role.author{background:#dbeafe;color:#1e40af;}
.badge-role.reader{background:#f1f5f9;color:#475569;}
.badge-appr{display:inline-block;padding:.1rem .5rem;border-radius:6px;font-size:.72rem;font-weight:600;}
.badge-appr.yes{background:#dcfce7;color:#166534;}
.badge-appr.no{background:#fef9c3;color:#854d0e;}
.badge-appr.ban{background:#fee2e2;color:#991b1b;}
.btn{display:inline-block;padding:.45rem .9rem;border-radius:7px;border:1px solid var(--border);
background:var(--card);color:var(--ink);font-size:.88rem;cursor:pointer;font-family:inherit;}
.btn:hover{background:#f1f5f9;}
.btn-primary{background:var(--admin-accent);border-color:var(--admin-accent);color:#fff;}
.btn-primary:hover{background:#1d4ed8;}
.btn-success{background:#16a34a;border-color:#16a34a;color:#fff;}
.btn-danger{background:#dc2626;border-color:#dc2626;color:#fff;}
.btn-sm{padding:.28rem .6rem;font-size:.8rem;}
.actions{display:flex;gap:.35rem;flex-wrap:wrap;}
.actions form{display:inline;margin:0;}
.filters{display:flex;flex-wrap:wrap;gap:.75rem;align-items:end;margin-bottom:1.25rem;
background:var(--card);border:1px solid var(--border);border-radius:10px;padding:1rem 1.1rem;}
.filters .field{display:flex;flex-direction:column;gap:.25rem;}
.filters label{font-size:.78rem;color:#64748b;font-weight:600;}
.form-row{margin-bottom:1.1rem;}
.form-row label{display:block;font-weight:600;font-size:.88rem;margin-bottom:.35rem;}
input[type=text],input[type=email],input[type=number],input[type=url],input[type=password],
select,textarea{width:100%;padding:.55rem .7rem;border:1px solid #cbd5e1;border-radius:7px;
font-family:inherit;font-size:.92rem;background:#fff;color:var(--ink);}
input:focus,select:focus,textarea:focus{outline:2px solid var(--admin-accent);outline-offset:-1px;border-color:var(--admin-accent);}
select{max-width:100%;}
textarea{min-height:90px;resize:vertical;}
.grid-2{display:grid;grid-template-columns:1fr 1fr;gap:1.25rem;}
.muted{color:#64748b;font-size:.85rem;}
.tree-indent{display:inline-block;}
.empty{padding:2rem;text-align:center;color:#94a3b8;}
.field-inline{display:flex;gap:.4rem;align-items:center;}
.field-inline input{min-width:0;}
@media(max-width:820px){
.grid-2{grid-template-columns:1fr;}
.admin-sidebar{transform:translateX(-100%);transition:transform .2s;}
.admin-sidebar.open{transform:translateX(0);}
.admin-main{margin-left:0;}
.admin-topbar .menu-toggle{display:block;}
}
</style>
</head>
<body>
<div class="admin-shell">
  <aside class="admin-sidebar" id="adminSidebar">
    <div class="brand"><span>&#9632;</span> real.com.tr</div>
    <ul class="admin-nav">
      <li><a class="<?= $isActive('/admin') ?>" href="/admin"><span><span class="ico">&#128202;</span><?= e(__('admin_nav_dashboard')) ?></span></a></li>
      <li><a class="<?= $isActive('/admin/yazilar') ?>" href="/admin/yazilar"><span><span class="ico">&#128196;</span><?= e(__('admin_nav_articles')) ?></span><?php if ($pendingArticles > 0): ?><span class="badge"><?= (int) $pendingArticles ?></span><?php endif; ?></a></li>
      <li><a class="<?= $isActive('/admin/kategoriler') ?>" href="/admin/kategoriler"><span><span class="ico">&#128193;</span><?= e(__('admin_nav_categories')) ?></span></a></li>
      <li><a class="<?= $isActive('/admin/kullanicilar') ?>" href="/admin/kullanicilar"><span><span class="ico">&#128100;</span><?= e(__('admin_nav_users')) ?></span><?php if ($pendingUsers > 0): ?><span class="badge"><?= (int) $pendingUsers ?></span><?php endif; ?></a></li>
      <li><a class="<?= $isActive('/admin/yorumlar') ?>" href="/admin/yorumlar"><span><span class="ico">&#128172;</span><?= e(__('admin_nav_comments')) ?></span><?php if ($pendingComments > 0): ?><span class="badge"><?= (int) $pendingComments ?></span><?php endif; ?></a></li>
      <li><a class="<?= $isActive('/admin/etiketler') ?>" href="/admin/etiketler"><span><span class="ico">&#127991;</span><?= e(__('admin_nav_tags')) ?></span></a></li>
      <li><a class="<?= $isActive('/admin/ayarlar') ?>" href="/admin/ayarlar"><span><span class="ico">&#9881;</span><?= e(__('admin_nav_settings')) ?></span></a></li>
    </ul>
    <div style="padding:1rem 1.25rem;border-top:1px solid var(--admin-hover);">
      <a href="/" style="color:var(--admin-muted);font-size:.85rem;">&#8592; <?= e(__('admin_view_site')) ?></a>
    </div>
  </aside>

  <div class="admin-main">
    <header class="admin-topbar">
      <button class="menu-toggle" type="button" aria-label="Menu" onclick="document.getElementById('adminSidebar').classList.toggle('open')">&#9776;</button>
      <div style="flex:1"></div>
      <div class="user">
        <span class="uname"><?= e($adminUser['name'] ?? __('admin_panel')) ?></span>
        <a class="logout" href="/cikis"><?= e(__('admin_logout')) ?></a>
      </div>
    </header>

    <main class="admin-content">
      <?php foreach ($flashes as $flash): ?>
        <div class="flash <?= e($flash['type']) ?>"><?= e($flash['message']) ?></div>
      <?php endforeach; ?>
      <?= $content ?>
    </main>
  </div>
</div>
<script src="/js/main.js" defer></script>
</body>
</html>
