<?php
require_once 'includes/layout.php';
require_once 'includes/auth.php';
require_once 'includes/db.php';
require_login();

$uid  = (int)current_user()['id'];
$db   = db();
$favs = $db->prepare('
  SELECT p.*, (SELECT s.path_or_url FROM screenshots s WHERE s.project_id=p.id ORDER BY s.id LIMIT 1) AS shot, (SELECT s.is_upload FROM screenshots s WHERE s.project_id=p.id ORDER BY s.id LIMIT 1) AS shot_up, c.name AS cat_name, c.slug AS cat_slug
  FROM favorites f
  JOIN projects p ON p.id = f.project_id AND p.status="published"
  JOIN categories c ON c.id = p.category_id
  WHERE f.user_id = ?
  ORDER BY f.created_at DESC
');
$favs->execute([$uid]);
$projects = $favs->fetchAll();

function cover_grad2(array $p): string {
    return cover_css($p);
}

head('My Library', 'lib');
?>
<div class="page-header fade-up">
  <h1>My Library</h1>
  <p class="page-sub">Projects you saved to your collection.</p>
</div>

<?php if ($projects): ?>
<div class="explore-grid fade-up">
  <?php foreach($projects as $p): ?>
    <a class="card explore-card" href="project.php?id=<?= $p['id'] ?>">
      <div class="cover" style="<?= cover_grad2($p) ?>">
        <span class="lic"><?= htmlspecialchars($p['license']) ?></span>
      </div>
      <div class="meta">
        <h3><?= htmlspecialchars($p['name']) ?></h3>
        <p class="mono"><?= htmlspecialchars($p['cat_name']) ?></p>
        <p class="likes"><?= icon('heart') ?> <?= number_format($p['likes_count']) ?></p>
      </div>
    </a>
  <?php endforeach; ?>
</div>
<?php else: ?>
  <div class="empty-state fade-up">
    <p><?= icon('library') ?> Your library is empty. <a href="explore.php">Explore projects</a> and save your favorites.</p>
  </div>
<?php endif; ?>
<?php foot(); ?>
