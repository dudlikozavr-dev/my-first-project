<?php
session_start();
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

$db  = db();
$msg = $error = '';
$customer = null;

// Выход
if (isset($_GET['logout'])) {
    unset($_SESSION['customer_id']);
    header('Location: /account/');
    exit;
}

// Вход / Регистрация
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $phone  = trim($_POST['phone'] ?? '');
    $name   = trim($_POST['name']  ?? '');
    $pass   = $_POST['password']   ?? '';

    if ($action === 'login') {
        $stmt = $db->prepare("SELECT * FROM customers WHERE phone = ?");
        $stmt->execute([$phone]);
        $row = $stmt->fetch();
        if ($row && password_verify($pass, $row['password_hash'])) {
            $_SESSION['customer_id'] = $row['id'];
            header('Location: /account/');
            exit;
        }
        $error = 'Неверный телефон или пароль';

    } elseif ($action === 'register') {
        $exists = $db->prepare("SELECT id FROM customers WHERE phone = ?");
        $exists->execute([$phone]);
        if ($exists->fetch()) {
            $error = 'Этот номер уже зарегистрирован. Войдите.';
        } elseif (strlen($pass) < 6) {
            $error = 'Пароль должен быть не менее 6 символов';
        } else {
            $hash = password_hash($pass, PASSWORD_DEFAULT);
            $db->prepare("INSERT INTO customers (name, phone, password_hash) VALUES (?,?,?)")
               ->execute([$name, $phone, $hash]);
            $_SESSION['customer_id'] = (int)$db->lastInsertId();
            header('Location: /account/');
            exit;
        }
    }
}

