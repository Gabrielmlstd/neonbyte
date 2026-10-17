<?php
require_once 'includes/layout.php';
require_once 'includes/data.php';
$all = projects();
$top = $all; usort($top, fn($a,$b)=>$b['likes']<=>$a['likes']); $top = array_slice($top,0,5);
$new = array_values(array_filter($all, fn($p)=>$p['new']));
$help = array_values(array_filter($all, fn($p)=>$p['help']));
head('Open source games and software');
?>

<!-- ═══════════════ HERO BANNER ═══════════════ -->
<section class="hero" aria-label="Most liked projects">
  <div class="stage" id="stage">
  <?php foreach($top as $i=>$p): ?>
    <article class="slide" data-i="<?= $i ?>" style="<?= cover($p) ?>">
      <div class="info">
        <span class="mono"><?= htmlspecialchars($p['cat']) ?> &nbsp;·&nbsp; <?= htmlspecialchars($p['lic']) ?></span>
        <h1><?= htmlspecialchars($p['name']) ?></h1>
        <p><?= htmlspecialchars($p['desc']) ?></p>
        <div>
          <a class="btn" href="project.php?id=<?= $p['id'] ?>">View project</a>
          <span class="likes"><?= icon('heart') ?> <?= number_format($p['likes']) ?></span>
        </div>
      </div>
    </article>
  <?php endforeach; ?>
  </div>

  <!-- progress bar -->
  <div id="hero-progress" class="hero-progress"></div>

  <!-- dot indicators -->
  <div class="hero-dots" aria-label="Slide indicators">
    <?php foreach($top as $i=>$p): ?>
      <button class="hero-dot" aria-label="Go to slide <?= $i+1 ?>"></button>
    <?php endforeach; ?>
  </div>

  <button class="nav prev" aria-label="Previous"><?= icon('chevron-left') ?></button>
  <button class="nav next" aria-label="Next"><?= icon('chevron-right') ?></button>
</section>

<!-- ═══════════════ TRENDING NOW ═══════════════ -->
<section class="fade-up">
  <div class="row-h">
    <h2>Trending now</h2>
    <a href="explore.php?sort=likes">View all &rsaquo;</a>
  </div>
  <div class="strip"><?php foreach($all as $p) echo card($p); ?></div>
</section>

<?php ad('ad-inline','90px'); ?>

<!-- ═══════════════ NEW RELEASES ═══════════════ -->
<section class="fade-up">
  <div class="row-h">
    <h2>New releases</h2>
    <a href="explore.php?sort=new">View all &rsaquo;</a>
  </div>
  <div class="strip"><?php foreach($new as $p) echo card($p); ?></div>
</section>

<?php ad('ad-inline','90px'); ?>

<!-- ═══════════════ NEEDS CONTRIBUTORS ═══════════════ -->
<section class="fade-up">
  <div class="row-h">
    <h2>Needs contributors</h2>
    <a href="explore.php?help=1">View all &rsaquo;</a>
  </div>
  <div class="strip"><?php foreach($help as $p) echo card($p); ?></div>
</section>

<?php foot(); ?>
