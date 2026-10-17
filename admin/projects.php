<?php
require_once '../includes/layout.php';
require_once '../includes/auth.php';
require_once '../includes/db.php';
require_admin();

$db = db();

// Actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $pid = (int)($_POST['project_id'] ?? 0);
    if (isset($_POST['remove']))    $db->prepare('UPDATE projects SET status="removed" WHERE id=?')->execute([$pid]);
    if (isset($_POST['restore']))   $db->prepare('UPDATE projects SET status="published" WHERE id=?')->execute([$pid]);
    if (isset($_POST['feature']))   $db->prepare('UPDATE projects SET featured=1 WHERE id=?')->execute([$pid]);
    if (isset($_POST['unfeature'])) $db->prepare('UPDATE projects SET featured=0 WHERE id=?')->execute([$pid]);
    header('Location: projects.php'); exit;
}

$page = max(1,(int)($_GET['page'] ?? 1)); $per=30; $off=($page-1)*$per;
$total = (int)$db->query('SELECT COUNT(*) FROM projects')->fetchColumn();
$pages = (int)ceil($total/$per);

$projs = $db->prepare('
  SELECT p.*,u.name AS author,c.name AS cat
  FROM projects p JOIN users u ON u.id=p.user_id JOIN categories c ON c.id=p.category_id
  ORDER BY p.created_at DESC LIMIT '.$per.' OFFSET '.$off
);
$projs->execute();
$projects = $projs->fetchAll();

head('Admin — Projects');
?>
<div class="page-header fade-up"><h1><?= icon('file') ?> Manage Projects</h1></div>
<nav class="admin-tabs fade-up">
  <a href="index.php"><?= icon('chart') ?> Dashboard</a>
  <a href="projects.php" class="on"><?= icon('file') ?> Projects</a>
  <a href="users.php"><?= icon('user') ?> Users</a>
  <a href="reports.php"><?= icon('flag') ?> Reports</a>
</nav>

<div class="dash-table-wrap fade-up">
  <table class="dash-table">
    <thead><tr><th>ID</th><th>Name</th><th>Cat</th><th>Author</th><th>Status</th><th>Featured</th><th>Likes</th><th>Actions</th></tr></thead>
    <tbody>
    <?php foreach($projects as $p): ?>
      <tr>
        <td><?= $p['id'] ?></td>
        <td><a href="../project.php?id=<?= $p['id'] ?>" target="_blank"><?= htmlspecialchars($p['name']) ?></a></td>
        <td><?= htmlspecialchars($p['cat']) ?></td>
        <td><?= htmlspecialchars($p['author']) ?></td>
        <td><span class="status-badge status-<?= $p['status'] ?>"><?= $p['status'] ?></span></td>
        <td><?= $p['featured'] ? icon('star') : '—' ?></td>
        <td><?= number_format($p['likes_count']) ?></td>
        <td>
          <form method="post" style="display:flex;gap:4px;flex-wrap:wrap">
            <?= csrf_field() ?>
            <input type="hidden" name="project_id" value="<?= $p['id'] ?>">
            <?php if($p['status']==='published'): ?>
              <button name="remove" class="btn btn-xs btn-danger" onclick="return confirm('Remove?')">Remove</button>
            <?php else: ?>
              <button name="restore" class="btn btn-xs">Restore</button>
            <?php endif; ?>
            <?php if(!$p['featured']): ?>
              <button name="feature" class="btn btn-xs btn-ghost">Feature</button>
            <?php else: ?>
              <button name="unfeature" class="btn btn-xs btn-ghost">Unfeature</button>
            <?php endif; ?>
          </form>
        </td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
</div>

<!-- Pagination -->
<?php if ($pages > 1): ?>
<nav class="pagination fade-up">
  <?php for($i=1;$i<=$pages;$i++): ?>
    <a href="?page=<?= $i ?>" class="page-link <?= $i===$page?'active':'' ?>"><?= $i ?></a>
  <?php endfor; ?>
</nav>
<?php endif; ?>
<?php foot(); ?>
