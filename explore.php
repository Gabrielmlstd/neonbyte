<?php
require_once 'includes/layout.php';
require_once 'includes/auth.php';
require_once 'includes/db.php';

$db = db();

// ── Build query ────────────────────────────────
$q      = trim($_GET['q']    ?? '');
$cat    = trim($_GET['cat']  ?? '');
$lic    = trim($_GET['lic']  ?? '');
$plat   = trim($_GET['plat'] ?? '');
$sort   = in_array($_GET['sort'] ?? '', ['likes','new','views']) ? $_GET['sort'] : 'likes';
$help   = isset($_GET['help']);
$page   = max(1,(int)($_GET['page'] ?? 1));
$per    = 24;
$offset = ($page-1)*$per;

$where  = ["p.status='published'"];
$params = [];

if ($q) {
    $where[]  = 'MATCH(p.name,p.description) AGAINST(? IN BOOLEAN MODE)';
    $params[] = $q.'*';
}
if ($cat) {
    $where[]  = 'c.slug = ?';
    $params[] = $cat;
}
if ($lic) {
    $where[]  = 'p.license = ?';
    $params[] = $lic;
}
if ($plat) {
    $where[]  = 'EXISTS (SELECT 1 FROM project_platforms pp WHERE pp.project_id=p.id AND pp.platform=?)';
    $params[] = $plat;
}
if ($help) {
    $where[] = 'p.needs_help = 1';
}

$orderBy = match($sort) {
    'new'   => 'p.created_at DESC',
    'views' => 'p.views DESC',
    default => 'p.likes_count DESC',
};

$whereSQL = implode(' AND ', $where);

$total = (int)$db->prepare("SELECT COUNT(*) FROM projects p JOIN categories c ON c.id=p.category_id WHERE $whereSQL")
                  ->execute($params) ? $db->prepare("SELECT COUNT(*) FROM projects p JOIN categories c ON c.id=p.category_id WHERE $whereSQL")->execute($params) || 0 : 0;
// Re-run for actual count
$stC = $db->prepare("SELECT COUNT(*) FROM projects p JOIN categories c ON c.id=p.category_id WHERE $whereSQL");
$stC->execute($params);
$total = (int)$stC->fetchColumn();

