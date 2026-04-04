<?php
require_once __DIR__ . '/includes/auth_check.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

$db = db();

$total_orders    = $db->query("SELECT COUNT(*) FROM orders")->fetchColumn();
$new_orders      = $db->query("SELECT COUNT(*) FROM orders WHERE status='new'")->fetchColumn();
$total_revenue   = $db->query("SELECT COALESCE(SUM(total),0) FROM orders WHERE status != 'cancelled'")->fetchColumn();
$total_customers = $db->query("SELECT COUNT(*) FROM customers")->fetchColumn();

$recent_orders = $db->query("
    SELECT id, name, phone, total, status, created_at
    FROM orders ORDER BY created_at DESC LIMIT 10
")->fetchAll();

$page_title  = 'Дашборд';
$active_page = 'dashboard';
require __DIR__ . '/includes/header.php';
?>

<div class="stats-grid">
  <div class="stat-card">
    <div class="stat-num"><?= $new_orders ?></div>
    <div class="stat-label">Новых заказов</div>
  </div>
  <div class="stat-card">
    <div class="stat-num"><?= $total_orders ?></div>
    <div class="stat-label">Всего заказов</div>
  </div>
  <div class="stat-card">
    <div class="stat-num"><?= number_format($total_revenue, 0, '.', ' ') ?> ₽</div>
    <div class="stat-label">Выручка</div>
  </div>
  <div class="stat-card">
    <div class="stat-num"><?= $total_customers ?></div>
    <div class="stat-label">Покупателей</div>
  </div>
</div>

<div class="card">
  <div class="card-title">Последние заказы</div>
  <table>
    <thead>
      <tr><th>#</th><th>Покупатель</th><th>Телефон</th><th>Сумма</th><th>Статус</th><th>Дата</th><th></th></tr>
    </thead>
    <tbody>
      <?php foreach ($recent_orders as $o): ?>
      <tr>
        <td><?= $o['id'] ?></td>
        <td><?= h($o['name']) ?></td>
        <td><?= h($o['phone']) ?></td>
        <td><?= number_format($o['total'], 0, '.', ' ') ?> ₽</td>
        <td><span class="badge" style="background:<?= order_status_color($o['status']) ?>22;color:<?= order_status_color($o['status']) ?>"><?= order_status_label($o['status']) ?></span></td>
        <td><?= date('d.m.Y H:i', strtotime($o['created_at'])) ?></td>
        <td><a href="/admin/order.php?id=<?= $o['id'] ?>" class="btn btn-outline" style="padding:4px 10px;font-size:0.8rem;">Открыть</a></td>
      </tr>
      <?php endforeach; ?>
      <?php if (!$recent_orders): ?>
      <tr><td colspan="7" style="text-align:center;color:rgba(28,18,8,0.4);padding:24px;">Заказов пока нет</td></tr>
      <?php endif; ?>
    </tbody>
  </table>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
