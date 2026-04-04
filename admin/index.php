<?php
session_start();
if (!empty($_SESSION['admin_id'])) {
    header('Location: /admin/dashboard.php');
    exit;
}

require_once __DIR__ . '/../includes/db.php';

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    $stmt = db()->prepare("SELECT id, password_hash FROM admins WHERE username = ?");
    $stmt->execute([$username]);
    $admin = $stmt->fetch();

    if ($admin && password_verify($password, $admin['password_hash'])) {
        $_SESSION['admin_id'] = $admin['id'];
        header('Location: /admin/dashboard.php');
        exit;
    }
    $error = 'Неверный логин или пароль';
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Вход — SikretSweet Admin</title>
  <style>
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
    body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; background: #f5f0ea; display: flex; align-items: center; justify-content: center; min-height: 100vh; }
    .login-box { background: #fff; border-radius: 16px; padding: 40px; width: 100%; max-width: 380px; box-shadow: 0 4px 24px rgba(28,18,8,0.08); }
    .logo { font-size: 1.4rem; font-weight: 700; color: #d4a843; margin-bottom: 8px; }
    .subtitle { color: rgba(28,18,8,0.5); font-size: 0.9rem; margin-bottom: 32px; }
    .form-group { margin-bottom: 16px; }
    label { display: block; font-size: 0.85rem; font-weight: 500; margin-bottom: 6px; color: rgba(28,18,8,0.7); }
    input { width: 100%; padding: 10px 14px; border: 1px solid rgba(28,18,8,0.2); border-radius: 8px; font-size: 1rem; font-family: inherit; background: #faf6f0; outline: none; }
    input:focus { border-color: #d4a843; }
    .btn { width: 100%; padding: 11px; background: #d4a843; color: #1c1208; border: none; border-radius: 8px; font-size: 1rem; font-weight: 600; cursor: pointer; margin-top: 8px; }
    .btn:hover { background: #c49535; }
    .error { background: rgba(220,53,69,0.1); border: 1px solid rgba(220,53,69,0.3); color: #721c24; padding: 10px 14px; border-radius: 8px; font-size: 0.875rem; margin-bottom: 16px; }
  </style>
</head>
<body>
  <div class="login-box">
    <div class="logo">SikretSweet</div>
    <div class="subtitle">Панель управления</div>
    <?php if ($error): ?>
      <div class="error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    <form method="POST">
      <div class="form-group">
        <label>Логин</label>
        <input type="text" name="username" autofocus required>
      </div>
      <div class="form-group">
        <label>Пароль</label>
        <input type="password" name="password" required>
      </div>
      <button type="submit" class="btn">Войти</button>
    </form>
  </div>
</body>
</html>
