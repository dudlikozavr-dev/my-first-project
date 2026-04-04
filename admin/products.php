<?php
require_once __DIR__ . '/includes/auth_check.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

$db = db();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
    $db->prepare("DELETE FROM products WHERE id = ?")->execute([(int)$_POST['delete_id']]);
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['toggle_id'])) {
    $db->prepare("UPDATE products SET active = 1 - active WHERE id = ?")->execute([(int)$_POST['toggle_id']]);
}

$products = $db->query("SELECT * FROM products ORDER BY sort_order, id")->fetchAll();

$page_title  = 'Товары';
$active_page = 'products';
require __DIR__ . '/includes/header.php';
?>

<div style="margin-bottom:20px;">
  <a href="/admin/product_edit.php" class="btn btn-primary">+ Добавить товар</a>
</div>

<div class="card">
  <table>
    <thead>
      <tr><th>#</th><th>Название</th><th>Цена</th><th>Статус</th><th></th></tr>
    </thead>
    <tbody>
      <?php foreach ($products as $p): ?>
      <tr>
        <td><?= $p['id'] ?></td>
        <td><?= h($p['name']) ?></td>
        <td><?= number_format($p['price'], 0, '.', ' ') ?> ₽</td>
        <td>
          <form method="POST" style="display:inline;">
            <input type="hidden" name="toggle_id" value="<?= $p['id'] ?>">
            <button type="submit" class="badge" style="border:none;cursor:pointer;background:<?= $p['active'] ? '#2eb87d22' : '#dc354522' ?>;color:<?= $p['active'] ? '#1a6644' : '#721c24' ?>">
              <?= $p['active'] ? 'Активен' : 'Скрыт' ?>
            </button>
          </form>
        </td>
        <td style="display:flex;gap:8px;">
          <a href="/admin/product_edit.php?id=<?= $p['id'] ?>" class="btn btn-outline" style="padding:4px 10px;font-size:0.8rem;">Изменить</a>
          <form method="POST" onsubmit="return confirm('Удалить товар?')">
            <input type="hidden" name="delete_id" value="<?= $p['id'] ?>">
            <button type="submit" class="btn btn-danger" style="padding:4px 10px;font-size:0.8rem;">Удалить</button>
          </form>
        </td>
      </tr>
      <?php endforeach; ?>
      <?php if (!$products): ?>
      <tr><td colspan="5" style="text-align:center;color:rgba(28,18,8,0.4);padding:24px;">Товаров нет — <a href="/admin/product_edit.php">добавить первый</a></td></tr>
      <?php endif; ?>
    </tbody>
  </table>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
