<?php
require_once 'includes/layout.php';
require_once 'includes/auth.php';
require_once 'includes/db.php';

$uid = (int)($_GET['id'] ?? 0);
if (!$uid) redirect('index.php');

$db   = db();
$user = $db->prepare('SELECT id,name,avatar,role,created_at FROM users WHERE id=? AND banned=0');
$user->execute([$uid]);
$prof = $user->fetch();
if (!$prof) { http_response_code(404); echo 'User not found.'; exit; }

$projs = $db->prepare('
  SELECT p.*, (SELECT s.path_or_url FROM screenshots s WHERE s.project_id=p.id ORDER BY s.id LIMIT 1) AS shot, (SELECT s.is_upload FROM screenshots s WHERE s.project_id=p.id ORDER BY s.id LIMIT 1) AS shot_up, c.name AS cat_name
  FROM projects p JOIN categories c ON c.id=p.category_id
  WHERE p.user_id=? AND p.status="published"
  ORDER BY p.likes_count DESC
');
$projs->execute([$uid]);
$projects = $projs->fetchAll();

function cover_grad4(array $p): string {
    return cover_css($p);
}

head(htmlspecialchars($prof['name']).'\'s Profile');
?>
<div class="profile-header fade-up">
  <div class="avatar-wrap">
    <?php if($prof['avatar']): ?>
      <img src="<?= htmlspecialchars($prof['avatar']) ?>" alt="<?= htmlspecialchars($prof['name']) ?>" class="avatar-lg">
    <?php else: ?>
      <div class="avatar-lg avatar-placeholder"><?= mb_strtoupper(mb_substr($prof['name'],0,1)) ?></div>
    <?php endif; ?>
  </div>
  <div>
    <h1><?= htmlspecialchars($prof['name']) ?></h1>
    <p class="page-sub">Member since <?= date('M Y', strtotime($prof['created_at'])) ?> &middot; <?= count($projects) ?> project<?= count($projects)!=1?'s':'' ?></p>
  </div>
</div>

<section class="fade-up">
  <div class="row-h"><h2>Projects</h2></div>
  <?php if($projects): ?>
  <div class="explore-grid">
    <?php foreach($projects as $p): ?>
      <a class="card explore-card" href="project.php?id=<?= $p['id'] ?>">
        <div class="cover" style="<?= cover_grad4($p) ?>"><span class="lic"><?= htmlspecialchars($p['license']) ?></span></div>
        <div class="meta">
          <h3><?= htmlspecialchars($p['name']) ?></h3>
          <p class="mono"><?= htmlspecialchars($p['cat_name']) ?></p>
          <p class="likes"><?= icon('heart') ?> <?= number_format($p['likes_count']) ?></p>
        </div>
      </a>
    <?php endforeach; ?>
  </div>
  <?php else: ?>
    <div class="empty-state"><p>No public projects yet.</p></div>
  <?php endif; ?>
</section>
<?php foot(); ?>
