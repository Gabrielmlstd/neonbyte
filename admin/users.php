<?php
require_once '../includes/layout.php';
require_once '../includes/auth.php';
require_once '../includes/db.php';
require_admin();

$db = db();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $uid = (int)($_POST['user_id'] ?? 0);
    if (isset($_POST['ban']))    $db->prepare('UPDATE users SET banned=1 WHERE id=?')->execute([$uid]);
    if (isset($_POST['unban']))  $db->prepare('UPDATE users SET banned=0 WHERE id=?')->execute([$uid]);
    if (isset($_POST['make_admin'])) $db->prepare('UPDATE users SET role="admin" WHERE id=?')->execute([$uid]);
    if (isset($_POST['set_limit'])) {
        $limit = max(0, min(50, (int)$_POST['limit']));
        $db->prepare('UPDATE users SET screenshot_limit=? WHERE id=?')->execute([$limit, $uid]);
    }
    header('Location: users.php'); exit;
}

$page=max(1,(int)($_GET['page']??1)); $per=30; $off=($page-1)*$per;
$total = (int)$db->query('SELECT COUNT(*) FROM users')->fetchColumn();
$pages = (int)ceil($total/$per);
$users = $db->query('SELECT * FROM users ORDER BY created_at DESC LIMIT '.$per.' OFFSET '.$off)->fetchAll();

head('Admin — Users');
?>
<div class="page-header fade-up"><h1><?= icon('user') ?> Manage Users</h1></div>
<nav class="admin-tabs fade-up">
  <a href="index.php"><?= icon('chart') ?> Dashboard</a>
  <a href="projects.php"><?= icon('file') ?> Projects</a>
  <a href="users.php" class="on"><?= icon('user') ?> Users</a>
  <a href="reports.php"><?= icon('flag') ?> Reports</a>
</nav>

<div class="dash-table-wrap fade-up">
  <table class="dash-table">
    <thead><tr><th>ID</th><th>Name</th><th>E-mail</th><th>Role</th><th>Screenshots limit</th><th>Banned</th><th>Actions</th></tr></thead>
    <tbody>
    <?php foreach($users as $u): ?>
      <tr>
        <td><?= $u['id'] ?></td>
        <td><a href="../profile.php?id=<?= $u['id'] ?>"><?= htmlspecialchars($u['name']) ?></a></td>
        <td><?= htmlspecialchars(nb_mask_email($u['email_encrypted'] ?? '')) ?></td>
        <td><span class="tag <?= $u['role']==='admin'?'tag-admin':'' ?>"><?= $u['role'] ?></span></td>
        <td><?= $u['screenshot_limit'] ?></td>
        <td><?= $u['banned'] ? 'Yes' : 'No' ?></td>
        <td>
          <form method="post" style="display:flex;gap:4px;flex-wrap:wrap;align-items:center">
            <?= csrf_field() ?>
            <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
            <?php if(!$u['banned']): ?>
              <button name="ban" class="btn btn-xs btn-danger" onclick="return confirm('Ban user?')">Ban</button>
            <?php else: ?>
              <button name="unban" class="btn btn-xs">Unban</button>
            <?php endif; ?>
            <?php if($u['role'] !== 'admin'): ?>
              <button name="make_admin" class="btn btn-xs btn-ghost" onclick="return confirm('Make admin?')">Make admin</button>
            <?php endif; ?>
            <input type="number" name="limit" value="<?= $u['screenshot_limit'] ?>" min="0" max="50" style="width:55px" class="field-input">
            <button name="set_limit" class="btn btn-xs btn-ghost">Set limit</button>
          </form>
        </td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
</div>

<?php if ($pages > 1): ?>
<nav class="pagination fade-up">
  <?php for($i=1;$i<=$pages;$i++): ?>
    <a href="?page=<?= $i ?>" class="page-link <?= $i===$page?'active':'' ?>"><?= $i ?></a>
  <?php endfor; ?>
</nav>
<?php endif; ?>
<?php foot(); ?>