// Загрузка данных авторизованного покупателя
if (!empty($_SESSION['customer_id'])) {
    $stmt = $db->prepare("SELECT * FROM customers WHERE id = ?");
    $stmt->execute([$_SESSION['customer_id']]);
    $customer = $stmt->fetch();

    if ($customer) {
        $orders = $db->prepare("
            SELECT o.*, GROUP_CONCAT(oi.product_name SEPARATOR ', ') as items_str
            FROM orders o
            LEFT JOIN order_items oi ON oi.order_id = o.id
            WHERE o.customer_id = ?
            GROUP BY o.id
            ORDER BY o.created_at DESC
        ");
        $orders->execute([$customer['id']]);
        $orders = $orders->fetchAll();
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Личный кабинет — SikretSweet</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;500&family=Jost:wght@300;400;500;600&display=swap" rel="stylesheet">
  <style>
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
    body { font-family: 'Jost', sans-serif; background: #faf6f0; color: #1c1208; font-size: 1rem; line-height: 1.6; }
    a { color: #b8892e; text-decoration: none; }
    a:hover { text-decoration: underline; }

    nav { background: rgba(250,246,240,0.96); backdrop-filter: blur(12px); border-bottom: 1px solid rgba(28,18,8,0.1); padding: 0 clamp(16px,4vw,48px); }
    .nav-inner { max-width: 900px; margin: 0 auto; height: 60px; display: flex; align-items: center; justify-content: space-between; }
    .nav-logo { font-family: 'Playfair Display', serif; font-size: 1.3rem; color: #b8892e; }
    .nav-links { display: flex; gap: 20px; font-size: 0.875rem; color: rgba(28,18,8,0.5); }

    .wrap { max-width: 900px; margin: 0 auto; padding: clamp(32px,6vw,64px) clamp(16px,4vw,48px); }
    .page-title { font-family: 'Playfair Display', serif; font-size: clamp(1.6rem,4vw,2.4rem); margin-bottom: 32px; }

    .card { background: #fff; border-radius: 16px; border: 1px solid rgba(28,18,8,0.08); padding: clamp(20px,4vw,36px); margin-bottom: 24px; box-shadow: 0 2px 12px rgba(28,18,8,0.05); }
    .card-title { font-weight: 600; font-size: 1rem; margin-bottom: 16px; }

    .tabs { display: flex; gap: 0; border-bottom: 2px solid rgba(28,18,8,0.1); margin-bottom: 24px; }
    .tab { padding: 10px 20px; cursor: pointer; font-size: 0.9rem; color: rgba(28,18,8,0.5); border-bottom: 2px solid transparent; margin-bottom: -2px; transition: color 0.2s, border-color 0.2s; }
    .tab.active { color: #b8892e; border-bottom-color: #b8892e; font-weight: 500; }
    .tab-content { display: none; }
    .tab-content.active { display: block; }

    .form-group { margin-bottom: 16px; }
    .form-label { display: block; font-size: 0.85rem; font-weight: 500; margin-bottom: 6px; color: rgba(28,18,8,0.6); }
    .form-input { width: 100%; padding: 10px 14px; border: 1px solid rgba(28,18,8,0.15); border-radius: 8px; font-size: 1rem; font-family: inherit; background: #faf6f0; outline: none; transition: border-color 0.2s; }
    .form-input:focus { border-color: rgba(184,137,46,0.5); }
    .btn { display: inline-flex; align-items: center; justify-content: center; padding: 10px 24px; border-radius: 50px; font-family: 'Jost', sans-serif; font-size: 0.95rem; font-weight: 500; cursor: pointer; border: none; transition: opacity 0.2s; }
    .btn-primary { background: linear-gradient(135deg,#d4a843,#c9748a); color: #0d0a0f; }
    .btn-primary:hover { opacity: 0.85; }
    .btn-outline { background: transparent; border: 1px solid rgba(184,137,46,0.4); color: #b8892e; }

    .alert { padding: 12px 16px; border-radius: 8px; margin-bottom: 16px; font-size: 0.9rem; }
    .alert-error { background: rgba(220,53,69,0.08); border: 1px solid rgba(220,53,69,0.3); color: #721c24; }

    .badge { display: inline-block; padding: 3px 10px; border-radius: 20px; font-size: 0.78rem; font-weight: 500; }

    table { width: 100%; border-collapse: collapse; }
    th { text-align: left; padding: 8px 12px; font-size: 0.78rem; text-transform: uppercase; letter-spacing: 0.05em; color: rgba(28,18,8,0.4); border-bottom: 1px solid rgba(28,18,8,0.08); }
    td { padding: 12px; border-bottom: 1px solid rgba(28,18,8,0.05); font-size: 0.9rem; }
    tr:last-child td { border-bottom: none; }
  </style>
</head>
<body>

<nav>
  <div class="nav-inner">
    <a href="/" class="nav-logo">SikretSweet</a>
    <div class="nav-links">
      <?php if ($customer): ?>
        <span><?= h($customer['name'] ?: $customer['phone']) ?></span>
        <a href="?logout=1">Выйти</a>
      <?php else: ?>
        <a href="/">На главную</a>
      <?php endif; ?>
    </div>
  </div>
</nav>

<div class="wrap">

<?php if ($customer): ?>

  <h1 class="page-title">Привет, <?= h($customer['name'] ?: 'покупатель') ?>!</h1>

  <div class="card">
    <div class="card-title">Мои заказы</div>
    <?php if ($orders): ?>
    <table>
      <thead><tr><th>#</th><th>Состав</th><th>Сумма</th><th>Статус</th><th>Дата</th></tr></thead>
      <tbody>
        <?php foreach ($orders as $o): ?>
        <tr>
          <td><?= $o['id'] ?></td>
          <td style="max-width:240px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"><?= h($o['items_str'] ?? '—') ?></td>
          <td><?= number_format($o['total'], 0, '.', ' ') ?> ₽</td>
          <td>
            <span class="badge" style="background:<?= order_status_color($o['status']) ?>22;color:<?= order_status_color($o['status']) ?>">
              <?= order_status_label($o['status']) ?>
            </span>
          </td>
          <td><?= date('d.m.Y', strtotime($o['created_at'])) ?></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
    <?php else: ?>
    <p style="color:rgba(28,18,8,0.4);text-align:center;padding:24px 0;">Заказов пока нет — <a href="/#catalog">перейти в каталог</a></p>
    <?php endif; ?>
  </div>

<?php else: ?>

  <h1 class="page-title">Личный кабинет</h1>

  <?php if ($error): ?><div class="alert alert-error"><?= h($error) ?></div><?php endif; ?>

  <div class="card" style="max-width:420px;">
    <div class="tabs">
      <div class="tab active" onclick="switchTab('login')">Войти</div>
      <div class="tab" onclick="switchTab('register')">Регистрация</div>
    </div>

    <div id="tab-login" class="tab-content active">
      <form method="POST">
        <input type="hidden" name="action" value="login">
        <div class="form-group">
          <label class="form-label">Телефон</label>
          <input type="tel" name="phone" class="form-input" placeholder="+7 900 000 00 00" required>
        </div>
        <div class="form-group">
          <label class="form-label">Пароль</label>
          <input type="password" name="password" class="form-input" required>
        </div>
        <button type="submit" class="btn btn-primary" style="width:100%;">Войти</button>
      </form>
    </div>

    <div id="tab-register" class="tab-content">
      <form method="POST">
        <input type="hidden" name="action" value="register">
        <div class="form-group">
          <label class="form-label">Ваше имя</label>
          <input type="text" name="name" class="form-input" placeholder="Как к вам обращаться">
        </div>
        <div class="form-group">
          <label class="form-label">Телефон</label>
          <input type="tel" name="phone" class="form-input" placeholder="+7 900 000 00 00" required>
        </div>
        <div class="form-group">
          <label class="form-label">Пароль</label>
          <input type="password" name="password" class="form-input" placeholder="Минимум 6 символов" required>
        </div>
        <button type="submit" class="btn btn-primary" style="width:100%;">Зарегистрироваться</button>
      </form>
    </div>
  </div>

<?php endif; ?>
</div>

<script>
function switchTab(name) {
  document.querySelectorAll('.tab').forEach((t,i) => t.classList.toggle('active', ['login','register'][i]===name));
  document.querySelectorAll('.tab-content').forEach(c => c.classList.toggle('active', c.id==='tab-'+name));
}
<?php if ($error && isset($_POST['action'])): ?>
switchTab('<?= h($_POST['action']) ?>');
<?php endif; ?>
</script>
</body>
</html>
