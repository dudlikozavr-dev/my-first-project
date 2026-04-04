<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: https://sikretsweet.ru');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false]);
    exit;
}

// ── Настройки ──────────────────────────────────────────
$to            = 'elen_ka_09@mail.ru';
$tg_token      = '8232018923:AAEeWebmplaYpuUixtQt6emmmjRT19GoaMg';
$tg_chat_id    = '1069432017';
// ───────────────────────────────────────────────────────

$name     = htmlspecialchars(strip_tags($_POST['name']     ?? ''));
$phone    = htmlspecialchars(strip_tags($_POST['phone']    ?? ''));
$delivery = htmlspecialchars(strip_tags($_POST['delivery'] ?? ''));
$comment  = htmlspecialchars(strip_tags($_POST['comment']  ?? '—'));
$gift     = htmlspecialchars(strip_tags($_POST['gift']     ?? 'Нет'));
$order    = htmlspecialchars(strip_tags($_POST['order']    ?? '—'));
$total    = htmlspecialchars(strip_tags($_POST['total']    ?? ''));

// ── Email ───────────────────────────────────────────────
$subject = '=?UTF-8?B?' . base64_encode("Новый заказ SikretSweet — $name") . '?=';

$body  = "Новый заказ на sikretsweet.ru\n";
$body .= str_repeat('=', 40) . "\n\n";
$body .= "Имя:                 $name\n";
$body .= "Телефон:             $phone\n";
$body .= "Доставка:            $delivery\n";
$body .= "Подарочная упаковка: $gift\n";
$body .= "Комментарий:         $comment\n\n";
$body .= "Состав заказа:\n$order\n\n";
$body .= str_repeat('-', 40) . "\n";
$body .= "Итого: $total\n";

$headers  = "From: =?UTF-8?B?" . base64_encode('SikretSweet') . "?= <noreply@sikretsweet.ru>\r\n";
$headers .= "Content-Type: text/plain; charset=UTF-8\r\n";
$headers .= "Content-Transfer-Encoding: base64\r\n";

$result = mail($to, $subject, base64_encode($body), $headers);

// ── Telegram ────────────────────────────────────────────
$tg_text  = "🛍 *Новый заказ SikretSweet*\n\n";
$tg_text .= "👤 Имя: $name\n";
$tg_text .= "📞 Телефон: $phone\n";
$tg_text .= "🚚 Доставка: $delivery\n";
$tg_text .= "🎁 Упаковка: $gift\n";
$tg_text .= "💬 Комментарий: $comment\n\n";
$tg_text .= "📦 Заказ:\n$order\n\n";
$tg_text .= "💰 Итого: *$total*";

$tg_url = "https://api.telegram.org/bot{$tg_token}/sendMessage";

$ch = curl_init($tg_url);
curl_setopt_array($ch, [
    CURLOPT_POST           => true,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
    CURLOPT_POSTFIELDS     => json_encode([
        'chat_id'    => $tg_chat_id,
        'text'       => $tg_text,
        'parse_mode' => 'Markdown'
    ])
]);
curl_exec($ch);
curl_close($ch);

echo json_encode(['success' => (bool)$result]);
