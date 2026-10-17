<?php
require_once 'includes/layout.php';
require_once 'includes/auth.php';
require_once 'includes/db.php';
require_once 'includes/upload.php';
require_login();

$uid = (int)current_user()['id'];
$pid = (int)($_GET['id'] ?? 0);
$db  = db();

// Load project — owner or admin only
$st = $db->prepare('SELECT * FROM projects WHERE id=?');
$st->execute([$pid]);
$p = $st->fetch();
if (!$p || ($p['user_id'] !== $uid && !is_admin())) {
    http_response_code(403); exit('Access denied.');
}

$cats = $db->query('SELECT * FROM categories ORDER BY name')->fetchAll();
$platfs = ['Windows','Linux','macOS','Android','iOS','Web','Other'];
$licenses = ['MIT','GPL-2.0','GPL-3.0','AGPL-3.0','Apache-2.0','BSD-2-Clause','BSD-3-Clause','MPL-2.0','LGPL-2.1','CC-BY-SA-3.0','CC-BY-SA-4.0','Unlicense'];

// Current platforms
$cpst = $db->prepare('SELECT platform FROM project_platforms WHERE project_id=?');
$cpst->execute([$pid]);
$cur_plats = $cpst->fetchAll(PDO::FETCH_COLUMN);

// Current screenshots
$cshots = $db->prepare('SELECT * FROM screenshots WHERE project_id=?');
$cshots->execute([$pid]);
$screenshots = $cshots->fetchAll();

$error   = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();

    $name     = trim($_POST['name']         ?? '');
    $desc     = trim($_POST['description']  ?? '');
    $cat_id   = (int)($_POST['category_id'] ?? 0);
    $license  = $_POST['license']           ?? '';
    $repo     = trim($_POST['repo_url']     ?? '');
    $dl       = trim($_POST['download_url'] ?? '');
    $vid      = trim($_POST['video_url']    ?? '');
    $hue      = min(360, max(0, (int)($_POST['hue'] ?? 180)));
    $needs    = isset($_POST['needs_help']) ? 1 : 0;
    $plats    = array_filter($_POST['platforms'] ?? []);

    // Validate
    if (!$name || !$desc || !$cat_id || !$license || !$repo || !$dl)
        $error = 'Please fill in all required fields.';

    if (!$error) {
        $db->prepare('UPDATE projects SET name=?,description=?,category_id=?,license=?,repo_url=?,download_url=?,video_url=?,hue=?,needs_help=? WHERE id=?')
           ->execute([$name,$desc,$cat_id,$license,$repo,$dl,$vid?:null,$hue,$needs,$pid]);

        // Platforms
        $db->prepare('DELETE FROM project_platforms WHERE project_id=?')->execute([$pid]);
        foreach ($plats as $pl) {
            $db->prepare('INSERT INTO project_platforms (project_id,platform) VALUES (?,?)')->execute([$pid,$pl]);
        }

        // Screenshots: delete removed ones
        $del_shots = array_filter($_POST['del_screenshot'] ?? [], 'is_numeric');
        foreach ($del_shots as $sid) {
            $row = $db->prepare('SELECT path_or_url,is_upload FROM screenshots WHERE id=? AND project_id=?');
            $row->execute([(int)$sid, $pid]);
            $s = $row->fetch();
            if ($s && $s['is_upload']) {
                $file = UPLOAD_DIR . $s['path_or_url'];
                if (file_exists($file)) @unlink($file);
            }
            $db->prepare('DELETE FROM screenshots WHERE id=?')->execute([(int)$sid]);
        }

        // New screenshot uploads / URL entries
        $user_row = $db->prepare('SELECT screenshot_limit FROM users WHERE id=?');
        $user_row->execute([$uid]);
        $limit = (int)$user_row->fetchColumn();
        $used  = (int)$db->query('SELECT COUNT(*) FROM screenshots WHERE project_id='.$pid)->fetchColumn();
        $slots = max(0, $limit - $used);

        $idx = 0;
        foreach (($_FILES['screenshots']['name'] ?? []) as $i => $fname) {
            if ($idx >= $slots) break;
            if ($_FILES['screenshots']['error'][$i] !== UPLOAD_ERR_OK) continue;
            $r = handle_screenshot_upload([
                'tmp_name' => $_FILES['screenshots']['tmp_name'][$i],
                'error'    => $_FILES['screenshots']['error'][$i],
                'size'     => $_FILES['screenshots']['size'][$i],
            ]);
            if ($r['ok']) {
                $db->prepare('INSERT INTO screenshots (project_id,user_id,path_or_url,is_upload) VALUES (?,?,?,1)')->execute([$pid,$uid,$r['path']]);
                $idx++;
            } else { $error = $r['error']; break; }
        }

        // External URL screenshots
        foreach (($_POST['shot_urls'] ?? []) as $url) {
            if ($idx >= $slots) break;
            $url = trim($url);
            if ($url && validate_external_url($url)) {
                $db->prepare('INSERT INTO screenshots (project_id,user_id,path_or_url,is_upload) VALUES (?,?,?,0)')->execute([$pid,$uid,$url]);
                $idx++;
            }
        }

        if (!$error) { $success = 'Project updated!'; }
        // Reload updated project data
        $st->execute([$pid]);
        $p = $st->fetch();
        $cpst->execute([$pid]);
        $cur_plats = $cpst->fetchAll(PDO::FETCH_COLUMN);
        $cshots->execute([$pid]);
        $screenshots = $cshots->fetchAll();
    }
}

head('Edit Project');
?>
<div class="page-header fade-up">
  <h1>Edit Project</h1>
  <p class="page-sub"><a href="project.php?id=<?= $pid ?>"><?= icon('arrow-left') ?> Back to project</a></p>
