<?php

// ========== НАСТРОЙКИ ==========
define('TOKEN', '8895000985:AAGPyvKFinh8KL1my7ZjDoOhTC9hqcGFHpc');
define('CHANNEL_ID', '@bunnyboba1'); // или числовой ID: -1001234567890
define('CHANNEL_LINK', 'https://t.me/bunnyboba1');
define('PROMO_CODE', 'ФИЛАЛАЙТ');

// ========== ФУНКЦИИ ==========

// Отправка запроса к Telegram API
function botApi($method, $params = []) {
    $url = 'https://api.telegram.org/bot' . TOKEN . '/' . $method;
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($params));
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    $result = curl_exec($ch);
    curl_close($ch);
    return json_decode($result, true);
}

// Проверка подписки на канал
function isSubscribed($userId) {
    $result = botApi('getChatMember', [
        'chat_id' => CHANNEL_ID,
        'user_id' => $userId
    ]);
    if (!isset($result['ok']) || !$result['ok']) return false;
    $status = $result['result']['status'];
    return in_array($status, ['member', 'administrator', 'creator']);
}

// Главная клавиатура
function getMainKeyboard() {
    return [
        'inline_keyboard' => [
            [['text' => '📢 Подписаться на канал', 'url' => CHANNEL_LINK]],
            [['text' => '✅ Проверить подписку', 'callback_data' => 'check']]
        ]
    ];
}

// Клавиатура для неподписавшихся
function getNotSubKeyboard() {
    return [
        'inline_keyboard' => [
            [['text' => '📢 Подписаться на канал', 'url' => CHANNEL_LINK]],
            [['text' => '🔄 Проверить снова', 'callback_data' => 'check']]
        ]
    ];
}

// ========== ОБРАБОТКА ==========

$update = json_decode(file_get_contents('php://input'), true);

// Обработка команды /start или сообщения
if (isset($update['message'])) {
    $chatId = $update['message']['chat']['id'];
    $text = $update['message']['text'] ?? '';

    if ($text == '/start') {
        botApi('sendMessage', [
            'chat_id' => $chatId,
            'text' => "Привет! Чтобы получить скидку, нужно быть подписанным на наш канал.\nПодпишитесь и нажмите кнопку проверки.",
            'reply_markup' => getMainKeyboard()
        ]);
    }
}

// Обработка нажатия на кнопку (callback)
if (isset($update['callback_query'])) {
    $callback = $update['callback_query'];
    $chatId = $callback['message']['chat']['id'];
    $userId = $callback['from']['id'];
    $messageId = $callback['message']['message_id'];
    $data = $callback['data'];

    if ($data == 'check') {
        if (isSubscribed($userId)) {
            botApi('editMessageText', [
                'chat_id' => $chatId,
                'message_id' => $messageId,
                'text' => "✅ Спасибо за подписку!\n\nВаша скидка: *" . PROMO_CODE . "*\n\nИспользуйте промокод при оформлении заказа на сайте https://bunnyboba.ru/",
                'parse_mode' => 'Markdown'
            ]);
        } else {
            botApi('editMessageText', [
                'chat_id' => $chatId,
                'message_id' => $messageId,
                'text' => "❌ Вы ещё не подписаны на канал.\n\nПодпишитесь и нажмите кнопку ниже для проверки.",
                'reply_markup' => getNotSubKeyboard()
            ]);
        }
    }

    // Подтверждаем callback (убираем часики на кнопке)
    botApi('answerCallbackQuery', [
        'callback_query_id' => $callback['id']
    ]);
}