<?php
require_once __DIR__ . '/db.php';

function h(string $s): string {
    return htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
}

function order_status_label(string $status): string {
    return [
        'new'        => 'Новый',
        'processing' => 'В работе',
        'shipped'    => 'Отправлен',
        'delivered'  => 'Доставлен',
        'cancelled'  => 'Отменён',
    ][$status] ?? $status;
}

function order_status_color(string $status): string {
    return [
        'new'        => '#b8892e',
        'processing' => '#2e7db8',
        'shipped'    => '#7b4db8',
        'delivered'  => '#2eb87d',
        'cancelled'  => '#b82e2e',
    ][$status] ?? '#888';
}

function send_telegram(string $text): void {
    $token   = '8232018923:AAEeWebmplaYpuUixtQt6emmmjRT19GoaMg';
    $chat_id = '1069432017';
    $url     = "https://api.telegram.org/bot{$token}/sendMessage";

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_POST           => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
        CURLOPT_POSTFIELDS     => json_encode([
            'chat_id'    => $chat_id,
            'text'       => $text,
            'parse_mode' => 'Markdown',
        ]),
    ]);
    curl_exec($ch);
    curl_close($ch);
}

function create_order(array $data): int {
    $db = db();

    $db->beginTransaction();
    try {
        $stmt = $db->prepare("
            INSERT INTO orders (customer_id, name, phone, delivery, comment, gift, total, status)
            VALUES (:customer_id, :name, :phone, :delivery, :comment, :gift, :total, 'new')
        ");
        $stmt->execute([
            ':customer_id' => $data['customer_id'] ?? null,
            ':name'        => $data['name'],
            ':phone'       => $data['phone'],
            ':delivery'    => $data['delivery'],
            ':comment'     => $data['comment'],
            ':gift'        => $data['gift'] ? 1 : 0,
            ':total'       => $data['total'],
        ]);
        $order_id = (int)$db->lastInsertId();

        if (!empty($data['items'])) {
            $item_stmt = $db->prepare("
                INSERT INTO order_items (order_id, product_name, price, qty)
                VALUES (:order_id, :product_name, :price, :qty)
            ");
            foreach ($data['items'] as $item) {
                $item_stmt->execute([
                    ':order_id'     => $order_id,
                    ':product_name' => $item['name'],
                    ':price'        => $item['price'],
                    ':qty'          => $item['qty'] ?? 1,
                ]);
            }
        }

        $db->commit();
        return $order_id;
    } catch (Exception $e) {
        $db->rollBack();
        throw $e;
    }
}
