<?php
require_once 'includes/layout.php';
require_once 'includes/auth.php';
require_once 'includes/db.php';

$id = (int)($_GET['id'] ?? 0);
if (!$id) { header('Location: explore.php'); exit; }

$db = db();

// Fetch project
$st = $db->prepare('
  SELECT p.*, (SELECT s.path_or_url FROM screenshots s WHERE s.project_id=p.id ORDER BY s.id LIMIT 1) AS shot, (SELECT s.is_upload FROM screenshots s WHERE s.project_id=p.id ORDER BY s.id LIMIT 1) AS shot_up, c.name AS cat_name, c.slug AS cat_slug,
         u.name AS author_name, u.id AS author_id
  FROM projects p
  JOIN categories c ON c.id = p.category_id
  JOIN users u ON u.id = p.user_id
  WHERE p.id = ? AND p.status = "published"
');
$st->execute([$id]);
$p = $st->fetch();
if (!$p) { http_response_code(404); echo 'Project not found.'; exit; }

// Increment views (once per session)
if (empty($_SESSION['viewed'][$id])) {
    $_SESSION['viewed'][$id] = true;
    $db->prepare('UPDATE projects SET views = views + 1 WHERE id = ?')->execute([$id]);
}

// Platforms
$plat = $db->prepare('SELECT platform FROM project_platforms WHERE project_id = ?');
$plat->execute([$id]);
$platforms = $plat->fetchAll(PDO::FETCH_COLUMN);

// Screenshots
$shots = $db->prepare('SELECT path_or_url, is_upload FROM screenshots WHERE project_id = ?');
$shots->execute([$id]);
$screenshots = $shots->fetchAll();

// Comments
$coms = $db->prepare('
  SELECT cm.*, u.name AS uname, u.avatar FROM comments cm
  JOIN users u ON u.id = cm.user_id
  WHERE cm.project_id = ? ORDER BY cm.created_at DESC
');
$coms->execute([$id]);
$comments = $coms->fetchAll();

// Rating average
$ratingRow = $db->prepare('SELECT AVG(rating) AS avg, COUNT(*) AS cnt FROM comments WHERE project_id = ? AND rating IS NOT NULL');
$ratingRow->execute([$id]);
$rating = $ratingRow->fetch();

// Like / Favorite status for logged-in user
$user      = current_user();
$is_liked  = false;
$is_fav    = false;
if ($user) {
    $lk = $db->prepare('SELECT 1 FROM likes WHERE user_id=? AND project_id=?');
    $lk->execute([$user['id'],$id]);
    $is_liked = (bool)$lk->fetchColumn();
    $fv = $db->prepare('SELECT 1 FROM favorites WHERE user_id=? AND project_id=?');
    $fv->execute([$user['id'],$id]);
    $is_fav = (bool)$fv->fetchColumn();
}

// Comment post
$com_error = '';
if ($user && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['comment_body'])) {
    csrf_verify();
    $body   = trim($_POST['comment_body'] ?? '');
    $rval   = ($_POST['rating'] ?? '') !== '' ? (int)$_POST['rating'] : null;
    if (!$body) { $com_error = 'Comment cannot be empty.'; }
    elseif ($rval !== null && ($rval < 1 || $rval > 5)) { $com_error = 'Invalid rating.'; }
    else {
        $db->prepare('INSERT INTO comments (project_id,user_id,rating,body) VALUES (?,?,?,?)')
           ->execute([$id, $user['id'], $rval, $body]);
        $o = $db->prepare('SELECT user_id,name FROM projects WHERE id=?'); $o->execute([$id]); $o = $o->fetch();
        if ($o && (int)$o['user_id'] !== (int)$user['id']) notify((int)$o['user_id'],'comment',$user['name'].' commented on '.$o['name'],'project.php?id='.$id.'#comments');
        header('Location: project.php?id='.$id.'#comments');
        exit;
    }
}

// Helper: screenshot URL
function shot_url(array $shot): string {
    if ($shot['is_upload']) return UPLOAD_URL . $shot['path_or_url'];
    return htmlspecialchars($shot['path_or_url']);
}

// Stars display
function stars(float $avg, int $max = 5): string {
    $out = '';
    for ($i = 1; $i <= $max; $i++) {
        $out .= '<span class="star'.($i <= round($avg) ? ' on' : '').'">'.icon('star').'</span>';
    }
    return $out;
}

// Cover gradient
function cover_style(array $p): string { return cover_css($p); }

head(htmlspecialchars($p['name']));
?>

<!-- ── Breadcrumb ── -->
<nav class="breadcrumb fade-up" aria-label="Breadcrumb">
  <a href="index.php">Home</a> <span>›</span>
  <a href="explore.php?cat=<?= $p['cat_slug'] ?>"><?= htmlspecialchars($p['cat_name']) ?></a>
  <span>›</span> <span><?= htmlspecialchars($p['name']) ?></span>
</nav>

<!-- ── Hero banner ── -->
<div class="proj-hero fade-up" style="<?= cover_style($p) ?>">
  <div class="proj-hero-overlay"></div>
  <div class="proj-hero-content">
    <div class="proj-meta-top">
      <span class="tag"><?= htmlspecialchars($p['cat_name']) ?></span>
      <span class="tag tag-lic"><?= htmlspecialchars($p['license']) ?></span>
      <?php if($p['needs_help']): ?><span class="tag tag-help">Needs contributors</span><?php endif; ?>
    </div>
    <h1><?= htmlspecialchars($p['name']) ?></h1>
    <p class="proj-sub">by <a href="profile.php?id=<?= $p['author_id'] ?>"><?= htmlspecialchars($p['author_name']) ?></a></p>
    <div class="proj-stats">
      <span><?= icon('heart') ?> <?= number_format($p['likes_count']) ?> likes</span>
      <span><?= icon('eye') ?> <?= number_format($p['views']) ?> views</span>
      <?php if ($rating['cnt'] > 0): ?>
        <span><?= stars((float)$rating['avg']) ?> <?= round((float)$rating['avg'],1) ?> (<?= $rating['cnt'] ?>)</span>
      <?php endif; ?>
    </div>
    <div class="proj-actions">
      <a href="<?= htmlspecialchars($p['download_url']) ?>" target="_blank" rel="noopener"
         class="btn" id="dl-btn-<?= $id ?>"
         onclick="fetch('api/click.php?id=<?= $id ?>')">Download</a>
      <a href="<?= htmlspecialchars($p['repo_url']) ?>" target="_blank" rel="noopener"
         class="btn btn-ghost">Repository</a>
      <?php if($user): ?>
        <button class="btn btn-icon like-btn <?= $is_liked?'liked':'' ?>"
                data-id="<?= $id ?>" aria-label="Like" title="Like">
          <?= icon('heart') ?> <span class="like-count"><?= number_format($p['likes_count']) ?></span>
        </button>
        <button class="btn btn-icon fav-btn <?= $is_fav?'faved':'' ?>"
                data-id="<?= $id ?>" aria-label="Save to favorites" title="Save">
          <?= icon('bookmark') ?>
        </button>
      <?php endif; ?>
    </div>
  </div>
</div>

<!-- ── Screenshots gallery ── -->
<?php if ($screenshots): ?>
<section class="proj-section fade-up" aria-label="Screenshots">
  <h2 class="section-title">Screenshots</h2>
  <div class="gallery">
    <?php foreach($screenshots as $i=>$s): ?>
      <button class="gallery-thumb" onclick="openLightbox(<?= $i ?>)" aria-label="View screenshot <?= $i+1 ?>">
        <img src="<?= shot_url($s) ?>" alt="Screenshot <?= $i+1 ?>" loading="lazy">
      </button>
    <?php endforeach; ?>
  </div>
</section>

<!-- Lightbox -->
<div id="lightbox" class="lightbox" role="dialog" aria-modal="true" aria-label="Screenshot viewer" hidden>
  <button class="lb-close" onclick="closeLightbox()" aria-label="Close"><?= icon('x') ?></button>
  <button class="lb-prev" onclick="lbPrev()" aria-label="Previous"><?= icon('chevron-left') ?></button>
  <img id="lb-img" src="" alt="Screenshot">
  <button class="lb-next" onclick="lbNext()" aria-label="Next"><?= icon('chevron-right') ?></button>
  <p id="lb-counter"></p>
</div>
<script>
const shots=[<?php foreach($screenshots as $s): ?>'<?= addslashes(shot_url($s)) ?>',<?php endforeach; ?>];
let lbIdx=0;
function openLightbox(i){lbIdx=i;document.getElementById('lightbox').hidden=false;lbShow();}
function closeLightbox(){document.getElementById('lightbox').hidden=true;}
function lbPrev(){lbIdx=(lbIdx-1+shots.length)%shots.length;lbShow();}
function lbNext(){lbIdx=(lbIdx+1)%shots.length;lbShow();}
function lbShow(){document.getElementById('lb-img').src=shots[lbIdx];document.getElementById('lb-counter').textContent=(lbIdx+1)+' / '+shots.length;}
document.getElementById('lightbox')?.addEventListener('click',function(e){if(e.target===this)closeLightbox();});
document.addEventListener('keydown',e=>{if(e.key==='Escape')closeLightbox();if(e.key==='ArrowLeft')lbPrev();if(e.key==='ArrowRight')lbNext();});
</script>
<?php endif; ?>

<!-- ── Video ── -->
<?php if ($p['video_url']): ?>
<section class="proj-section fade-up" aria-label="Video">
  <h2 class="section-title">Video</h2>
  <?php
    $vid = $p['video_url'];
    // YouTube embed
    if (preg_match('/(?:youtube\.com\/watch\?v=|youtu\.be\/)([a-zA-Z0-9_-]+)/', $vid, $m)):
  ?>
    <div class="video-wrap">
      <iframe src="https://www.youtube.com/embed/<?= $m[1] ?>" title="Project video"
              frameborder="0" allowfullscreen loading="lazy"></iframe>
    </div>
  <?php else: ?>
    <a href="<?= htmlspecialchars($vid) ?>" target="_blank" rel="noopener" class="btn btn-ghost">Watch video <?= icon('arrow-up-right') ?></a>
  <?php endif; ?>
</section>
<?php endif; ?>

<!-- ── Description ── -->
<section class="proj-section fade-up">
  <h2 class="section-title">About</h2>
  <div class="prose"><?= nl2br(htmlspecialchars($p['description'])) ?></div>

  <div class="proj-info-grid">
    <div class="info-item"><span class="info-label">License</span><span class="tag tag-lic"><?= htmlspecialchars($p['license']) ?></span></div>
    <div class="info-item"><span class="info-label">Category</span><a href="explore.php?cat=<?= $p['cat_slug'] ?>" class="tag"><?= htmlspecialchars($p['cat_name']) ?></a></div>
    <div class="info-item"><span class="info-label">Platforms</span>
      <span><?php foreach($platforms as $pl): ?><span class="tag"><?= htmlspecialchars($pl) ?></span> <?php endforeach; ?></span>
    </div>
    <div class="info-item"><span class="info-label">Submitted</span><time datetime="<?= $p['created_at'] ?>"><?= date('d M Y', strtotime($p['created_at'])) ?></time></div>
  </div>
</section>

<!-- ── Comments ── -->
<section class="proj-section fade-up" id="comments">
  <h2 class="section-title">Reviews &amp; Comments <span class="cnt"><?= count($comments) ?></span></h2>

  <?php if ($user): ?>
    <?php if ($com_error): ?><div class="alert alert-error"><?= htmlspecialchars($com_error) ?></div><?php endif; ?>
    <form method="post" action="project.php?id=<?= $id ?>#comments" class="comment-form">
      <?= csrf_field() ?>
      <div class="rating-pick" role="group" aria-label="Rating">
        <?php for($r=1;$r<=5;$r++): ?>
          <input type="radio" name="rating" id="r<?= $r ?>" value="<?= $r ?>">
          <label for="r<?= $r ?>" title="<?= $r ?> star<?= $r>1?'s':'' ?>"><?= icon('star') ?></label>
        <?php endfor; ?>
      </div>
      <textarea name="comment_body" class="field-input" rows="3" placeholder="Leave a comment…" required maxlength="2000"></textarea>
      <button type="submit" class="btn">Post comment</button>
    </form>
  <?php else: ?>
    <p class="auth-note"><a href="login.php">Sign in</a> to leave a comment.</p>
  <?php endif; ?>

  <div class="comment-list">
    <?php foreach($comments as $cm): ?>
      <div class="comment-item">
        <div class="comment-meta">
          <span class="comment-name"><?= htmlspecialchars($cm['uname']) ?></span>
          <?php if($cm['rating']): ?><span class="stars"><?= stars($cm['rating']) ?></span><?php endif; ?>
          <time class="comment-date"><?= date('d M Y', strtotime($cm['created_at'])) ?></time>
        </div>
        <p class="comment-body"><?= nl2br(htmlspecialchars($cm['body'])) ?></p>
      </div>
    <?php endforeach; ?>
    <?php if(!$comments): ?><p class="empty-note">No comments yet. Be the first!</p><?php endif; ?>
  </div>
</section>

<!-- ── Report button ── -->
<?php if ($user): ?>
<section class="proj-section fade-up">
  <details class="report-details">
    <summary class="report-toggle"><?= icon('flag') ?> Report this project</summary>
    <form class="report-form" id="report-form">
      <?= csrf_field() ?>
      <input type="hidden" name="project_id" value="<?= $id ?>">
      <input name="reason" type="text" class="field-input" placeholder="Reason for report…" required maxlength="255">
      <button type="submit" class="btn btn-ghost" style="margin-top:8px">Submit report</button>
      <p id="report-msg" style="margin-top:8px;color:var(--pri)"></p>
    </form>
  </details>
</section>
<script>
// Like toggle
document.querySelector('.like-btn')?.addEventListener('click',function(){
  const btn=this, id=btn.dataset.id;
  fetch('api/like.php',{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded','X-CSRF-TOKEN':'<?= csrf_token() ?>'},
    body:'project_id='+id})
    .then(r=>r.json()).then(d=>{
      btn.classList.toggle('liked',d.liked);
      btn.querySelector('.like-count').textContent=d.count.toLocaleString();
    });
});
// Fav toggle
document.querySelector('.fav-btn')?.addEventListener('click',function(){
  const btn=this, id=btn.dataset.id;
  fetch('api/favorite.php',{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded','X-CSRF-TOKEN':'<?= csrf_token() ?>'},
    body:'project_id='+id})
    .then(r=>r.json()).then(d=>{
      btn.classList.toggle('faved',d.faved);
    });
});
// Report
document.getElementById('report-form')?.addEventListener('submit',function(e){
  e.preventDefault();
  const fd=new FormData(this);
  const params=new URLSearchParams(fd);
  fetch('api/report.php',{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded','X-CSRF-TOKEN':'<?= csrf_token() ?>'},
    body:params})
    .then(r=>r.json()).then(d=>{
      document.getElementById('report-msg').textContent=d.message||'Reported.';
    });
});
</script>
<?php endif; ?>

<?php foot(); ?>
