<?php
require_once '../includes/layout.php';
require_once '../includes/auth.php';
require_once '../includes/db.php';
require_admin();

$db = db();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $rid = (int)($_POST['report_id']  ?? 0);
    $pid = (int)($_POST['project_id'] ?? 0);
    if (isset($_POST['resolve']))       $db->prepare('UPDATE reports SET resolved=1 WHERE id=?')->execute([$rid]);
    if (isset($_POST['remove_project'])){
        $db->prepare('UPDATE projects SET status="removed" WHERE id=?')->execute([$pid]);
        $db->prepare('UPDATE reports SET resolved=1 WHERE project_id=?')->execute([$pid]);
    }
    header('Location: reports.php'); exit;
}

$reports = $db->query('
  SELECT r.*, p.name AS proj_name, u.name AS reporter
  FROM reports r
  JOIN projects p ON p.id = r.project_id
  JOIN users u ON u.id = r.user_id
  WHERE r.resolved = 0
  ORDER BY r.created_at ASC
')->fetchAll();

head('Admin — Reports');
?>
<div class="page-header fade-up"><h1><?= icon('flag') ?> Open Reports <span class="cnt"><?= count($reports) ?></span></h1></div>
<nav class="admin-tabs fade-up">
  <a href="index.php"><?= icon('chart') ?> Dashboard</a>
  <a href="projects.php"><?= icon('file') ?> Projects</a>
  <a href="users.php"><?= icon('user') ?> Users</a>
  <a href="reports.php" class="on"><?= icon('flag') ?> Reports</a>
</nav>

<?php if ($reports): ?>
<div class="dash-table-wrap fade-up">
  <table class="dash-table">
    <thead><tr><th>Project</th><th>Reporter</th><th>Reason</th><th>Date</th><th>Actions</th></tr></thead>
    <tbody>
    <?php foreach($reports as $r): ?>
      <tr>
        <td><a href="../project.php?id=<?= $r['project_id'] ?>" target="_blank"><?= htmlspecialchars($r['proj_name']) ?></a></td>
        <td><?= htmlspecialchars($r['reporter']) ?></td>
        <td><?= htmlspecialchars($r['reason']) ?></td>
        <td><?= date('d/m/Y H:i', strtotime($r['created_at'])) ?></td>
        <td>
          <form method="post" style="display:flex;gap:4px">
            <?= csrf_field() ?>
            <input type="hidden" name="report_id"  value="<?= $r['id'] ?>">
            <input type="hidden" name="project_id" value="<?= $r['project_id'] ?>">
            <button name="resolve" class="btn btn-xs btn-ghost">Dismiss</button>
            <button name="remove_project" class="btn btn-xs btn-danger"
                    onclick="return confirm('Remove project and resolve all reports for it?')">Remove project</button>
          </form>
        </td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
</div>
<?php else: ?>
  <div class="empty-state fade-up"><p><?= icon('check') ?> No open reports.</p></div>
<?php endif; ?>
<?php foot(); ?>
