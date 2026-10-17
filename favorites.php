<?php
require_once 'includes/layout.php';
require_once 'includes/auth.php';
require_once 'includes/db.php';
require_login();

$uid = (int)current_user()['id'];
$db  = db();
$lks = $db->prepare('
  SELECT p.*, (SELECT s.path_or_url FROM screenshots s WHERE s.project_id=p.id ORDER BY s.id LIMIT 1) AS shot, (SELECT s.is_upload FROM screenshots s WHERE s.project_id=p.id ORDER BY s.id LIMIT 1) AS shot_up, c.name AS cat_name
  FROM likes l
  JOIN projects p ON p.id=l.project_id AND p.status="published"
  JOIN categories c ON c.id=p.category_id
  WHERE l.user_id=?
  ORDER BY p.likes_count DESC
');
$lks->execute([$uid]);
$projects = $lks->fetchAll();

function cover_grad3(array $p): string {
    return cover_css($p);
}

head('Favorites', 'fav');
?>
<div class="page-header fade-up">
  <h1>Favorites</h1>
  <p class="page-sub">Projects you liked.</p>
</div>

<?php if ($projects): ?>
<div class="explore-grid fade-up">
  <?php foreach($projects as $p): ?>
    <a class="card explore-card" href="project.php?id=<?= $p['id'] ?>">
      <div class="cover" style="<?= cover_grad3($p) ?>"><span class="lic"><?= htmlspecialchars($p['license']) ?></span></div>
      <div class="meta">
        <h3><?= htmlspecialchars($p['name']) ?></h3>
        <p class="mono"><?= htmlspecialchars($p['cat_name']) ?></p>
        <p class="likes"><?= icon('heart') ?> <?= number_format($p['likes_count']) ?></p>
      </div>
    </a>
  <?php endforeach; ?>
</div>
<?php else: ?>
  <div class="empty-state fade-up"><p><?= icon('heart') ?> No favorites yet. <a href="explore.php">Find something to like.</a></p></div>
<?php endif; ?>
<?php foot(); ?>
