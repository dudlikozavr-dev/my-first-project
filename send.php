<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: https://sikretsweet.ru');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false]);
    exit;
}

require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';

$name     = trim(strip_tags($_POST['name']     ?? ''));
$phone    = trim(strip_tags($_POST['phone']    ?? ''));
$delivery = trim(strip_tags($_POST['delivery'] ?? ''));
$comment  = trim(strip_tags($_POST['comment']  ?? ''));
$gift     = ($_POST['gift'] ?? '') === 'Да';
$order    = trim(strip_tags($_POST['order']    ?? ''));
$total    = trim(strip_tags($_POST['total']    ?? '0'));
$total_num = (float)preg_replace('/[^\d.]/', '', $total);

// Разбиваем строку состава заказа на позиции
$items = [];
foreach (explode("\n", $order) as $line) {
    $line = trim($line);
    if (!$line) continue;
    if (preg_match('/^(.+)\s*—\s*([\d\s]+)\s*₽$/u', $line, $m)) {
        $items[] = [
            'name'  => trim($m[1]),
            'price' => (float)preg_replace('/\s/', '', $m[2]),
            'qty'   => 1,
        ];
    } else {
        $items[] = ['name' => $line, 'price' => 0, 'qty' => 1];
    }
}

// Сохраняем в БД
$order_id = 0;
try {
    $order_id = create_order([
        'name'        => $name,
        'phone'       => $phone,
        'delivery'    => $delivery,
        'comment'     => $comment,
        'gift'        => $gift,
        'total'       => $total_num,
        'items'       => $items,
        'customer_id' => null,
    ]);
} catch (Exception $e) {
    // Продолжаем даже если БД недоступна
}

// Email
$to      = 'elen_ka_09@mail.ru';
$subject = '=?UTF-8?B?' . base64_encode("Новый заказ #$order_id SikretSweet — $name") . '?=';

$body  = "Новый заказ #$order_id на sikretsweet.ru\n";
$body .= str_repeat('=', 40) . "\n\n";
$body .= "Имя:                 $name\n";
$body .= "Телефон:             $phone\n";
$body .= "Доставка:            $delivery\n";
$body .= "Подарочная упаковка: " . ($gift ? 'Да' : 'Нет') . "\n";
$body .= "Комментарий:         " . ($comment ?: '—') . "\n\n";
$body .= "Состав заказа:\n$order\n\n";
$body .= str_repeat('-', 40) . "\n";
$body .= "Итого: $total\n";

$headers  = "From: =?UTF-8?B?" . base64_encode('SikretSweet') . "?= <noreply@sikretsweet.ru>\r\n";
$headers .= "Content-Type: text/plain; charset=UTF-8\r\n";
$headers .= "Content-Transfer-Encoding: base64\r\n";

$result = mail($to, $subject, base64_encode($body), $headers);

// Telegram
$tg_text  = "🛍 *Новый заказ #$order_id SikretSweet*\n\n";
$tg_text .= "👤 Имя: $name\n";
$tg_text .= "📞 Телефон: $phone\n";
$tg_text .= "🚚 Доставка: $delivery\n";
$tg_text .= "🎁 Упаковка: " . ($gift ? 'Да' : 'Нет') . "\n";
$tg_text .= "💬 Комментарий: " . ($comment ?: '—') . "\n\n";
$tg_text .= "📦 Заказ:\n$order\n\n";
$tg_text .= "💰 Итого: *$total*";

send_telegram($tg_text);

echo json_encode(['success' => true, 'order_id' => $order_id]);
