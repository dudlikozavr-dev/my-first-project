<?php
require_once __DIR__ . '/includes/auth_check.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

$db = db();
$id = (int)($_GET['id'] ?? 0);
$product = $id ? $db->prepare("SELECT * FROM products WHERE id=?") : null;
if ($product) { $product->execute([$id]); $product = $product->fetch(); }

$msg = $error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name  = trim($_POST['name']  ?? '');
    $price = (float)($_POST['price'] ?? 0);
    $desc  = trim($_POST['description'] ?? '');
    $sort  = (int)($_POST['sort_order'] ?? 0);
    $active = isset($_POST['active']) ? 1 : 0;

    // Фото — список путей через новую строку
    $photos_raw = trim($_POST['photos'] ?? '');
    $photos = array_filter(array_map('trim', explode("\n", $photos_raw)));
    $photos_json = json_encode(array_values($photos));

    if (!$name) {
        $error = 'Укажите название товара';
    } else {
        if ($id) {
            $db->prepare("UPDATE products SET name=?,price=?,description=?,photos=?,active=?,sort_order=? WHERE id=?")
               ->execute([$name, $price, $desc, $photos_json, $active, $sort, $id]);
        } else {
            $db->prepare("INSERT INTO products (name,price,description,photos,active,sort_order) VALUES (?,?,?,?,?,?)")
               ->execute([$name, $price, $desc, $photos_json, $active, $sort]);
            $id = (int)$db->lastInsertId();
        }
        header('Location: /admin/products.php');
        exit;
    }
}

$photos_value = '';
if ($product && $product['photos']) {
    $arr = json_decode($product['photos'], true) ?? [];
    $photos_value = implode("\n", $arr);
}

$page_title  = $id ? 'Редактировать товар' : 'Новый товар';
$active_page = 'products';
require __DIR__ . '/includes/header.php';
?>

<div style="max-width:640px;">
  <?php if ($error): ?><div class="alert alert-error"><?= h($error) ?></div><?php endif; ?>

  <div class="card">
    <form method="POST">
      <div class="form-group">
        <label class="form-label">Название *</label>
        <input type="text" name="name" class="form-input" value="<?= h($product['name'] ?? '') ?>" required>
      </div>
      <div class="form-group">
        <label class="form-label">Цена (₽)</label>
        <input type="number" name="price" class="form-input" value="<?= $product['price'] ?? 0 ?>" min="0" step="1">
      </div>
      <div class="form-group">
        <label class="form-label">Описание</label>
        <textarea name="description" class="form-textarea"><?= h($product['description'] ?? '') ?></textarea>
      </div>
      <div class="form-group">
        <label class="form-label">Пути к фото (каждое с новой строки)</label>
        <textarea name="photos" class="form-textarea" style="min-height:120px;" placeholder="Halaty/141/141.jpg&#10;Halaty/141/IMG_2196.jpg"><?= h($photos_value) ?></textarea>
        <div style="font-size:0.8rem;color:rgba(28,18,8,0.5);margin-top:4px;">Укажи пути к фото относительно корня сайта</div>
      </div>
      <div class="form-group">
        <label class="form-label">Порядок сортировки</label>
        <input type="number" name="sort_order" class="form-input" value="<?= $product['sort_order'] ?? 0 ?>">
      </div>
      <div class="form-group">
        <label style="display:flex;align-items:center;gap:8px;cursor:pointer;">
          <input type="checkbox" name="active" <?= ($product['active'] ?? 1) ? 'checked' : '' ?>>
          Показывать на сайте
        </label>
      </div>
      <div style="display:flex;gap:12px;">
        <button type="submit" class="btn btn-primary">Сохранить</button>
        <a href="/admin/products.php" class="btn btn-outline">Отмена</a>
      </div>
    </form>
  </div>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
