<?php
require_once __DIR__ . '/../database.php';

class AdminHandler {
    private static function extractUserId(?string $text): ?int {
        if (!$text) return null;
        if (preg_match('/🆔 <b>المعرف:<\/b> <code>(\d+)<\/code>/', $text, $matches)) {
            return (int)$matches[1];
        }
        return null;
    }

    public static function handleMessage($bot, $message): void {
        $adminId = $message->getFrom()->getId();
        if (!Database::isAdmin($adminId)) return;

        $text = $message->getText() ?? '';

        // أوامر الإدارة
        if (strpos($text, '/addadmin') === 0 && $message->getReplyToMessage()) {
            $targetId = $message->getReplyToMessage()->getFrom()->getId();
            Database::addAdmin($targetId);
            $bot->sendMessage($adminId, "✅ تم إضافة المشرف بنجاح.");
            return;
        }

        if (strpos($text, '/removeadmin') === 0 && $message->getReplyToMessage()) {
            $targetId = $message->getReplyToMessage()->getFrom()->getId();
            Database::removeAdmin($targetId);
            $bot->sendMessage($adminId, "✅ تم إزالة المشرف بنجاح.");
            return;
        }

        if (strpos($text, '/unban') === 0) {
            $parts = explode(' ', $text);
            if (isset($parts[1]) && is_numeric($parts[1])) {
                Database::setBanStatus((int)$parts[1], false);
                $bot->sendMessage($adminId, "✅ تم إلغاء حظر المستخدم <code>{$parts[1]}</code>.", 'HTML');
            }
            return;
        }

        if (strpos($text, '/') === 0) return;

        // معالجة الرد المباشر وحل مشكلة Forward Privacy عبر Regex
        if ($message->getReplyToMessage()) {
            $replyMsg = $message->getReplyToMessage();
            $targetUserId = null;

            if ($replyMsg->getForwardFrom()) {
                $targetUserId = $replyMsg->getForwardFrom()->getId();
            } elseif ($replyMsg->getText()) {
                $targetUserId = self::extractUserId($replyMsg->getText());
            }

            if ($targetUserId) {
                try {
                    $header = "📩 <b>رد من الدعم الفني:</b>\n\n";
                    if ($text) {
                        $bot->sendMessage($targetUserId, $header . htmlspecialchars($text), 'HTML');
                    } else {
                        $bot->sendMessage($targetUserId, $header, 'HTML');
                        $bot->forwardMessage($targetUserId, $adminId, $message->getMessageId());
                    }
                    $bot->sendMessage($adminId, "✅ تم إرسال الرد إلى المستخدم بنجاح.");
                } catch (Exception $e) {
                    $bot->sendMessage($adminId, "❌ فشل إرسال الرد. قد يكون المستخدم حظر البوت.");
                }
            } else {
                $bot->sendMessage($adminId, "⚠️ تعذر تحديد صاحب التذكرة. قم بعمل Reply على كارت التذكرة نفسه.");
            }
        }
    }

    public static function handleCallback($bot, $callbackQuery): void {
        $adminId = $callbackQuery->getFrom()->getId();
        if (!Database::isAdmin($adminId)) return;

        $data = $callbackQuery->getData();

        if (strpos($data, 'close_ticket:') === 0) {
            $parts = explode(':', $data);
            $ticketId = (int)$parts[1];
            $userId = (int)$parts[2];

            Database::closeTicket($ticketId);
            try {
                $bot->sendMessage($userId, "✅ تم إغلاق تذكرتك من قبل فريق الدعم.");
            } catch (Exception $e) {}

            $bot->answerCallbackQuery($callbackQuery->getId(), "تم إغلاق التذكرة بنجاح.");
            $msgText = $callbackQuery->getMessage()->getText();
            $bot->editMessageText($adminId, $callbackQuery->getMessage()->getMessageId(), $msgText . "\n\n[✅ تم إغلاق التذكرة]");
        }

        if (strpos($data, 'ban_user:') === 0) {
            $parts = explode(':', $data);
            $userId = (int)$parts[1];

            Database::setBanStatus($userId, true);
            $bot->answerCallbackQuery($callbackQuery->getId(), "تم حظر المستخدم {$userId}.", true);
            try {
                $bot->sendMessage($userId, "🚫 تم حظرك من استخدام هذا البوت.");
            } catch (Exception $e) {}
        }
    }
}
