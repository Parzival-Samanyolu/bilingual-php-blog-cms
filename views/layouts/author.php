<?php
/**
 * Author panel layout.
 *
 * NOTE: This file is normally owned by the Author-panel agent (Agent 4). It was
 * missing from the delivered build, so the Integration/QA pass created this
 * minimal, correct version so the /yazar-paneli/* pages render. Loads Quill for
 * the article editor and the shared front-end JS.
 *
 * Receives: $content plus all controller data keys ($title, ...).
 *
 * @var string $content
 */

use App\Core\Lang;
use App\Core\Session;
use App\Models\SettingModel;

$authorUser = Session::getUser();
$flashes    = Session::getFlash();
$pageTitle  = isset($title) ? (string) $title : __('dashboard_title');
$brand      = SettingModel::get('site_name_' . Lang::getLang()) ?: (SettingModel::get('site_name_tr') ?: 'My Blog');
$currentPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
?>
<!doctype html>
<html lang="<?= e(Lang::getLang()) ?>">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="csrf-token" content="<?= e(Session::getToken()) ?>">
<meta name="robots" content="noindex, nofollow">
<title><?= e($pageTitle) ?> — <?= e($brand) ?></title>
<link rel="stylesheet" href="/css/main.css">
<link rel="stylesheet" href="https://cdn.quilljs.com/1.3.7/quill.snow.css" crossorigin="anonymous">
<script defer src="https://cdn.quilljs.com/1.3.7/quill.min.js" crossorigin="anonymous"></script>
<script defer src="/js/main.js"></script>
<style>
:root{--accent:#2563eb;--border:#e2e8f0;--ink:#1a1a1a;--muted:#64748b;--bg:#f8fafc;}
*{box-sizing:border-box;}
body{margin:0;font-family:system-ui,-apple-system,"Segoe UI",Roboto,sans-serif;color:var(--ink);background:var(--bg);}
a{color:var(--accent);text-decoration:none;}
.author-top{background:#fff;border-bottom:1px solid var(--border);padding:.9rem 1.25rem;
display:flex;align-items:center;justify-content:space-between;gap:1rem;flex-wrap:wrap;}
.author-top .brand{font-weight:700;font-size:1.1rem;color:var(--ink);}
.author-top nav{display:flex;gap:1rem;align-items:center;font-size:.92rem;}
.author-wrap{max-width:920px;margin:0 auto;padding:1.5rem 1.25rem;}
.author-wrap h1{font-size:1.5rem;margin:0 0 1.25rem;}
.flash{padding:.7rem 1rem;border-radius:8px;margin-bottom:1rem;font-size:.92rem;}
.flash.success{background:#dcfce7;color:#166534;border:1px solid #86efac;}
.flash.error{background:#fee2e2;color:#991b1b;border:1px solid #fca5a5;}
.flash.info{background:#dbeafe;color:#1e40af;border:1px solid #93c5fd;}
.card{background:#fff;border:1px solid var(--border);border-radius:10px;padding:1.25rem;margin-bottom:1.25rem;}
.stat-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(160px,1fr));gap:1rem;margin-bottom:1.5rem;}
.stat-card{background:#fff;border:1px solid var(--border);border-radius:10px;padding:1rem 1.1rem;}
.stat-card .num{font-size:1.7rem;font-weight:700;line-height:1;}
.stat-card .label{color:var(--muted);font-size:.85rem;margin-top:.35rem;}
table.data{width:100%;border-collapse:collapse;background:#fff;font-size:.92rem;}
table.data th,table.data td{padding:.6rem .7rem;text-align:left;border-bottom:1px solid var(--border);vertical-align:middle;}
table.data thead th{background:#f1f5f9;font-size:.78rem;text-transform:uppercase;letter-spacing:.02em;color:var(--muted);}
.table-wrap{overflow-x:auto;border:1px solid var(--border);border-radius:10px;}
.badge-status{display:inline-block;padding:.12rem .5rem;border-radius:999px;font-size:.74rem;font-weight:600;}
.badge-status.draft{background:#e2e8f0;color:#475569;}
.badge-status.pending{background:#fef9c3;color:#854d0e;}
.badge-status.published{background:#dcfce7;color:#166534;}
.badge-status.rejected{background:#fee2e2;color:#991b1b;}
.btn{display:inline-block;padding:.45rem .9rem;border-radius:7px;border:1px solid var(--border);
background:#fff;color:var(--ink);font-size:.88rem;cursor:pointer;font-family:inherit;}
.btn:hover{background:#f1f5f9;}
.btn-primary{background:var(--accent);border-color:var(--accent);color:#fff;}
.btn-success{background:#16a34a;border-color:#16a34a;color:#fff;}
.btn-danger{background:#dc2626;border-color:#dc2626;color:#fff;}
.btn-sm{padding:.28rem .6rem;font-size:.8rem;}
.actions{display:flex;gap:.35rem;flex-wrap:wrap;}
.actions form{display:inline;margin:0;}
.form-row{margin-bottom:1.1rem;}
.form-row label{display:block;font-weight:600;font-size:.88rem;margin-bottom:.35rem;}
input[type=text],input[type=email],input[type=number],input[type=url],input[type=file],
select,textarea{width:100%;padding:.55rem .7rem;border:1px solid #cbd5e1;border-radius:7px;
font-family:inherit;font-size:.92rem;background:#fff;color:var(--ink);}
textarea{min-height:80px;resize:vertical;}
.muted{color:var(--muted);}
.empty{padding:2rem;text-align:center;color:#94a3b8;}
#editor{min-height:320px;background:#fff;}
.hint{font-size:.8rem;color:var(--muted);margin-top:.25rem;}
</style>
</head>
<body>
<header class="author-top">
  <span class="brand"><?= e($brand) ?> &middot; <?= e(__('nav_dashboard')) ?></span>
  <nav>
    <a href="/yazar-paneli"><?= e(__('nav_dashboard')) ?></a>
    <a href="/yazar-paneli/yeni"><?= e(__('editor_new_title')) ?></a>
    <a href="/"><?= e(__('nav_home')) ?></a>
    <a href="/cikis"><?= e(__('nav_logout')) ?></a>
  </nav>
</header>
<div class="author-wrap">
  <?php foreach ($flashes as $flash): ?>
    <div class="flash <?= e($flash['type']) ?>"><?= e($flash['message']) ?></div>
  <?php endforeach; ?>
  <?= $content ?>
</div>
</body>
</html>
