<?php
require_once 'includes/layout.php';
require_login();
$msg = ''; $err = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $cur = $_POST['current'] ?? ''; $new = $_POST['new'] ?? ''; $rep = $_POST['repeat'] ?? '';
    $row = db()->prepare('SELECT password_hash FROM users WHERE id=?');
    $row->execute([current_user()['id']]);
    $hash = $row->fetchColumn();
    if (!password_verify($cur, $hash)) $err = 'Current password is wrong.';
    elseif (strlen($new) < 8) $err = 'New password must have at least 8 characters.';
    elseif ($new !== $rep) $err = 'Passwords do not match.';
    else {
        db()->prepare('UPDATE users SET password_hash=? WHERE id=?')->execute([password_hash($new, PASSWORD_DEFAULT), current_user()['id']]);
        $msg = 'Password updated. Sign out and back in to refresh your session.';
    }
}
head('Change password');
?>
<h1>Change password</h1>
<?php if ($msg): ?><p class="notice"><?= htmlspecialchars($msg) ?></p><?php endif; ?>
<?php if ($err): ?><p class="notice" style="border-color:#f33"><?= htmlspecialchars($err) ?></p><?php endif; ?>
<form method="post" class="acc-form">
  <?= csrf_field() ?>
  <input type="password" name="current" placeholder="Current password" required autocomplete="current-password">
  <input type="password" name="new" placeholder="New password (min 8)" required autocomplete="new-password">
  <input type="password" name="repeat" placeholder="Repeat new password" required autocomplete="new-password">
  <button class="btn" type="submit">Update password</button>
</form>
<?php foot(); ?>
