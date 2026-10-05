<?php
require_once __DIR__ . '/vendor/autoload.php';

use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();

define('BOT_TOKEN', $_ENV['BOT_TOKEN'] ?? '');
define('CHANNEL_ID', $_ENV['CHANNEL_ID'] ?? '');
define('DB_PATH', __DIR__ . '/' . ($_ENV['DB_PATH'] ?? 'bot_database.sqlite'));

$adminsRaw = $_ENV['ADMIN_IDS'] ?? '';
$adminIds = array_filter(array_map('trim', explode(',', $adminsRaw)), 'is_numeric');
define('ADMIN_IDS', array_map('intval', $adminIds));
