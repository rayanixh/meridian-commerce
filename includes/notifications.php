<?php
/**
 * Order notifications: WhatsApp, Telegram, Messenger
 */

function notify_new_order(array $order): void {
    $wa = setting('whatsapp_enabled', '0') === '1';
    $tg = setting('telegram_enabled', '0') === '1';

    $adminUrl = base_url('admin/order-view.php?id=' . (int)$order['id']);
    $msg = "🛍 NEW ORDER\n"
         . "Order: " . $order['order_number'] . "\n"
         . "Customer: " . $order['full_name'] . "\n"
         . "Phone: " . $order['phone'] . "\n"
         . "Total: " . format_money($order['total']) . "\n"
         . "Payment: " . strtoupper($order['payment_method']) . "\n"
         . "Status: " . $order['order_status'] . "\n"
         . "Admin: " . $adminUrl;

    if ($wa) send_whatsapp($msg);
    if ($tg) send_telegram($msg);
}

function send_whatsapp(string $message): bool {
    $url = setting('whatsapp_api_url', '');
    $token = setting('whatsapp_api_token', '');
    $number = setting('whatsapp_recipient', '');
    if (!$url || !$token || !$number) return false;
    $endpoint = rtrim($url, '/') . '/messages/send';
    $payload = json_encode(['number' => $number, 'message' => $message, 'token' => $token]);
    $ch = curl_init($endpoint);
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $payload,
        CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 5,
    ]);
    $resp = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    log_error('whatsapp', "HTTP $code RESP: $resp");
    return $code >= 200 && $code < 300;
}

function send_telegram(string $message): bool {
    $botToken = setting('telegram_bot_token', '');
    $chatId = setting('telegram_chat_id', '');
    if (!$botToken || !$chatId) return false;
    $url = "https://api.telegram.org/bot{$botToken}/sendMessage";
    $payload = json_encode(['chat_id' => $chatId, 'text' => $message, 'parse_mode' => 'HTML']);
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $payload,
        CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 5,
    ]);
    $resp = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    log_error('telegram', "HTTP $code RESP: $resp");
    return $code >= 200 && $code < 300;
}
