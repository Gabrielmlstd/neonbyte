<?php
// ── PDO singleton connection ───────────────────
require_once __DIR__ . '/../config.php';

function db(): PDO {
    static $pdo = null;
    if ($pdo) return $pdo;
    $dsn = 'mysql:host='.DB_HOST.';dbname='.DB_NAME.';charset='.DB_CHARSET;
    $pdo = new PDO($dsn, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ]);
    return $pdo;
}

function notify(int $userId, string $type, string $message, string $link = ''): void {
    db()->prepare('INSERT INTO notifications (user_id,type,message,link) VALUES (?,?,?,?)')->execute([$userId,$type,$message,$link]);
}
function unread_count(int $userId): int {
    $s = db()->prepare('SELECT COUNT(*) FROM notifications WHERE user_id=? AND is_read=0'); $s->execute([$userId]);
    return (int)$s->fetchColumn();
}
