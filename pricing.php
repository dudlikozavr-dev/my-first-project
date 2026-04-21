<?php
/**
 * pricing.php — динамическое отображение товаров из API mini-app
 */

// Функция чтения товаров с кешем
function fetch_products() {
    $cache_file = sys_get_temp_dir() . '/mia_products_cache.json';
    $cache_ttl = 60; // секунды

    // Проверка кеша
    if (file_exists($cache_file) && time() - filemtime($cache_file) < $cache_ttl) {
        $json = file_get_contents($cache_file);
        $data = json_decode($json, true);
        if ($data && isset($data['products'])) {
            return $data['products'];
        }
    }

    // Запрос к API через curl
    $json = null;
    $error = '';

    if (function_exists('curl_init')) {
        $api_url = 'https://app.sikretsweet.ru/api/products';
        $ch = curl_init($api_url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 5,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
        ]);
        $json = curl_exec($ch);
        if ($json === false) {
            $error = 'curl_error: ' . curl_error($ch);
            error_log('API fetch failed: ' . $error);
        }
        curl_close($ch);
    } else {
        $error = 'curl_not_available';
        error_log('curl не установлен на хостинге');
    }

    // Если curl не работает — пробуем file_get_contents
    if (!$json && ini_get('allow_url_fopen')) {
        $api_url = 'https://app.sikretsweet.ru/api/products';
        $opts = ['http' => ['method' => 'GET', 'timeout' => 5]];
        $context = stream_context_create($opts);
        $json = file_get_contents($api_url, false, $context);
        if ($json === false) {
            error_log('file_get_contents failed');
        }
    } elseif (!$json && !ini_get('allow_url_fopen')) {
        error_log('allow_url_fopen отключен');
    }

    if ($json) {
        // Сохраняем в кеш
        @file_put_contents($cache_file, $json);
        $data = json_decode($json, true);
        if ($data && isset($data['products'])) {
            return $data['products'];
        }
    }

    error_log('Используются fallback товары');

    // Fallback: статические товары
    return [
        (object)[
            'id' => 1,
            'name' => 'Сатиновый',
            'category_slug' => 'robe',
            'material_label' => 'Мягкий сатин, короткий крой',
            'price' => 2900,
            'old_price' => null,
            'badge' => null,
            'cover' => null,
        ],
        (object)[
            'id' => 2,
            'name' => 'Шёлковый',
            'category_slug' => 'robe',
            'material_label' => '100% натуральный шёлк, средний крой',
            'price' => 5900,
            'old_price' => null,
            'badge' => 'hit',
            'cover' => null,
        ],
        (object)[
            'id' => 3,
            'name' => 'Люкс',
            'category_slug' => 'robe',
            'material_label' => 'Шёлк премиум-класса, длинный крой',
            'price' => 9900,
            'old_price' => null,
            'badge' => null,
            'cover' => null,
        ],
    ];
}

$products = fetch_products();
$bot_url = 'https://t.me/sikretsweet_home_bot/app';
?>

