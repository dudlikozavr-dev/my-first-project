<!DOCTYPE html>
<html lang="ru">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= $page_title ?? 'Админ' ?> — SikretSweet</title>
  <style>
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
    body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; background: #f5f0ea; color: #1c1208; font-size: 15px; }
    a { color: #b8892e; text-decoration: none; }
    a:hover { text-decoration: underline; }

    .sidebar {
      position: fixed; top: 0; left: 0; width: 220px; height: 100vh;
      background: #1c1208; color: #e8dcc8; display: flex; flex-direction: column;
      padding: 24px 0; z-index: 100;
    }
    .sidebar-logo { font-size: 1.2rem; font-weight: 600; color: #d4a843; padding: 0 24px 24px; border-bottom: 1px solid rgba(255,255,255,0.1); }
    .sidebar-nav { flex: 1; padding: 16px 0; }
    .sidebar-nav a {
      display: flex; align-items: center; gap: 10px;
      padding: 10px 24px; color: rgba(232,220,200,0.75); font-size: 0.9rem;
      transition: background 0.15s, color 0.15s;
    }
    .sidebar-nav a:hover, .sidebar-nav a.active { background: rgba(212,168,67,0.12); color: #d4a843; text-decoration: none; }
    .sidebar-footer { padding: 16px 24px; border-top: 1px solid rgba(255,255,255,0.1); }
    .sidebar-footer a { color: rgba(232,220,200,0.5); font-size: 0.85rem; }

    .main { margin-left: 220px; min-height: 100vh; }
    .topbar { background: #fff; border-bottom: 1px solid rgba(28,18,8,0.1); padding: 16px 32px; display: flex; align-items: center; justify-content: space-between; }
    .topbar h1 { font-size: 1.2rem; font-weight: 600; }
    .content { padding: 32px; }

    .card { background: #fff; border-radius: 12px; border: 1px solid rgba(28,18,8,0.08); padding: 24px; margin-bottom: 24px; }
    .card-title { font-size: 1rem; font-weight: 600; margin-bottom: 16px; color: #1c1208; }

    table { width: 100%; border-collapse: collapse; }
    th { text-align: left; padding: 10px 12px; font-size: 0.8rem; text-transform: uppercase; letter-spacing: 0.05em; color: rgba(28,18,8,0.5); border-bottom: 2px solid rgba(28,18,8,0.08); }
    td { padding: 12px; border-bottom: 1px solid rgba(28,18,8,0.06); font-size: 0.9rem; }
    tr:last-child td { border-bottom: none; }
    tr:hover td { background: rgba(184,137,46,0.04); }

    .badge { display: inline-block; padding: 3px 10px; border-radius: 20px; font-size: 0.78rem; font-weight: 500; }
    .btn { display: inline-flex; align-items: center; gap: 6px; padding: 8px 16px; border-radius: 8px; font-size: 0.875rem; font-weight: 500; cursor: pointer; border: none; transition: opacity 0.2s; }
    .btn:hover { opacity: 0.85; text-decoration: none; }
    .btn-primary { background: #d4a843; color: #1c1208; }
    .btn-danger  { background: #dc3545; color: #fff; }
    .btn-outline { background: transparent; border: 1px solid rgba(28,18,8,0.2); color: #1c1208; }

    .form-group { margin-bottom: 16px; }
    .form-label { display: block; font-size: 0.85rem; font-weight: 500; margin-bottom: 6px; color: rgba(28,18,8,0.7); }
    .form-input, .form-select, .form-textarea {
      width: 100%; padding: 9px 12px; border: 1px solid rgba(28,18,8,0.2); border-radius: 8px;
      font-size: 0.9rem; font-family: inherit; background: #faf6f0; outline: none;
      transition: border-color 0.2s;
    }
    .form-input:focus, .form-select:focus, .form-textarea:focus { border-color: #d4a843; }
    .form-textarea { resize: vertical; min-height: 80px; }

    .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(160px, 1fr)); gap: 16px; margin-bottom: 24px; }
    .stat-card { background: #fff; border-radius: 12px; border: 1px solid rgba(28,18,8,0.08); padding: 20px; }
    .stat-num { font-size: 2rem; font-weight: 700; color: #d4a843; line-height: 1; }
    .stat-label { font-size: 0.8rem; color: rgba(28,18,8,0.5); margin-top: 4px; }

    .alert { padding: 12px 16px; border-radius: 8px; margin-bottom: 16px; font-size: 0.9rem; }
    .alert-success { background: rgba(46,184,125,0.1); border: 1px solid rgba(46,184,125,0.3); color: #1a6644; }
    .alert-error   { background: rgba(220,53,69,0.1);  border: 1px solid rgba(220,53,69,0.3);  color: #721c24; }
  </style>
</head>
<body>

<div class="sidebar">
  <div class="sidebar-logo">SikretSweet</div>
  <nav class="sidebar-nav">
    <a href="/admin/dashboard.php" <?= ($active_page??'')==='dashboard' ? 'class="active"' : '' ?>>📊 Дашборд</a>
    <a href="/admin/orders.php"    <?= ($active_page??'')==='orders'    ? 'class="active"' : '' ?>>📦 Заказы</a>
    <a href="/admin/products.php"  <?= ($active_page??'')==='products'  ? 'class="active"' : '' ?>>🛍 Товары</a>
    <a href="/admin/customers.php" <?= ($active_page??'')==='customers' ? 'class="active"' : '' ?>>👥 Покупатели</a>
  </nav>
  <div class="sidebar-footer">
    <a href="/admin/logout.php">Выйти</a>
  </div>
</div>

<div class="main">
  <div class="topbar">
    <h1><?= h($page_title ?? '') ?></h1>
    <a href="/" target="_blank" style="font-size:0.85rem;color:rgba(28,18,8,0.5);">↗ Открыть сайт</a>
  </div>
  <div class="content">
