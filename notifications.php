<?php
require_once 'includes/layout.php';
require_login();
$uid = (int)current_user()['id'];
$st = db()->prepare('SELECT * FROM notifications WHERE user_id=? ORDER BY id DESC LIMIT 50');
$st->execute([$uid]);
$items = $st->fetchAll();
head('Notifications');
?>
<h1>Notifications</h1>
<?php if (!$items): ?><p class="mono">Nothing here yet. You will be notified about likes and comments on your projects.</p><?php else: ?>
<ul class="nlist">
<?php foreach ($items as $n): ?>
  <li class="<?= $n['is_read'] ? '' : 'unread' ?>">
    <?php if ($n['link']): ?><a href="<?= htmlspecialchars($n['link']) ?>"><?= htmlspecialchars($n['message']) ?></a>
    <?php else: echo htmlspecialchars($n['message']); endif; ?>
    <time><?= htmlspecialchars($n['created_at']) ?></time>
  </li>
<?php endforeach; ?>
</ul>
<?php endif;
db()->prepare('UPDATE notifications SET is_read=1 WHERE user_id=?')->execute([$uid]);
foot(); ?>
