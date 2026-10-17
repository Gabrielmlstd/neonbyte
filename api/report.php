<?php
require_once '../includes/auth.php';
require_once '../includes/db.php';
require_login();
csrf_verify();

$pid    = (int)($_POST['project_id'] ?? 0);
$uid    = (int)current_user()['id'];
$reason = trim($_POST['reason'] ?? '');
if (!$pid || !$reason) json_response(['error' => 'Missing fields'], 400);

$db = db();
// Prevent duplicate report from same user
$check = $db->prepare('SELECT id FROM reports WHERE user_id=? AND project_id=?');
$check->execute([$uid, $pid]);
if ($check->fetchColumn()) {
    json_response(['message' => 'You already reported this project.']);
}
$db->prepare('INSERT INTO reports (project_id,user_id,reason) VALUES (?,?,?)')->execute([$pid,$uid,$reason]);
json_response(['message' => 'Report submitted. Thank you.']);
