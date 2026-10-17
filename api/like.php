<?php
require_once '../includes/auth.php';
require_once '../includes/db.php';
require_login();
csrf_verify();

$pid  = (int)($_POST['project_id'] ?? 0);
$uid  = (int)current_user()['id'];
if (!$pid) json_response(['error' => 'Invalid project'], 400);

$db = db();
// Check exists
$check = $db->prepare('SELECT 1 FROM likes WHERE user_id=? AND project_id=?');
$check->execute([$uid, $pid]);
$exists = $check->fetchColumn();

if ($exists) {
    $db->prepare('DELETE FROM likes WHERE user_id=? AND project_id=?')->execute([$uid, $pid]);
    $db->prepare('UPDATE projects SET likes_count = GREATEST(0, likes_count-1) WHERE id=?')->execute([$pid]);
    $liked = false;
} else {
    $db->prepare('INSERT IGNORE INTO likes (user_id,project_id) VALUES (?,?)')->execute([$uid,$pid]);
    $db->prepare('UPDATE projects SET likes_count = likes_count+1 WHERE id=?')->execute([$pid]);
    $liked = true;
    $o = $db->prepare('SELECT user_id,name FROM projects WHERE id=?'); $o->execute([$pid]); $o = $o->fetch();
    if ($o && (int)$o['user_id'] !== $uid) notify((int)$o['user_id'],'like',current_user()['name'].' liked '.$o['name'],'project.php?id='.$pid);
}
$count = (int)$db->prepare('SELECT likes_count FROM projects WHERE id=?')
                  ->execute([$pid]) ? $db->query('SELECT likes_count FROM projects WHERE id='.$pid)->fetchColumn() : 0;

json_response(['liked' => $liked, 'count' => (int)$count]);
