<?php
require_once __DIR__ . '/../database.php';

class ManagementHandler {
    public static function handleMessage($bot, $message): void {
        $adminId = $message->getFrom()->getId();
        if (!Database::isAdmin($adminId)) return;

        $text = $message->getText();

        if ($text === '/stats') {
            $stats = Database::getStats();
            $bot->sendMessage(
                $adminId,
                "📊 <b>إحصائيات البوت الحالية:</b>\n\n" .
                "👥 إجمالي المستخدمين: <code>{$stats['users']}</code>\n" .
                "👨‍💼 عدد المشرفين: <code>{$stats['admins']}</code>\n" .
                "🎫 التذاكر المفتوحة: <code>{$stats['open_tickets']}</code>",
                'HTML'
            );
            return;
        }

        if (strpos($text, '/broadcast') === 0) {
            if (!$message->getReplyToMessage()) {
                $bot->sendMessage($adminId, "⚠️ يرجى الرد على الرسالة المراد بثها لجميع المستخدمين.");
                return;
            }

            $users = Database::getAllUsers();
            $success = 0;
            $fail = 0;
            $total = count($users);

            $statusMsg = $bot->sendMessage($adminId, "🔄 جاري تحضير البث لـ {$total} مستخدم...");

            foreach ($users as $index => $userId) {
                try {
                    $bot->forwardMessage($userId, $adminId, $message->getReplyToMessage()->getMessageId());
                    $success++;
                } catch (Exception $e) {
                    $fail++;
                }

                // تأخير لحماية البوت من حظر تليجرام (30/sec)
                usleep(40000); // 40ms

                if (($index + 1) % 20 === 0 || ($index + 1) === $total) {
                    try {
                        $bot->editMessageText(
                            $adminId,
                            $statusMsg->getMessageId(),
                            "🔄 <b>جاري البث...</b>\n\n" .
                            "✅ نجاح: <code>{$success}</code>\n" .
                            "❌ فشل: <code>{$fail}</code>\n" .
                            "📊 التقدم: <code>" . ($index + 1) . "/{$total}</code>",
                            'HTML'
                        );
                    } catch (Exception $e) {}
                }
            }

            $bot->sendMessage(
                $adminId,
                "✅ <b>اكتملت عملية البث بنجاح!</b>\n\n" .
                "📤 تم الإرسال إلى: <code>{$success}</code>\n" .
                "🚫 فشل الإرسال إلى: <code>{$fail}</code>",
                'HTML'
            );
        }
    }
}
