<?php
require_once '../includes/auth.php';
require_once '../includes/db.php';
require_login();
csrf_verify();

$pid = (int)($_POST['project_id'] ?? 0);
$uid = (int)current_user()['id'];
if (!$pid) json_response(['error' => 'Invalid project'], 400);

$db = db();
$check = $db->prepare('SELECT 1 FROM favorites WHERE user_id=? AND project_id=?');
$check->execute([$uid, $pid]);
$exists = $check->fetchColumn();

if ($exists) {
    $db->prepare('DELETE FROM favorites WHERE user_id=? AND project_id=?')->execute([$uid, $pid]);
    $faved = false;
} else {
    $db->prepare('INSERT IGNORE INTO favorites (user_id,project_id) VALUES (?,?)')->execute([$uid, $pid]);
    $faved = true;
}
json_response(['faved' => $faved]);
