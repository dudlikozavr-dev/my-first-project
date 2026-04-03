<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: https://sikretsweet.ru');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false]);
    exit;
}

$to = 'elen_ka_09@mail.ru';

$name     = htmlspecialchars(strip_tags($_POST['name']     ?? ''));
$phone    = htmlspecialchars(strip_tags($_POST['phone']    ?? ''));
$delivery = htmlspecialchars(strip_tags($_POST['delivery'] ?? ''));
$comment  = htmlspecialchars(strip_tags($_POST['comment']  ?? '—'));
$gift     = htmlspecialchars(strip_tags($_POST['gift']     ?? 'Нет'));
$order    = htmlspecialchars(strip_tags($_POST['order']    ?? '—'));
$total    = htmlspecialchars(strip_tags($_POST['total']    ?? ''));

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

echo json_encode(['success' => (bool)$result]);
