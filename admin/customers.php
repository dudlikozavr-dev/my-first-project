<?php
require_once __DIR__ . '/includes/auth_check.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

$db = db();
$customers = $db->query("
    SELECT c.*, COUNT(o.id) as orders_count, COALESCE(SUM(o.total),0) as total_spent
    FROM customers c
    LEFT JOIN orders o ON o.customer_id = c.id
    GROUP BY c.id
    ORDER BY c.created_at DESC
")->fetchAll();

$page_title  = 'Покупатели';
$active_page = 'customers';
require __DIR__ . '/includes/header.php';
?>

<div class="card">
  <table>
    <thead>
      <tr><th>#</th><th>Имя</th><th>Телефон</th><th>Email</th><th>Заказов</th><th>Потрачено</th><th>Дата</th></tr>
    </thead>
    <tbody>
      <?php foreach ($customers as $c): ?>
      <tr>
        <td><?= $c['id'] ?></td>
        <td><?= h($c['name'] ?? '—') ?></td>
        <td><?= h($c['phone'] ?? '—') ?></td>
        <td><?= h($c['email'] ?? '—') ?></td>
        <td><?= $c['orders_count'] ?></td>
        <td><?= number_format($c['total_spent'], 0, '.', ' ') ?> ₽</td>
        <td><?= date('d.m.Y', strtotime($c['created_at'])) ?></td>
      </tr>
      <?php endforeach; ?>
      <?php if (!$customers): ?>
      <tr><td colspan="7" style="text-align:center;color:rgba(28,18,8,0.4);padding:24px;">Покупателей пока нет</td></tr>
      <?php endif; ?>
    </tbody>
  </table>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
