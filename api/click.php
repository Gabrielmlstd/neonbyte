<?php
// Track download link clicks (counted once per visitor session per project)
require_once '../includes/auth.php';
require_once '../includes/db.php';
$id = (int)($_GET['id'] ?? 0);
if ($id && empty($_SESSION['clicked'][$id])) {
    $_SESSION['clicked'][$id] = 1;
    db()->prepare('UPDATE projects SET clicks=clicks+1 WHERE id=?')->execute([$id]);
}
http_response_code(204);
