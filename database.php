<?php
require_once __DIR__ . '/config.php';

class Database {
    private static ?PDO $pdo = null;

    public static function connect(): PDO {
        if (self::$pdo === null) {
            self::$pdo = new PDO('sqlite:' . DB_PATH);
            self::$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            self::$pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        }
        return self::$pdo;
    }

    public static function initDb(): void {
        $db = self::connect();
        $db->exec("CREATE TABLE IF NOT EXISTS users (user_id INTEGER PRIMARY KEY, is_banned INTEGER DEFAULT 0)");
        $db->exec("CREATE TABLE IF NOT EXISTS admins (user_id INTEGER PRIMARY KEY)");
        $db->exec("CREATE TABLE IF NOT EXISTS tickets (
            ticket_id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER,
            message_id INTEGER,
            is_open INTEGER DEFAULT 1,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )");

        $stmt = $db->prepare("INSERT OR IGNORE INTO admins (user_id) VALUES (?)");
        foreach (ADMIN_IDS as $adminId) {
            $stmt->execute([$adminId]);
        }
    }

    public static function addUser(int $userId): void {
        $stmt = self::connect()->prepare("INSERT OR IGNORE INTO users (user_id) VALUES (?)");
        $stmt->execute([$userId]);
    }

    public static function isBanned(int $userId): bool {
        $stmt = self::connect()->prepare("SELECT is_banned FROM users WHERE user_id = ?");
        $stmt->execute([$userId]);
        $res = $stmt->fetch();
        return $res && (int)$res['is_banned'] === 1;
    }

    public static function setBanStatus(int $userId, bool $banned): void {
        $stmt = self::connect()->prepare("UPDATE users SET is_banned = ? WHERE user_id = ?");
        $stmt->execute([$banned ? 1 : 0, $userId]);
    }

    public static function addAdmin(int $userId): void {
        $stmt = self::connect()->prepare("INSERT OR IGNORE INTO admins (user_id) VALUES (?)");
        $stmt->execute([$userId]);
    }

    public static function removeAdmin(int $userId): void {
        $stmt = self::connect()->prepare("DELETE FROM admins WHERE user_id = ?");
        $stmt->execute([$userId]);
    }

    public static function isAdmin(int $userId): bool {
        $stmt = self::connect()->prepare("SELECT 1 FROM admins WHERE user_id = ?");
        $stmt->execute([$userId]);
        return (bool)$stmt->fetch();
    }

    public static function getAllAdmins(): array {
        $stmt = self::connect()->query("SELECT user_id FROM admins");
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    public static function createTicket(int $userId, int $messageId): int {
        $db = self::connect();
        $stmt = $db->prepare("INSERT INTO tickets (user_id, message_id) VALUES (?, ?)");
        $stmt->execute([$userId, $messageId]);
        return (int)$db->lastInsertId();
    }

    public static function closeTicket(int $ticketId): void {
        $stmt = self::connect()->prepare("UPDATE tickets SET is_open = 0 WHERE ticket_id = ?");
        $stmt->execute([$ticketId]);
    }

    public static function getAllUsers(): array {
        $stmt = self::connect()->query("SELECT user_id FROM users WHERE is_banned = 0");
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    public static function getStats(): array {
        $db = self::connect();
        $users = $db->query("SELECT COUNT(*) FROM users")->fetchColumn();
        $admins = $db->query("SELECT COUNT(*) FROM admins")->fetchColumn();
        $tickets = $db->query("SELECT COUNT(*) FROM tickets WHERE is_open = 1")->fetchColumn();

        return [
            'users' => $users,
            'admins' => $admins,
            'open_tickets' => $tickets
        ];
    }
}