<!DOCTYPE html>
<html lang="ru">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Цены — Шелковые халаты</title>
  <link rel="icon" type="image/svg+xml" href="/favicon.svg">
  <link rel="manifest" href="/site.webmanifest">
  <style>
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

    :root {
      --gold: #d4a843;
      --gold-light: #f5d483;
      --orange: #f59e0b;
      --rose: #c9748a;
      --bg: #0d0a0f;
      --card-bg: rgba(255,255,255,0.04);
      --border: rgba(255,255,255,0.08);
    }

    html { scroll-behavior: smooth; }

    body {
      background: var(--bg);
      color: #fff;
      font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
      overflow-x: hidden;
      min-height: 100vh;
    }

    /* ── Фоновые пятна ── */
    .bg-layer {
      position: fixed;
      inset: 0;
      pointer-events: none;
      z-index: 0;
    }
    .bg-layer::before {
      content: '';
      position: absolute;
      width: 600px; height: 600px;
      border-radius: 50%;
      background: radial-gradient(circle, rgba(212,168,67,0.12) 0%, transparent 70%);
      top: -200px; left: -100px;
      animation: blob 8s ease-in-out infinite alternate;
    }
    .bg-layer::after {
      content: '';
      position: absolute;
      width: 500px; height: 500px;
      border-radius: 50%;
      background: radial-gradient(circle, rgba(201,116,138,0.10) 0%, transparent 70%);
      bottom: -100px; right: -100px;
      animation: blob 10s ease-in-out infinite alternate-reverse;
    }
    @keyframes blob {
      from { transform: scale(1) translate(0, 0); }
      to   { transform: scale(1.2) translate(30px, 20px); }
    }

    /* ── Обёртка ── */
    .page {
      position: relative;
      z-index: 1;
      max-width: 1100px;
      margin: 0 auto;
      padding: clamp(40px, 8vw, 80px) clamp(16px, 5vw, 40px);
    }

    /* ── Заголовок ── */
    .hero {
      text-align: center;
      margin-bottom: clamp(40px, 6vw, 72px);
    }
    .hero-label {
      display: inline-block;
      font-size: 13px;
      font-weight: 600;
      letter-spacing: 0.12em;
      text-transform: uppercase;
      color: var(--orange);
      margin-bottom: 16px;
      padding: 6px 16px;
      border: 1px solid rgba(245,158,11,0.3);
      border-radius: 100px;
      background: rgba(245,158,11,0.07);
    }
    .hero h1 {
      font-size: clamp(28px, 5vw, 52px);
      font-weight: 700;
      line-height: 1.15;
      background: linear-gradient(135deg, #fff 30%, var(--gold-light) 70%, var(--rose) 100%);
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
      background-clip: text;
      margin-bottom: 16px;
    }
    .hero p {
      font-size: clamp(15px, 2vw, 18px);
      color: rgba(255,255,255,0.55);
      max-width: 480px;
      margin: 0 auto;
      line-height: 1.6;
    }

    /* ── Сетка карточек ── */
    .cards {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
      gap: 20px;
      align-items: start;
    }

    /* ── Карточка ── */
    .card {
      background: var(--card-bg);
      border: 1px solid var(--border);
      border-radius: 24px;
      padding: clamp(24px, 4vw, 36px);
      backdrop-filter: blur(16px);
      -webkit-backdrop-filter: blur(16px);
      transition: transform 0.3s ease, box-shadow 0.3s ease;
      opacity: 0;
      transform: translateY(30px);
    }
    .card.visible {
      opacity: 1;
      transform: translateY(0);
      transition: opacity 0.5s ease, transform 0.5s ease, box-shadow 0.3s ease;
    }
    .card:hover {
      transform: translateY(-6px);
      box-shadow: 0 24px 60px rgba(0,0,0,0.4);
    }

    /* ── Популярная карточка ── */
    .card.popular {
      border-color: rgba(245,158,11,0.45);
      background: rgba(245,158,11,0.07);
      position: relative;
      box-shadow: 0 0 40px rgba(245,158,11,0.12);
    }
    .card.popular:hover {
      box-shadow: 0 24px 60px rgba(245,158,11,0.2);
    }
    .badge {
      position: absolute;
      top: -13px;
      left: 50%;
      transform: translateX(-50%);
      background: linear-gradient(135deg, var(--orange), var(--rose));
      color: #fff;
      font-size: 12px;
      font-weight: 700;
      letter-spacing: 0.08em;
      text-transform: uppercase;
      padding: 5px 18px;
      border-radius: 100px;
      white-space: nowrap;
    }

    /* ── Иллюстрация-иконка ── */
    .card-icon {
      width: 52px;
      height: 52px;
      border-radius: 14px;
      display: flex;
      align-items: center;
      justify-content: center;
      margin-bottom: 20px;
      background: rgba(255,255,255,0.06);
      font-size: 28px;
    }
    .card.popular .card-icon {
      background: rgba(245,158,11,0.15);
    }

    /* ── Название и цена ── */
    .card-name {
      font-size: 13px;
      font-weight: 600;
      letter-spacing: 0.1em;
      text-transform: uppercase;
      color: rgba(255,255,255,0.45);
      margin-bottom: 8px;
    }
    .card-title {
      font-size: clamp(20px, 3vw, 26px);
      font-weight: 700;
      color: #fff;
      margin-bottom: 6px;
    }
    .card-material {
      font-size: 14px;
      color: rgba(255,255,255,0.45);
      margin-bottom: 24px;
    }

    .price-row {
      display: flex;
      align-items: baseline;
      gap: 6px;
      margin-bottom: 28px;
      padding-bottom: 24px;
      border-bottom: 1px solid var(--border);
    }
    .price-amount {
      font-size: clamp(32px, 5vw, 44px);
      font-weight: 800;
      line-height: 1;
      color: #fff;
    }
    .card.popular .price-amount {
      background: linear-gradient(135deg, var(--orange), var(--gold-light));
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
      background-clip: text;
    }
    .price-currency {
      font-size: 20px;
      font-weight: 600;
      color: rgba(255,255,255,0.5);
    }

    /* ── Список фич ── */
    .features {
      list-style: none;
      display: flex;
      flex-direction: column;
      gap: 12px;
      margin-bottom: 28px;
    }
    .features li {
      display: flex;
      align-items: center;
      gap: 10px;
      font-size: 14px;
      color: rgba(255,255,255,0.75);
      line-height: 1.4;
    }
    .features li.off {
      color: rgba(255,255,255,0.28);
    }
    .feat-icon {
      flex-shrink: 0;
      width: 20px;
      height: 20px;
    }

    /* ── Кнопка ── */
    .btn {
      display: block;
      width: 100%;
      padding: 14px;
      border-radius: 12px;
      font-size: 15px;
      font-weight: 600;
      text-align: center;
      cursor: pointer;
      border: none;
      transition: transform 0.2s ease, box-shadow 0.2s ease, opacity 0.2s ease;
      text-decoration: none;
    }
    .btn:hover { transform: translateY(-2px); opacity: 0.9; }
    .btn:active { transform: translateY(0); }

    .btn-default {
      background: rgba(255,255,255,0.08);
      color: #fff;
      border: 1px solid rgba(255,255,255,0.12);
    }
    .btn-default:hover {
      background: rgba(255,255,255,0.13);
      box-shadow: 0 8px 24px rgba(0,0,0,0.3);
    }

    .btn-primary {
      background: linear-gradient(135deg, var(--orange) 0%, #e85d8a 100%);
      color: #fff;
      box-shadow: 0 8px 32px rgba(245,158,11,0.3);
    }
    .btn-primary:hover {
      box-shadow: 0 12px 40px rgba(245,158,11,0.45);
    }

    /* ── Баннер доставки ── */
    .delivery-banner {
      margin-top: clamp(40px, 6vw, 64px);
      background: var(--card-bg);
      border: 1px solid var(--border);
      border-radius: 20px;
      padding: clamp(20px, 4vw, 32px) clamp(24px, 5vw, 48px);
      backdrop-filter: blur(16px);
      -webkit-backdrop-filter: blur(16px);
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 16px;
      flex-wrap: wrap;
      text-align: center;
    }
    .delivery-banner svg { flex-shrink: 0; }
    .delivery-text strong {
      display: block;
      font-size: clamp(15px, 2.5vw, 18px);
      color: #fff;
      margin-bottom: 4px;
    }
    .delivery-text span {
      font-size: 14px;
      color: rgba(255,255,255,0.45);
    }
  </style>
</head>
<body>

<div class="bg-layer"></div>

<div class="page">

  <!-- Заголовок -->
  <div class="hero">
    <span class="hero-label">Наши халаты</span>
    <h1>Выбери свой<br>идеальный халат</h1>
    <p>Натуральные материалы, живые цвета и ощущение роскоши с первой примерки</p>
  </div>

  <!-- Карточки -->
  <div class="cards">
    <?php foreach ($products as $p):
      $is_popular = isset($p->badge) && $p->badge === 'hit';
      $badge_text = '';
      if (isset($p->badge)) {
        $badge_text = $p->badge === 'hit' ? 'Хит продаж' : 'Новинка';
      }
      $icon = '👗';
    ?>
    <div class="card <?= $is_popular ? 'popular' : '' ?>">
      <?php if ($badge_text): ?>
        <div class="badge"><?= htmlspecialchars($badge_text) ?></div>
      <?php endif; ?>

      <div class="card-icon"><?= $icon ?></div>

      <div class="card-name">
        <?= htmlspecialchars($p->category_slug ?? 'Коллекция') ?>
      </div>

      <div class="card-title"><?= htmlspecialchars($p->name) ?></div>

      <div class="card-material"><?= htmlspecialchars($p->material_label ?? '') ?></div>

      <div class="price-row">
        <span class="price-amount"><?= number_format($p->price, 0, ',', ' ') ?></span>
        <span class="price-currency">₽</span>
      </div>

      <ul class="features">
        <li>
          <svg class="feat-icon" viewBox="0 0 20 20" fill="none">
            <circle cx="10" cy="10" r="9" stroke="<?= $is_popular ? 'rgba(245,158,11,0.3)' : 'rgba(255,255,255,0.2)' ?>" stroke-width="1.5"/>
            <path d="M6 10l3 3 5-5" stroke="<?= $is_popular ? '#f59e0b' : '#6ee7b7' ?>" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
          </svg>
          Материал: <?= htmlspecialchars($p->material_label ?? 'шёлк') ?>
        </li>
        <li>
          <svg class="feat-icon" viewBox="0 0 20 20" fill="none">
            <circle cx="10" cy="10" r="9" stroke="<?= $is_popular ? 'rgba(245,158,11,0.3)' : 'rgba(255,255,255,0.2)' ?>" stroke-width="1.5"/>
            <path d="M6 10l3 3 5-5" stroke="<?= $is_popular ? '#f59e0b' : '#6ee7b7' ?>" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
          </svg>
          Натуральные ткани
        </li>
        <li>
          <svg class="feat-icon" viewBox="0 0 20 20" fill="none">
            <circle cx="10" cy="10" r="9" stroke="<?= $is_popular ? 'rgba(245,158,11,0.3)' : 'rgba(255,255,255,0.2)' ?>" stroke-width="1.5"/>
            <path d="M6 10l3 3 5-5" stroke="<?= $is_popular ? '#f59e0b' : '#6ee7b7' ?>" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
          </svg>
          Премиум качество
        </li>
        <li class="off">
          <svg class="feat-icon" viewBox="0 0 20 20" fill="none">
            <circle cx="10" cy="10" r="9" stroke="rgba(255,255,255,0.1)" stroke-width="1.5"/>
            <path d="M7 7l6 6M13 7l-6 6" stroke="rgba(255,255,255,0.2)" stroke-width="1.8" stroke-linecap="round"/>
          </svg>
          Подарочная коробка
        </li>
      </ul>

      <a href="<?= htmlspecialchars($bot_url) ?>" class="btn <?= $is_popular ? 'btn-primary' : 'btn-default' ?>">Купить</a>
    </div>
    <?php endforeach; ?>
  </div>

  <!-- Баннер доставки -->
  <div class="delivery-banner">
    <svg width="32" height="32" viewBox="0 0 24 24" fill="none">
      <rect x="1" y="8" width="15" height="10" rx="2" stroke="var(--orange)" stroke-width="1.5"/>
      <path d="M16 10h4l3 4v4h-7V10z" stroke="var(--orange)" stroke-width="1.5" stroke-linejoin="round"/>
      <circle cx="5.5" cy="19.5" r="1.5" stroke="var(--orange)" stroke-width="1.5"/>
      <circle cx="18.5" cy="19.5" r="1.5" stroke="var(--orange)" stroke-width="1.5"/>
    </svg>
    <div class="delivery-text">
      <strong>Бесплатная доставка от 3 000 ₽</strong>
      <span>Курьером по всей России — от 1 до 3 дней</span>
    </div>
  </div>

</div>

<script>
  // Scroll-reveal для карточек
  const cards = document.querySelectorAll('.card');

  const observer = new IntersectionObserver((entries) => {
    entries.forEach((entry, i) => {
      if (entry.isIntersecting) {
        setTimeout(() => entry.target.classList.add('visible'), i * 100);
        observer.unobserve(entry.target);
      }
    });
  }, { threshold: 0.1 });

  cards.forEach(card => observer.observe(card));
</script>

</body>
</html>
