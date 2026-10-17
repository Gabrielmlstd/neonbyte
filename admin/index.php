<?php
require_once '../includes/layout.php';
require_once '../includes/auth.php';
require_once '../includes/db.php';
require_admin();

$db = db();
$stats = [
    'projects' => $db->query('SELECT COUNT(*) FROM projects WHERE status="published"')->fetchColumn(),
    'users'    => $db->query('SELECT COUNT(*) FROM users')->fetchColumn(),
    'reports'  => $db->query('SELECT COUNT(*) FROM reports WHERE resolved=0')->fetchColumn(),
    'comments' => $db->query('SELECT COUNT(*) FROM comments')->fetchColumn(),
];

// Recent projects
$recent = $db->query('
  SELECT p.*, u.name AS author, c.name AS cat
  FROM projects p JOIN users u ON u.id=p.user_id JOIN categories c ON c.id=p.category_id
  ORDER BY p.created_at DESC LIMIT 10
')->fetchAll();

head('Admin Panel');
?>
<div class="page-header fade-up">
  <h1><?= icon('settings') ?> Admin Panel</h1>
  <p class="page-sub">Full control of Neonbyte.</p>
</div>

<!-- Stats -->
<div class="stat-row fade-up">
  <div class="stat-card"><span class="stat-val"><?= $stats['projects'] ?></span><span class="stat-lbl">Projects</span></div>
  <div class="stat-card"><span class="stat-val"><?= $stats['users'] ?></span><span class="stat-lbl">Users</span></div>
  <div class="stat-card" style="<?= $stats['reports']>0?'border-color:var(--acc)':'' ?>">
    <span class="stat-val" style="<?= $stats['reports']>0?'color:var(--acc)':'' ?>"><?= $stats['reports'] ?></span>
    <span class="stat-lbl">Open reports</span>
  </div>
  <div class="stat-card"><span class="stat-val"><?= $stats['comments'] ?></span><span class="stat-lbl">Comments</span></div>
</div>

<!-- Nav tabs -->
<nav class="admin-tabs fade-up">
  <a href="index.php" class="on"><?= icon('chart') ?> Dashboard</a>
  <a href="projects.php"><?= icon('file') ?> Projects</a>
  <a href="users.php"><?= icon('user') ?> Users</a>
  <a href="reports.php" <?= $stats['reports']>0?'class="tab-alert"':'' ?>><?= icon('flag') ?> Reports <?= $stats['reports']>0?"<span class='badge'>{$stats['reports']}</span>":'' ?></a>
</nav>

<!-- Recent projects -->
<section class="dash-section fade-up">
  <h2>Recent Projects</h2>
  <div class="dash-table-wrap">
    <table class="dash-table">
      <thead><tr><th>Name</th><th>Category</th><th>Author</th><th>Status</th><th>Likes</th><th>Views</th><th>Date</th></tr></thead>
      <tbody>
      <?php foreach($recent as $p): ?>
        <tr>
          <td><a href="../project.php?id=<?= $p['id'] ?>"><?= htmlspecialchars($p['name']) ?></a></td>
          <td><?= htmlspecialchars($p['cat']) ?></td>
          <td><?= htmlspecialchars($p['author']) ?></td>
          <td><span class="status-badge status-<?= $p['status'] ?>"><?= $p['status'] ?></span></td>
          <td><?= number_format($p['likes_count']) ?></td>
          <td><?= number_format($p['views']) ?></td>
          <td><?= date('d/m/Y', strtotime($p['created_at'])) ?></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</section>
<?php foot(); ?>
