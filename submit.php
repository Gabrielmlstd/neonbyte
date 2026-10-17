<?php
require_once 'includes/layout.php';
require_once 'includes/auth.php';
require_once 'includes/db.php';
require_once 'includes/upload.php';
require_login();

$user   = current_user();
$errors = [];
$cats   = db()->query('SELECT * FROM categories ORDER BY name')->fetchAll();
$platfs = ['Windows','Linux','macOS','Android','iOS','Web','Other'];
$licenses = ['MIT','GPL-2.0','GPL-3.0','AGPL-3.0','Apache-2.0','BSD-2-Clause','BSD-3-Clause','MPL-2.0','LGPL-2.1','CC-BY-SA-3.0','CC-BY-SA-4.0','Unlicense','Other'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();

    $name     = trim($_POST['name']     ?? '');
    $desc     = trim($_POST['desc']     ?? '');
    $license  = trim($_POST['license']  ?? '');
    $cat_id   = (int)($_POST['cat']     ?? 0);
    $repo_url = trim($_POST['repo_url'] ?? '');
    $dl_url   = trim($_POST['dl_url']   ?? '');
    $vid_url  = trim($_POST['vid_url']  ?? '');
    $help     = isset($_POST['needs_help']) ? 1 : 0;
    $hue      = (int)($_POST['hue']     ?? rand(0,359));
    $selected_plat = array_filter($_POST['platforms'] ?? [], fn($p) => in_array($p, $platfs));

    // Validation
    if (!$name)     $errors[] = 'Project name is required.';
    if (!$desc)     $errors[] = 'Description is required.';
    if (!$license)  $errors[] = 'License is required.';
    if (!$cat_id)   $errors[] = 'Category is required.';
    if (!filter_var($repo_url, FILTER_VALIDATE_URL)) $errors[] = 'Valid repository URL is required.';
    if (!filter_var($dl_url,  FILTER_VALIDATE_URL))  $errors[] = 'Valid download URL is required.';
    if ($vid_url && !filter_var($vid_url, FILTER_VALIDATE_URL)) $errors[] = 'Video URL is invalid.';
    if (!$selected_plat) $errors[] = 'Select at least one platform.';

    // Screenshot limit
    $used = (int) db()->prepare('SELECT COUNT(*) FROM screenshots WHERE user_id=?')
                      ->execute([$user['id']]) ? 0 : 0; // calculated after insert
    $shot_results = [];
    if (empty($errors)) {
        $db = db();
        $db->beginTransaction();
        try {
            $st = $db->prepare('INSERT INTO projects
              (user_id,category_id,name,description,license,repo_url,download_url,video_url,needs_help,hue)
              VALUES (?,?,?,?,?,?,?,?,?,?)');
            $st->execute([$user['id'],$cat_id,$name,$desc,$license,$repo_url,$dl_url,
                          $vid_url ?: null, $help, $hue]);
            $pid = $db->lastInsertId();

            // Platforms
            $stp = $db->prepare('INSERT INTO project_platforms (project_id,platform) VALUES (?,?)');
            foreach ($selected_plat as $p) $stp->execute([$pid, $p]);

            // Screenshots — check user limit
            $limit = (int)$user['screenshot_limit'];
            $used_count = (int) $db->query(
                'SELECT COUNT(*) FROM screenshots WHERE user_id='.(int)$user['id']
            )->fetchColumn();
            $slots = max(0, $limit - $used_count);

            $sts = $db->prepare('INSERT INTO screenshots (project_id,user_id,path_or_url,is_upload) VALUES (?,?,?,?)');
            $added = 0;
            // File uploads
            foreach ($_FILES['screenshots']['tmp_name'] ?? [] as $i => $tmp) {
                if ($added >= $slots) break;
                if ($_FILES['screenshots']['error'][$i] === UPLOAD_ERR_NO_FILE) continue;
                $r = handle_screenshot_upload([
                    'tmp_name' => $tmp,
                    'size'     => $_FILES['screenshots']['size'][$i],
                    'error'    => $_FILES['screenshots']['error'][$i],
                ]);
                if ($r['ok']) { $sts->execute([$pid,$user['id'],$r['path'],$r['is_upload']]); $added++; }
                else $errors[] = 'Screenshot '.($i+1).': '.$r['error'];
            }
            // URL screenshots
            foreach ($_POST['shot_urls'] ?? [] as $url) {
                if ($added >= $slots) break;
                $url = trim($url);
                if (!$url) continue;
                $r = handle_screenshot_url($url);
                if ($r['ok']) { $sts->execute([$pid,$user['id'],$r['path'],$r['is_upload']]); $added++; }
                else $errors[] = 'Screenshot URL: '.$r['error'];
            }

            if (empty($errors)) {
                $db->commit();
                flash('success', 'Project submitted successfully!');
                redirect('project.php?id='.$pid);
            } else {
                $db->rollBack();
            }
        } catch (Throwable $e) {
            $db->rollBack();
            $errors[] = 'Database error. Please try again.';
        }
    }
}

head('Submit a project', 'submit');
?>
<div class="page-header fade-up">
  <h1>Submit a Project</h1>
  <p class="page-sub">Share an open source project with the community.</p>
</div>

<?php if ($errors): ?>
  <div class="alert alert-error">
    <strong>Please fix the following errors:</strong>
    <ul><?php foreach($errors as $e): ?><li><?= htmlspecialchars($e) ?></li><?php endforeach; ?></ul>
  </div>
<?php endif; ?>

<form method="post" action="submit.php" enctype="multipart/form-data" class="form-card fade-up" novalidate>
  <?= csrf_field() ?>

  <section class="form-section">
    <h2 class="form-title">Project info</h2>

    <div class="field-row">
      <div class="field-group">
        <label class="field-label" for="proj-name">Project name *</label>
        <input id="proj-name" name="name" type="text" class="field-input"
               value="<?= htmlspecialchars($_POST['name'] ?? '') ?>" required maxlength="100">
      </div>
      <div class="field-group">
        <label class="field-label" for="proj-cat">Category *</label>
        <select id="proj-cat" name="cat" class="field-input" required>
          <option value="">— choose —</option>
          <?php foreach($cats as $c): ?>
            <option value="<?= $c['id'] ?>" <?= ($_POST['cat']??'')==$c['id']?'selected':'' ?>>
              <?= htmlspecialchars($c['name']) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>
    </div>

    <div class="field-group">
      <label class="field-label" for="proj-desc">Description *</label>
      <textarea id="proj-desc" name="desc" class="field-input" rows="4" required
                maxlength="2000"><?= htmlspecialchars($_POST['desc'] ?? '') ?></textarea>
    </div>

    <div class="field-row">
      <div class="field-group">
        <label class="field-label" for="proj-lic">License *</label>
        <select id="proj-lic" name="license" class="field-input" required>
          <option value="">— choose —</option>
          <?php foreach($licenses as $l): ?>
            <option value="<?= $l ?>" <?= ($_POST['license']??'')===$l?'selected':'' ?>><?= $l ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="field-group">
        <label class="field-label" for="proj-hue">Cover color (hue 0–360)</label>
        <div class="hue-pick">
          <input id="proj-hue" name="hue" type="range" min="0" max="359"
                 value="<?= (int)($_POST['hue'] ?? rand(0,359)) ?>" class="hue-slider">
          <span id="hue-swatch" class="hue-swatch"></span>
        </div>
      </div>
    </div>
  </section>

  <section class="form-section">
    <h2 class="form-title">Platforms *</h2>
    <div class="check-grid">
      <?php foreach($platfs as $p): ?>
        <label class="check-item">
          <input type="checkbox" name="platforms[]" value="<?= $p ?>"
                 <?= in_array($p, $_POST['platforms'] ?? []) ? 'checked' : '' ?>>
          <span><?= $p ?></span>
        </label>
      <?php endforeach; ?>
    </div>
  </section>

  <section class="form-section">
    <h2 class="form-title">Links</h2>
    <div class="field-group">
      <label class="field-label" for="proj-repo">Repository URL *</label>
      <input id="proj-repo" name="repo_url" type="url" class="field-input"
             placeholder="https://github.com/user/project"
             value="<?= htmlspecialchars($_POST['repo_url'] ?? '') ?>" required>
    </div>
    <div class="field-group">
      <label class="field-label" for="proj-dl">Download URL *</label>
      <input id="proj-dl" name="dl_url" type="url" class="field-input"
             placeholder="https://github.com/user/project/releases"
             value="<?= htmlspecialchars($_POST['dl_url'] ?? '') ?>" required>
    </div>
    <div class="field-group">
      <label class="field-label" for="proj-vid">Video URL <span class="opt">(optional)</span></label>
      <input id="proj-vid" name="vid_url" type="url" class="field-input"
             placeholder="https://youtube.com/watch?v=..."
             value="<?= htmlspecialchars($_POST['vid_url'] ?? '') ?>">
    </div>
  </section>

  <section class="form-section">
    <h2 class="form-title">Screenshots <span class="opt">(up to <?= (int)$user['screenshot_limit'] ?> total across all your projects)</span></h2>
    <p class="field-sub">Upload files <strong>or</strong> paste image URLs below.</p>

    <div class="field-group">
      <label class="field-label" for="shots-file">Upload files (jpg / png / webp / gif, max 4 MB each)</label>
      <input id="shots-file" name="screenshots[]" type="file" class="field-input"
             accept="image/jpeg,image/png,image/webp,image/gif" multiple>
    </div>

    <div class="field-group">
      <label class="field-label">External image URLs</label>
      <?php for($i=0;$i<3;$i++): ?>
        <input name="shot_urls[]" type="url" class="field-input" style="margin-bottom:8px"
               placeholder="https://example.com/screenshot.png"
               value="<?= htmlspecialchars(($_POST['shot_urls']??[])[$i] ?? '') ?>">
      <?php endfor; ?>
    </div>
  </section>

  <section class="form-section">
    <label class="check-item">
      <input type="checkbox" name="needs_help" <?= isset($_POST['needs_help'])?'checked':'' ?>>
      <span>This project needs contributors / help</span>
    </label>
  </section>

  <button type="submit" class="btn">Submit project</button>
</form>

<script>
// Live hue preview
const hueSlider = document.getElementById('proj-hue');
const hueSwatch = document.getElementById('hue-swatch');
function updateHue() {
  const h = hueSlider.value;
  hueSwatch.style.background = `linear-gradient(135deg,hsl(${h},90%,35%),hsl(${(+h+60)%360},80%,18%))`;
}
hueSlider?.addEventListener('input', updateHue);
updateHue();
</script>
<?php foot(); ?>
