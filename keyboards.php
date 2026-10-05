<?php
use TelegramBot\Api\Types\Inline\InlineKeyboardMarkup;

class Keyboards {
    public static function getTicketAdminKeyboard(int $userId, int $ticketId): InlineKeyboardMarkup {
        return new InlineKeyboardMarkup([
            [
                ['text' => '👤 ملف المستخدم', 'url' => "tg://user?id={$userId}"]
            ],
            [
                ['text' => '✅ إغلاق التذكرة', 'callback_data' => "close_ticket:{$ticketId}:{$userId}"],
                ['text' => '🚫 حظر', 'callback_data' => "ban_user:{$userId}"]
            ]
        ]);
    }

    public static function getStartKeyboard(string $channelUsername): InlineKeyboardMarkup {
        $cleanUsername = ltrim($channelUsername, '@');
        $url = !empty($cleanUsername) ? "https://t.me/{$cleanUsername}" : "#";

        return new InlineKeyboardMarkup([
            [['text' => '📢 اشترك في القناة', 'url' => $url]],
            [['text' => '✅ تحقق من الاشتراك', 'callback_data' => 'check_subscribe']]
        ]);
    }
}
