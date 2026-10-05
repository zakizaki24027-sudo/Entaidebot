<?php
require_once __DIR__ . '/../database.php';
require_once __DIR__ . '/../keyboards.php';

class UserHandler {
    public static function handleMessage($bot, $message): void {
        $userId = $message->getFrom()->getId();
        $text = $message->getText();

        Database::addUser($userId);

        if (Database::isBanned($userId)) {
            $bot->sendMessage($userId, "🚫 أنت محظور من استخدام هذا البوت.");
            return;
        }

        if ($text === '/start') {
            try {
                $member = $bot->getChatMember(CHANNEL_ID, $userId);
                if (in_array($member->getStatus(), ['left', 'kicked'])) {
                    $chat = $bot->getChat(CHANNEL_ID);
                    $bot->sendMessage(
                        $userId,
                        "👋 مرحباً بك في نظام الدعم الفني.\n\n🚫 يجب عليك الاشتراك في قناتنا أولاً لتأكيد استخدام البوت.",
                        'HTML',
                        false,
                        null,
                        Keyboards::getStartKeyboard($chat->getUsername() ?? '')
                    );
                    return;
                }
            } catch (Exception $e) {}

            $bot->sendMessage(
                $userId,
                "👋 <b>مرحباً بك في نظام الدعم الفني!</b>\n\n📝 يرجى كتابة استفسارك أو إرسال ملفك، وسيتم توجيهه فوراً إلى فريق الدعم.",
                'HTML'
            );
            return;
        }

        if (strpos($text, '/') === 0) return;

        // إنشاء تذكرة فريدة
        $ticketId = Database::createTicket($userId, $message->getMessageId());
        $adminText = "🎫 <b>تذكرة جديدة #{$ticketId}</b>\n" .
                     "👤 <b>المستخدم:</b> " . htmlspecialchars($message->getFrom()->getFirstName()) . "\n" .
                     "🆔 <b>المعرف:</b> <code>{$userId}</code>\n\n" .
                     "📝 <b>الرسالة/المحتوى المرفق أدناه:</b>";

        $admins = Database::getAllAdmins();
        foreach ($admins as $adminId) {
            try {
                $bot->sendMessage(
                    $adminId,
                    $adminText,
                    'HTML',
                    false,
                    null,
                    Keyboards::getTicketAdminKeyboard($userId, $ticketId)
                );
                $bot->forwardMessage($adminId, $userId, $message->getMessageId());
            } catch (Exception $e) {}
        }

        $bot->sendMessage($userId, "✅ تم استلام رسالتك وإبلاغ فريق الدعم. يرجى الانتظار لحين الرد.");
    }

    public static function handleCallback($bot, $callbackQuery): void {
        if ($callbackQuery->getData() === 'check_subscribe') {
            $userId = $callbackQuery->getFrom()->getId();
            try {
                $member = $bot->getChatMember(CHANNEL_ID, $userId);
                if (in_array($member->getStatus(), ['member', 'administrator', 'creator'])) {
                    $bot->deleteMessage($userId, $callbackQuery->getMessage()->getMessageId());
                    $bot->sendMessage($userId, "✅ تم التحقق من اشتراكك بنجاح! يمكنك الآن إرسال استفسارك.");
                } else {
                    $bot->answerCallbackQuery($callbackQuery->getId(), "❌ لم تقم بالاشتراك بعد!", true);
                }
            } catch (Exception $e) {
                $bot->answerCallbackQuery($callbackQuery->getId(), "⚠️ حدث خطأ أثناء التحقق من الاشتراك.", true);
            }
        }
    }
}
