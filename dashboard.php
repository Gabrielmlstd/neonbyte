<?php
require_once 'includes/layout.php';
require_once 'includes/auth.php';
require_once 'includes/db.php';
require_login();

$user = current_user();
$uid  = (int)$user['id'];
$db   = db();

$error   = '';
$success = '';

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    csrf_verify();
    $name = trim($_POST['name'] ?? '');
    if (!$name) { $error = 'Name cannot be empty.'; }
    else {
        $db->prepare('UPDATE users SET name=? WHERE id=?')->execute([$name, $uid]);
        $_SESSION['user']['name'] = $name;
        $user = current_user();
        $success = 'Profile updated.';
    }
}

// Handle password change
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    csrf_verify();
    $cur  = $_POST['current_password'] ?? '';
    $new  = $_POST['new_password']     ?? '';
    $new2 = $_POST['new_password2']    ?? '';
    $row  = $db->prepare('SELECT password_hash FROM users WHERE id=?');
    $row->execute([$uid]);
    $row  = $row->fetch();
    if (!password_verify($cur, $row['password_hash'])) { $error = 'Current password is incorrect.'; }
    elseif (strlen($new) < 8)  { $error = 'New password must be at least 8 characters.'; }
    elseif ($new !== $new2)    { $error = 'Passwords do not match.'; }
    else {
        $hash = password_hash($new, PASSWORD_BCRYPT, ['cost'=>12]);
        $db->prepare('UPDATE users SET password_hash=? WHERE id=?')->execute([$hash, $uid]);
        $success = 'Password changed.';
    }
}

// Handle project delete
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_project'])) {
    csrf_verify();
    $pid = (int)$_POST['project_id'];
    $db->prepare('UPDATE projects SET status="removed" WHERE id=? AND user_id=?')->execute([$pid,$uid]);
    $success = 'Project removed.';
}

// My projects
$myprojs = $db->prepare('
  SELECT p.*, c.name AS cat_name FROM projects p
  JOIN categories c ON c.id=p.category_id
  WHERE p.user_id=? ORDER BY p.created_at DESC
');
$myprojs->execute([$uid]);
$myprojects = $myprojs->fetchAll();

// Stats
$total_views  = array_sum(array_column($myprojects,'views'));
$total_clicks = array_sum(array_column($myprojects,'clicks'));
$total_likes  = array_sum(array_column($myprojects,'likes_count'));

// Screenshot usage
$used_shots = (int)$db->prepare('SELECT COUNT(*) FROM screenshots WHERE user_id=?')->execute([$uid])
              ? $db->query('SELECT COUNT(*) FROM screenshots WHERE user_id='.$uid)->fetchColumn() : 0;

head('Dashboard');
?>
<div class="page-header fade-up">
  <h1>Dashboard</h1>
  <p class="page-sub">Manage your projects and account.</p>
</div>

<?php if ($error): ?><div class="alert alert-error"><?= htmlspecialchars($error) ?></div><?php endif; ?>
<?php if ($success): ?><div class="alert alert-ok"><?= htmlspecialchars($success) ?></div><?php endif; ?>

<!-- ── Stats ── -->
<div class="stat-row fade-up">
  <div class="stat-card"><span class="stat-val"><?= count($myprojects) ?></span><span class="stat-lbl">Projects</span></div>
  <div class="stat-card"><span class="stat-val"><?= number_format($total_views) ?></span><span class="stat-lbl">Total views</span></div>
  <div class="stat-card"><span class="stat-val"><?= number_format($total_clicks) ?></span><span class="stat-lbl">Download clicks</span></div>
  <div class="stat-card"><span class="stat-val"><?= number_format($total_likes) ?></span><span class="stat-lbl">Total likes</span></div>
  <div class="stat-card"><span class="stat-val"><?= $used_shots ?>/<?= (int)$user['screenshot_limit'] ?></span><span class="stat-lbl">Screenshots used</span></div>
</div>

<!-- ── My projects ── -->
<section class="dash-section fade-up">
  <div class="row-h">
    <h2>My Projects</h2>
    <a href="submit.php" class="btn">+ New project</a>
  </div>
  <?php if($myprojects): ?>
  <div class="dash-table-wrap">
    <table class="dash-table">
      <thead><tr><th>Project</th><th>Category</th><th>Status</th><th>Views</th><th>Clicks</th><th>Likes</th><th>Actions</th></tr></thead>
      <tbody>
      <?php foreach($myprojects as $p): ?>
        <tr>
          <td><a href="project.php?id=<?= $p['id'] ?>"><?= htmlspecialchars($p['name']) ?></a></td>
          <td><?= htmlspecialchars($p['cat_name']) ?></td>
          <td><span class="status-badge status-<?= $p['status'] ?>"><?= $p['status'] ?></span></td>
          <td><?= number_format($p['views']) ?></td>
          <td><?= number_format($p['clicks']) ?></td>
          <td><?= icon('heart') ?> <?= number_format($p['likes_count']) ?></td>
          <td>
            <a href="edit-project.php?id=<?= $p['id'] ?>" class="btn btn-xs btn-ghost">Edit</a>
            <?php if($p['status']==='published'): ?>
            <form method="post" style="display:inline"
                  onsubmit="return confirm('Remove this project?')">
              <?= csrf_field() ?>
              <input type="hidden" name="project_id" value="<?= $p['id'] ?>">
              <button name="delete_project" class="btn btn-xs btn-danger">Remove</button>
            </form>
            <?php endif; ?>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php else: ?>
    <div class="empty-state"><p>No projects yet. <a href="submit.php">Submit one!</a></p></div>
  <?php endif; ?>
</section>

<!-- ── Profile settings ── -->
<section class="dash-section fade-up">
  <h2>Profile Settings</h2>
  <form method="post" class="form-card-sm">
    <?= csrf_field() ?>
    <div class="field-group">
      <label class="field-label" for="dash-name">Display name</label>
      <input id="dash-name" name="name" type="text" class="field-input"
             value="<?= htmlspecialchars($user['name']) ?>" required>
    </div>
    <button name="update_profile" type="submit" class="btn">Save changes</button>
  </form>
</section>

<!-- ── Password ── -->
<section class="dash-section fade-up">
  <h2>Change Password</h2>
  <form method="post" class="form-card-sm">
    <?= csrf_field() ?>
    <div class="field-group">
      <label class="field-label" for="cur-pass">Current password</label>
      <input id="cur-pass" name="current_password" type="password" class="field-input" required>
    </div>
    <div class="field-group">
      <label class="field-label" for="new-pass">New password</label>
      <input id="new-pass" name="new_password" type="password" class="field-input" required minlength="8">
    </div>
    <div class="field-group">
      <label class="field-label" for="new-pass2">Confirm new password</label>
      <input id="new-pass2" name="new_password2" type="password" class="field-input" required minlength="8">
    </div>
    <button name="change_password" type="submit" class="btn">Change password</button>
  </form>
</section>
<?php foot(); ?>
