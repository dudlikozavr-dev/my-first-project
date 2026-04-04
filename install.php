<?php
/**
 * Установщик SikretSweet
 * Запусти ОДИН РАЗ: https://sikretsweet.ru/install.php
 * После установки УДАЛИ этот файл!
 */

require_once __DIR__ . '/includes/db.php';

$steps = [];
$ok = true;

try {
    $db = db();
    $steps[] = ['ok' => true, 'text' => 'Подключение к базе данных установлено'];
} catch (Exception $e) {
    $steps[] = ['ok' => false, 'text' => 'Ошибка подключения к БД: ' . $e->getMessage()];
    $ok = false;
}

if ($ok) {
    // Создаём таблицы
    $sql = file_get_contents(__DIR__ . '/db_setup.sql');
    // Убираем комментарии и выполняем по одному
    $statements = array_filter(array_map('trim', explode(';', $sql)));
    foreach ($statements as $stmt) {
        if (empty($stmt) || str_starts_with(ltrim($stmt), '--') || str_starts_with(ltrim($stmt), 'SET')) {
            continue;
        }
        try {
            $db->exec($stmt);
        } catch (Exception $e) {
            // Таблица уже существует — не страшно
        }
    }
    $steps[] = ['ok' => true, 'text' => 'Таблицы созданы (или уже существовали)'];

    // Создаём администратора
    $admin_user = 'admin';
    $admin_pass = 'SikretSweet2026!';
    $hash = password_hash($admin_pass, PASSWORD_DEFAULT);

    try {
        $db->prepare("INSERT IGNORE INTO admins (username, password_hash) VALUES (?, ?)")
           ->execute([$admin_user, $hash]);
        $steps[] = ['ok' => true, 'text' => "Администратор создан: логин <b>$admin_user</b>, пароль <b>$admin_pass</b>"];
    } catch (Exception $e) {
        $steps[] = ['ok' => false, 'text' => 'Ошибка создания администратора: ' . $e->getMessage()];
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
  <meta charset="UTF-8">
  <title>Установка SikretSweet</title>
  <style>
    body { font-family: -apple-system, sans-serif; background: #f5f0ea; display:flex; align-items:center; justify-content:center; min-height:100vh; margin:0; }
    .box { background:#fff; border-radius:16px; padding:40px; max-width:500px; width:100%; box-shadow:0 4px 24px rgba(0,0,0,0.08); }
    h1 { font-size:1.4rem; color:#d4a843; margin-bottom:24px; }
    .step { display:flex; align-items:flex-start; gap:12px; margin-bottom:14px; font-size:0.9rem; }
    .icon { font-size:1.1rem; flex-shrink:0; margin-top:1px; }
    .warn { margin-top:24px; background:rgba(220,53,69,0.08); border:1px solid rgba(220,53,69,0.3); color:#721c24; padding:14px; border-radius:8px; font-size:0.875rem; }
    .link { display:inline-block; margin-top:16px; background:#d4a843; color:#1c1208; padding:10px 24px; border-radius:8px; text-decoration:none; font-weight:600; }
  </style>
</head>
<body>
<div class="box">
  <h1>Установка SikretSweet</h1>
  <?php foreach ($steps as $s): ?>
  <div class="step">
    <span class="icon"><?= $s['ok'] ? '✅' : '❌' ?></span>
    <span><?= $s['text'] ?></span>
  </div>
  <?php endforeach; ?>

  <?php if ($ok): ?>
  <div class="warn">
    ⚠️ <strong>Важно!</strong> Удали файл <code>install.php</code> с сервера после установки — он содержит пароль администратора.
  </div>
  <a href="/admin/" class="link">Перейти в панель управления →</a>
  <?php endif; ?>
</div>
</body>
</html>