</div>

<?php if ($error): ?><div class="alert alert-error"><?= htmlspecialchars($error) ?></div><?php endif; ?>
<?php if ($success): ?><div class="alert alert-ok"><?= htmlspecialchars($success) ?></div><?php endif; ?>

<form method="post" enctype="multipart/form-data" class="form-card fade-up">
  <?= csrf_field() ?>

  <!-- Basic info -->
  <div class="field-group">
    <label class="field-label" for="ep-name">Project name *</label>
    <input id="ep-name" name="name" type="text" class="field-input" required maxlength="120"
           value="<?= htmlspecialchars($p['name']) ?>">
  </div>
  <div class="field-group">
    <label class="field-label" for="ep-desc">Description *</label>
    <textarea id="ep-desc" name="description" class="field-input" rows="5" required maxlength="5000"><?= htmlspecialchars($p['description']) ?></textarea>
  </div>
  <div class="field-row">
    <div class="field-group">
      <label class="field-label" for="ep-cat">Category *</label>
      <select id="ep-cat" name="category_id" class="field-input" required>
        <?php foreach($cats as $c): ?>
          <option value="<?= $c['id'] ?>" <?= $c['id']==$p['category_id']?'selected':'' ?>><?= htmlspecialchars($c['name']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="field-group">
      <label class="field-label" for="ep-lic">License *</label>
      <select id="ep-lic" name="license" class="field-input" required>
        <?php foreach($licenses as $l): ?>
          <option value="<?= $l ?>" <?= $l===$p['license']?'selected':'' ?>><?= $l ?></option>
        <?php endforeach; ?>
      </select>
    </div>
  </div>

  <!-- URLs -->
  <div class="form-section">
    <h2>Links</h2>
    <div class="field-group">
      <label class="field-label" for="ep-repo">Repository URL *</label>
      <input id="ep-repo" name="repo_url" type="url" class="field-input" required
             value="<?= htmlspecialchars($p['repo_url']) ?>">
    </div>
    <div class="field-row">
      <div class="field-group">
        <label class="field-label" for="ep-dl">Download URL *</label>
        <input id="ep-dl" name="download_url" type="url" class="field-input" required
               value="<?= htmlspecialchars($p['download_url']) ?>">
      </div>
      <div class="field-group">
        <label class="field-label" for="ep-vid">Video URL (YouTube, optional)</label>
        <input id="ep-vid" name="video_url" type="url" class="field-input"
               value="<?= htmlspecialchars($p['video_url'] ?? '') ?>">
      </div>
    </div>
  </div>

  <!-- Platforms -->
  <div class="form-section">
    <h2>Platforms</h2>
    <div class="check-group">
      <?php foreach($platfs as $pl): ?>
        <label class="check-item">
          <input type="checkbox" name="platforms[]" value="<?= $pl ?>"
                 <?= in_array($pl,$cur_plats)?'checked':'' ?>> <?= $pl ?>
        </label>
      <?php endforeach; ?>
    </div>
  </div>

  <!-- Cover hue -->
  <div class="form-section">
    <h2>Cover Colour</h2>
    <div class="field-group">
      <label class="field-label" for="hue">Hue (0–360)</label>
      <input id="hue" name="hue" type="range" min="0" max="360"
             value="<?= (int)$p['hue'] ?>" style="width:100%;accent-color:var(--pri)">
      <div class="hue-preview"><div class="hue-marker"></div></div>
    </div>
  </div>

  <!-- Needs help -->
  <div class="field-group">
    <label class="check-item" style="display:inline-flex">
      <input type="checkbox" name="needs_help" <?= $p['needs_help']?'checked':'' ?>>
      &nbsp;Looking for contributors / help wanted
    </label>
  </div>

  <!-- Existing screenshots -->
  <?php if ($screenshots): ?>
  <div class="form-section">
    <h2>Current Screenshots</h2>
    <div class="gallery">
      <?php foreach($screenshots as $s): ?>
        <label class="gallery-thumb" style="cursor:default;position:relative">
          <img src="<?= $s['is_upload'] ? UPLOAD_URL.htmlspecialchars($s['path_or_url']) : htmlspecialchars($s['path_or_url']) ?>" alt="">
          <input type="checkbox" name="del_screenshot[]" value="<?= $s['id'] ?>"
                 style="position:absolute;top:4px;right:4px;accent-color:#ff5a5a;width:18px;height:18px"
                 title="Check to delete this screenshot">
        </label>
      <?php endforeach; ?>
    </div>
    <p class="field-hint">Check a screenshot and save to delete it.</p>
  </div>
  <?php endif; ?>

  <!-- New screenshots -->
  <div class="form-section">
    <h2>Add Screenshots</h2>
    <div class="field-group">
      <label class="field-label" for="ep-shots">Upload images (JPEG/PNG/WebP/GIF, max 4 MB each)</label>
      <input id="ep-shots" name="screenshots[]" type="file" class="field-input"
             accept="image/jpeg,image/png,image/webp,image/gif" multiple>
    </div>
    <p class="field-hint" style="margin:-12px 0 12px">Or add external URLs (one per line):</p>
    <div class="field-group">
      <textarea name="shot_urls[]" class="field-input" rows="3"
                placeholder="https://example.com/screenshot1.png"></textarea>
    </div>
  </div>

  <div style="display:flex;gap:12px;margin-top:12px">
    <button type="submit" class="btn">Save changes</button>
    <a href="project.php?id=<?= $pid ?>" class="btn btn-ghost">Cancel</a>
  </div>
</form>
<?php foot(); ?>
