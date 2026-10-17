<?php
require_once 'includes/layout.php';
require_once 'includes/auth.php';
require_once 'includes/db.php';

$db = db();
$cats = $db->query('
  SELECT c.*, COUNT(p.id) AS proj_count
  FROM categories c
  LEFT JOIN projects p ON p.category_id=c.id AND p.status="published"
  GROUP BY c.id ORDER BY c.name
')->fetchAll();

head('Categories', 'cats');
?>
<div class="page-header fade-up">
  <h1>Categories</h1>
  <p class="page-sub">Browse projects by type.</p>
</div>

<div class="cat-grid fade-up">
  <?php
  $icons = ['games'=>'gamepad','tools'=>'wrench','dev-tools'=>'terminal','multimedia'=>'film','security'=>'shield','reverse-engineering'=>'chip'];
  foreach($cats as $c):
    $icon = $icons[$c['slug']] ?? 'folder';
  ?>
  <a class="cat-card" href="explore.php?cat=<?= $c['slug'] ?>">
    <span class="cat-icon"><?= icon($icon) ?></span>
    <h2><?= htmlspecialchars($c['name']) ?></h2>
    <p><?= $c['proj_count'] ?> project<?= $c['proj_count']!=1?'s':'' ?></p>
  </a>
  <?php endforeach; ?>
</div>
<?php foot(); ?>