$stP = $db->prepare("
  SELECT p.*, (SELECT s.path_or_url FROM screenshots s WHERE s.project_id=p.id ORDER BY s.id LIMIT 1) AS shot, (SELECT s.is_upload FROM screenshots s WHERE s.project_id=p.id ORDER BY s.id LIMIT 1) AS shot_up, c.name AS cat_name, c.slug AS cat_slug,
         GROUP_CONCAT(pp.platform ORDER BY pp.platform SEPARATOR '|') AS plats
  FROM projects p
  JOIN categories c ON c.id = p.category_id
  LEFT JOIN project_platforms pp ON pp.project_id = p.id
  WHERE $whereSQL
  GROUP BY p.id
  ORDER BY $orderBy
  LIMIT $per OFFSET $offset
");
$stP->execute($params);
$projects = $stP->fetchAll();

$cats     = $db->query('SELECT * FROM categories ORDER BY name')->fetchAll();
$licenses = ['MIT','GPL-2.0','GPL-3.0','AGPL-3.0','Apache-2.0','BSD-2-Clause','BSD-3-Clause','MPL-2.0','LGPL-2.1','CC-BY-SA-3.0','CC-BY-SA-4.0','Unlicense'];
$platfs   = ['Windows','Linux','macOS','Android','iOS','Web','Other'];
$pages    = (int)ceil($total / $per);

function cover_grad(array $p): string {
    return cover_css($p);
}

head('Explore', 'explore');
?>
<div class="page-header fade-up">
  <h1>Explore</h1>
  <p class="page-sub"><?= $total ?> projects found<?= $q ? ' for "<strong>'.htmlspecialchars($q).'</strong>"' : '' ?></p>
</div>

<!-- ── Filters ── -->
<form method="get" action="explore.php" class="filter-bar fade-up">
  <input name="q" type="search" class="field-input" placeholder="Search…"
         value="<?= htmlspecialchars($q) ?>" aria-label="Search">

  <select name="cat" class="field-input" aria-label="Category">
    <option value="">All categories</option>
    <?php foreach($cats as $c): ?>
      <option value="<?= $c['slug'] ?>" <?= $c['slug']===$cat?'selected':'' ?>><?= htmlspecialchars($c['name']) ?></option>
    <?php endforeach; ?>
  </select>

  <select name="lic" class="field-input" aria-label="License">
    <option value="">All licenses</option>
    <?php foreach($licenses as $l): ?>
      <option value="<?= $l ?>" <?= $l===$lic?'selected':'' ?>><?= $l ?></option>
    <?php endforeach; ?>
  </select>

  <select name="plat" class="field-input" aria-label="Platform">
    <option value="">All platforms</option>
    <?php foreach($platfs as $pl): ?>
      <option value="<?= $pl ?>" <?= $pl===$plat?'selected':'' ?>><?= $pl ?></option>
    <?php endforeach; ?>
  </select>

  <select name="sort" class="field-input" aria-label="Sort by">
    <option value="likes" <?= $sort==='likes'?'selected':'' ?>>Most liked</option>
    <option value="new"   <?= $sort==='new'?  'selected':'' ?>>Newest</option>
    <option value="views" <?= $sort==='views'?'selected':'' ?>>Most viewed</option>
  </select>

  <label class="check-item">
    <input type="checkbox" name="help" <?= $help?'checked':'' ?>> Needs help
  </label>

  <button type="submit" class="btn">Search</button>
  <?php if ($q||$cat||$lic||$plat||$help): ?>
    <a href="explore.php" class="btn btn-ghost">Clear</a>
  <?php endif; ?>
</form>

<!-- ── Grid ── -->
<?php if ($projects): ?>
<div class="explore-grid fade-up">
  <?php foreach($projects as $p):
    $plats_arr = $p['plats'] ? explode('|',$p['plats']) : [];
  ?>
    <a class="card explore-card" href="project.php?id=<?= $p['id'] ?>">
      <div class="cover" style="<?= cover_grad($p) ?>">
        <span class="lic"><?= htmlspecialchars($p['license']) ?></span>
        <?php if($p['needs_help']): ?><span class="lic lic-help" style="left:auto;right:8px">Help wanted</span><?php endif; ?>
      </div>
      <div class="meta">
        <h3><?= htmlspecialchars($p['name']) ?></h3>
        <p class="mono"><?= htmlspecialchars($p['cat_name']) ?> | <?= htmlspecialchars(implode(', ',$plats_arr)) ?></p>
        <p class="likes"><?= icon('heart') ?> <?= number_format($p['likes_count']) ?></p>
      </div>
      <div class="card-preview"><p><?= htmlspecialchars(mb_substr($p['description'],0,120)) ?>…</p></div>
    </a>
  <?php endforeach; ?>
</div>

<!-- Pagination -->
<?php if ($pages > 1): ?>
<nav class="pagination fade-up" aria-label="Pagination">
  <?php
    $base = '?'.http_build_query(array_filter(['q'=>$q,'cat'=>$cat,'lic'=>$lic,'plat'=>$plat,'sort'=>$sort,'help'=>$help?'1':'']));
    for($i=1;$i<=$pages;$i++):
  ?>
    <a href="<?= $base.'&page='.$i ?>" class="page-link <?= $i===$page?'active':'' ?>"><?= $i ?></a>
  <?php endfor; ?>
</nav>
<?php endif; ?>

<?php else: ?>
  <div class="empty-state fade-up">
    <p><?= icon('search') ?> No projects found. <a href="explore.php">Clear filters</a></p>
  </div>
<?php endif; ?>

<?php foot(); ?>
