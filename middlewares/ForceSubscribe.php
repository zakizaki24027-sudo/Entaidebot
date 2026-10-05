<?php
require_once __DIR__ . '/../database.php';
require_once __DIR__ . '/../keyboards.php';

class ForceSubscribe {
    public static function check($bot, $update): bool {
        $user = null;
        if ($update->getMessage()) {
            $user = $update->getMessage()->getFrom();
        } elseif ($update->getCallbackQuery()) {
            $user = $update->getCallbackQuery()->getFrom();
        }

        if (!$user || $user->isBot()) return true;
        if (Database::isAdmin($user->getId())) return true;

        if ($update->getCallbackQuery() && $update->getCallbackQuery()->getData() === 'check_subscribe') {
            return true;
        }

        try {
            $member = $bot->getChatMember(CHANNEL_ID, $user->getId());
            $status = $member->getStatus();

            if (in_array($status, ['left', 'kicked'])) {
                $chat = $bot->getChat(CHANNEL_ID);
                $username = $chat->getUsername() ?? '';

                if ($update->getMessage()) {
                    $bot->sendMessage(
                        $user->getId(),
                        "🚫 <b>عذراً، يجب عليك الاشتراك في قناتنا أولاً لاستخدام البوت:</b>",
                        'HTML',
                        false,
                        null,
                        Keyboards::getStartKeyboard($username)
                    );
                } elseif ($update->getCallbackQuery()) {
                    $bot->answerCallbackQuery($update->getCallbackQuery()->getId(), "⚠️ يجب عليك الاشتراك في القناة أولاً!", true);
                }
                return false;
            }
        } catch (Exception $e) {
            // إمكانية تخطي الخطأ في حال كانت القناة غير معرّفة بالشكل الصحيح
        }

        return true;
    }
}
