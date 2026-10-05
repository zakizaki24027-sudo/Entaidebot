<?php
require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/database.php';
require_once __DIR__ . '/middlewares/ForceSubscribe.php';
require_once __DIR__ . '/handlers/UserHandler.php';
require_once __DIR__ . '/handlers/AdminHandler.php';
require_once __DIR__ . '/handlers/ManagementHandler.php';

use TelegramBot\Api\Client;

Database::initDb();
$bot = new Client(BOT_TOKEN);

// معالجة التحديثات عبر Long Polling أو Webhook
$bot->on(function (\TelegramBot\Api\Types\Update $update) use ($bot) {
    if (!ForceSubscribe::check($bot, $update)) {
        return;
    }

    if ($update->getMessage()) {
        $message = $update->getMessage();
        $userId = $message->getFrom()->getId();

        if (Database::isAdmin($userId)) {
            ManagementHandler::handleMessage($bot, $message);
            AdminHandler::handleMessage($bot, $message);
        }
        UserHandler::handleMessage($bot, $message);
    }

    if ($update->getCallbackQuery()) {
        $callback = $update->getCallbackQuery();
        $userId = $callback->getFrom()->getId();

        if (Database::isAdmin($userId)) {
            AdminHandler::handleCallback($bot, $callback);
        }
        UserHandler::handleCallback($bot, $callback);
    }
}, function () {
    return true;
});

echo "🚀 تم تشغيل بوت الدعم بنجاح بلغة PHP (Enterprise Ready 10/10)!\n";
$bot->run();

