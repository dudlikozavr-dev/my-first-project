<?php
require_once __DIR__ . '/includes/auth_check.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

$db     = db();
$status = $_GET['status'] ?? '';
$where  = $status ? "WHERE status = " . $db->quote($status) : '';
$orders = $db->query("SELECT * FROM orders $where ORDER BY created_at DESC")->fetchAll();

$page_title  = 'Заказы';
$active_page = 'orders';
require __DIR__ . '/includes/header.php';
?>

<div style="display:flex;gap:8px;margin-bottom:20px;flex-wrap:wrap;">
  <?php
  $statuses = ['' => 'Все', 'new' => 'Новые', 'processing' => 'В работе', 'shipped' => 'Отправлены', 'delivered' => 'Доставлены', 'cancelled' => 'Отменены'];
  foreach ($statuses as $key => $label):
  ?>
  <a href="?status=<?= $key ?>" class="btn <?= $status===$key ? 'btn-primary' : 'btn-outline' ?>"><?= $label ?></a>
  <?php endforeach; ?>
</div>

<div class="card">
  <table>
    <thead>
      <tr><th>#</th><th>Покупатель</th><th>Телефон</th><th>Доставка</th><th>Сумма</th><th>Статус</th><th>Дата</th><th></th></tr>
    </thead>
    <tbody>
      <?php foreach ($orders as $o): ?>
      <tr>
        <td><?= $o['id'] ?></td>
        <td><?= h($o['name']) ?></td>
        <td><a href="tel:<?= h($o['phone']) ?>"><?= h($o['phone']) ?></a></td>
        <td><?= h($o['delivery']) ?></td>
        <td><?= number_format($o['total'], 0, '.', ' ') ?> ₽</td>
        <td><span class="badge" style="background:<?= order_status_color($o['status']) ?>22;color:<?= order_status_color($o['status']) ?>"><?= order_status_label($o['status']) ?></span></td>
        <td><?= date('d.m.Y H:i', strtotime($o['created_at'])) ?></td>
        <td><a href="/admin/order.php?id=<?= $o['id'] ?>" class="btn btn-outline" style="padding:4px 10px;font-size:0.8rem;">Открыть</a></td>
      </tr>
      <?php endforeach; ?>
      <?php if (!$orders): ?>
      <tr><td colspan="8" style="text-align:center;color:rgba(28,18,8,0.4);padding:24px;">Заказов нет</td></tr>
      <?php endif; ?>
    </tbody>
  </table>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
