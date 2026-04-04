<?php
require_once __DIR__ . '/includes/auth_check.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

$db = db();
$id = (int)($_GET['id'] ?? 0);
$order = $db->prepare("SELECT * FROM orders WHERE id = ?");
$order->execute([$id]);
$order = $order->fetch();
if (!$order) { header('Location: /admin/orders.php'); exit; }

$items = $db->prepare("SELECT * FROM order_items WHERE order_id = ?");
$items->execute([$id]);
$items = $items->fetchAll();

$msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['status'])) {
    $allowed = ['new','processing','shipped','delivered','cancelled'];
    $new_status = $_POST['status'];
    if (in_array($new_status, $allowed)) {
        $db->prepare("UPDATE orders SET status = ? WHERE id = ?")->execute([$new_status, $id]);
        $order['status'] = $new_status;

        // Уведомление в Telegram
        $label = order_status_label($new_status);
        send_telegram("📋 Заказ #$id обновлён\nСтатус: *$label*\nПокупатель: {$order['name']} {$order['phone']}");
        $msg = 'Статус обновлён';
    }
}

$page_title  = "Заказ #$id";
$active_page = 'orders';
require __DIR__ . '/includes/header.php';
?>

<?php if ($msg): ?><div class="alert alert-success"><?= h($msg) ?></div><?php endif; ?>

<div style="display:grid;grid-template-columns:1fr 320px;gap:24px;align-items:start;">

  <div>
    <div class="card">
      <div class="card-title">Покупатель</div>
      <table>
        <tr><td style="color:rgba(28,18,8,0.5);width:140px;">Имя</td><td><?= h($order['name']) ?></td></tr>
        <tr><td style="color:rgba(28,18,8,0.5);">Телефон</td><td><a href="tel:<?= h($order['phone']) ?>"><?= h($order['phone']) ?></a></td></tr>
        <tr><td style="color:rgba(28,18,8,0.5);">Доставка</td><td><?= h($order['delivery']) ?></td></tr>
        <tr><td style="color:rgba(28,18,8,0.5);">Упаковка</td><td><?= $order['gift'] ? 'Да' : 'Нет' ?></td></tr>
        <tr><td style="color:rgba(28,18,8,0.5);">Комментарий</td><td><?= h($order['comment'] ?: '—') ?></td></tr>
        <tr><td style="color:rgba(28,18,8,0.5);">Дата</td><td><?= date('d.m.Y H:i', strtotime($order['created_at'])) ?></td></tr>
      </table>
    </div>

    <div class="card">
      <div class="card-title">Состав заказа</div>
      <table>
        <thead><tr><th>Товар</th><th>Кол-во</th><th>Цена</th></tr></thead>
        <tbody>
          <?php foreach ($items as $item): ?>
          <tr>
            <td><?= h($item['product_name']) ?></td>
            <td><?= $item['qty'] ?></td>
            <td><?= number_format($item['price'], 0, '.', ' ') ?> ₽</td>
          </tr>
          <?php endforeach; ?>
          <tr style="font-weight:600;">
            <td colspan="2">Итого</td>
            <td><?= number_format($order['total'], 0, '.', ' ') ?> ₽</td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>

  <div class="card">
    <div class="card-title">Статус заказа</div>
    <div style="margin-bottom:16px;">
      <span class="badge" style="font-size:0.9rem;padding:6px 14px;background:<?= order_status_color($order['status']) ?>22;color:<?= order_status_color($order['status']) ?>">
        <?= order_status_label($order['status']) ?>
      </span>
    </div>
    <form method="POST">
      <div class="form-group">
        <label class="form-label">Изменить статус</label>
        <select name="status" class="form-select">
          <option value="new"        <?= $order['status']==='new'        ? 'selected' : '' ?>>Новый</option>
          <option value="processing" <?= $order['status']==='processing' ? 'selected' : '' ?>>В работе</option>
          <option value="shipped"    <?= $order['status']==='shipped'    ? 'selected' : '' ?>>Отправлен</option>
          <option value="delivered"  <?= $order['status']==='delivered'  ? 'selected' : '' ?>>Доставлен</option>
          <option value="cancelled"  <?= $order['status']==='cancelled'  ? 'selected' : '' ?>>Отменён</option>
        </select>
      </div>
      <button type="submit" class="btn btn-primary" style="width:100%;justify-content:center;">Сохранить</button>
    </form>
    <div style="margin-top:16px;">
      <a href="/admin/orders.php" class="btn btn-outline" style="width:100%;justify-content:center;">← Все заказы</a>
    </div>
  </div>

</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
