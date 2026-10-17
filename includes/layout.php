<?php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/icons.php';
require_once __DIR__ . '/data.php';

function ad(string $cls, string $size): void {
    echo '<aside class="ad '.$cls.'" aria-label="Advertisement" data-ad-slot="'.$cls.'">
            <span>Sponsored</span>
            <div style="min-height:'.$size.'"></div>
          </aside>';
}

function head(string $title, string $active = 'home'): void {
    $nav = [
        'home'    => ['../index.php',         'home', 'Home'],
        'explore' => ['../explore.php',       'compass', 'Explore'],
        'cats'    => ['../categories.php',    'grid', 'Categories'],
        'lib'     => ['../library.php',       'library', 'My Library'],
        'fav'     => ['../favorites.php',     'heart', 'Favorites'],
        'submit'  => ['../submit.php',        'plus',   'Submit Project'],
    ];
    // Fix hrefs when called from root vs admin/legal subdirs
    $depth = substr_count($_SERVER['PHP_SELF'], '/') - 1 - substr_count(dirname($_SERVER['SCRIPT_FILENAME']), '/opt/lampp/htdocs/neonbyte');

    // Build simple relative prefix
    $prefix = '';
    $script = $_SERVER['SCRIPT_FILENAME'];
    $base   = realpath(__DIR__.'/../');
    $rel    = str_replace($base, '', dirname($script));
    $depth2 = substr_count(ltrim($rel, '/'), '/') + (ltrim($rel,'/')==='' ? 0 : 1);
    $prefix = str_repeat('../', $depth2);
    if ($prefix === '') $prefix = './';

    $nav = [
        'home'    => [$prefix.'index.php',      'home', 'Home'],
        'explore' => [$prefix.'explore.php',    'compass', 'Explore'],
        'cats'    => [$prefix.'categories.php', 'grid', 'Categories'],
        'lib'     => [$prefix.'library.php',    'library', 'My Library'],
        'fav'     => [$prefix.'favorites.php',  'heart', 'Favorites'],
        'submit'  => [$prefix.'submit.php',     'plus',   'Submit Project'],
    ];

    $user = current_user();
    ?>
<!doctype html>
<html lang="en" data-theme="neon-blue">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<link rel="icon" type="image/svg+xml" href="<?= $prefix ?>assets/img/favicon.svg">
<title><?= htmlspecialchars($title) ?> | Neonbyte</title>
<meta name="description" content="Neonbyte — discover and share open source games and software.">
<!-- Prevent flash of wrong theme -->
<script>try{document.documentElement.classList.add('js');document.documentElement.dataset.theme=localStorage.getItem('nb-theme')||'neon-blue';if(localStorage.getItem('nb-nav')==='1')document.documentElement.classList.add('collapsed')}catch(e){}</script>
<!-- Fonts -->
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Rajdhani:wght@500;700&family=Inter:wght@400;500;600;700;600&family=JetBrains+Mono:wght@400&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= $prefix ?>assets/css/style.css">
<link rel="stylesheet" href="<?= $prefix ?>assets/css/hydra.css"><?php /* Hydra-style layer: delete this line to go back to the neon look */ ?>
</head>
<body>

<!-- ── SIDE NAV ──────────────────────────────── -->
<nav class="side" aria-label="Main navigation">
  <a class="logo" href="<?= $prefix ?>index.php" aria-label="Neonbyte home"><?= logo_mark(32) ?><span>Neonbyte</span></a>
  <?php foreach($nav as $k=>$n): ?>
  <a href="<?= $n[0] ?>" class="<?= $k===$active?'on':'' ?>" title="<?= $n[2] ?>">
    <i aria-hidden="true"><?= icon($n[1]) ?></i><span><?= $n[2] ?></span>
  </a>
  <?php endforeach; ?>
  <div class="side-label">Library</div>
  <div class="lib">
  <?php
    if ($user) {
      $lq = db()->prepare('SELECT p.*,
          (SELECT s.path_or_url FROM screenshots s WHERE s.project_id=p.id ORDER BY s.id LIMIT 1) AS shot,
          (SELECT s.is_upload FROM screenshots s WHERE s.project_id=p.id ORDER BY s.id LIMIT 1) AS shot_up
        FROM favorites f JOIN projects p ON p.id=f.project_id AND p.status="published" WHERE f.user_id=? ORDER BY f.created_at DESC LIMIT 14');
      $lq->execute([(int)$user['id']]);
      $libRows = $lq->fetchAll();
      foreach ($libRows as $lr): ?>
    <a class="lib-item" href="<?= $prefix ?>project.php?id=<?= $lr['id'] ?>" title="<?= htmlspecialchars($lr['name']) ?>"><span class="lib-ico" style="<?= cover_css($lr) ?>"></span><span><?= htmlspecialchars($lr['name']) ?></span></a>
  <?php endforeach; if (!$libRows): ?><p class="lib-empty">Save projects to see them here.</p><?php endif;
    } else { ?><p class="lib-empty"><a href="<?= $prefix ?>login.php">Sign in</a> to build your library.</p><?php } ?>
  </div>
  <button id="collapse" class="ghost" aria-label="Collapse menu"><?= icon('sidebar') ?></button>
</nav>

<!-- ── MAIN SHELL ────────────────────────────── -->
<div class="shell">

<header class="top" role="banner">
  <button id="burger" class="ghost" aria-label="Open menu"><?= icon('menu') ?></button>
  <form action="<?= $prefix ?>explore.php" role="search">
    <input name="q" type="search" placeholder="Search open source projects…" aria-label="Search projects">
  </form>
  <label class="theme">Theme
    <select id="theme" aria-label="Color theme">
      <option value="neon-blue">Neon Blue</option>
      <option value="matrix-green">Matrix Green</option>
      <option value="synthwave">Synthwave</option>
      <option value="amber-terminal">Amber Terminal</option>
      <option value="blood-red">Blood Red</option>
    </select>
  </label>
  <?php if ($user): ?>
    <?php $n = unread_count((int)$user['id']); ?><a class="bell<?= $n ? ' has-new' : '' ?>" href="<?= $prefix ?>notifications.php" aria-label="Notifications"><?= icon('bell') ?><?php if ($n): ?><b><?= $n > 99 ? '99+' : $n ?></b><?php endif; ?></a>
    <div class="user-menu">
      <button class="user-btn" id="user-menu-btn" aria-haspopup="true" aria-expanded="false">
        <?php if($user['avatar']): ?>
          <img src="<?= htmlspecialchars($user['avatar']) ?>" alt="" class="user-avatar">
        <?php else: ?>
          <span class="user-initial"><?= mb_strtoupper(mb_substr($user['name'],0,1)) ?></span>
        <?php endif; ?>
        <span class="user-name"><?= htmlspecialchars($user['name']) ?></span> <?= icon('chevron-down') ?>
      </button>
      <div class="user-dropdown" id="user-dropdown" hidden>
        <a href="<?= $prefix ?>dashboard.php"><?= icon('chart') ?> Dashboard</a>
        <a href="<?= $prefix ?>profile.php?id=<?= $user['id'] ?>"><?= icon('user') ?> Profile</a>
        <a href="<?= $prefix ?>submit.php"><?= icon('plus') ?> Submit project</a>
        <a href="<?= $prefix ?>account.php"><?= icon('lock') ?> Change password</a>
        <?php if($user['role']==='admin'): ?>
          <a href="<?= $prefix ?>admin/index.php"><?= icon('settings') ?> Admin Panel</a>
        <?php endif; ?>
        <a href="<?= $prefix ?>logout.php" class="dropdown-danger"><?= icon('logout') ?> Sign out</a>
      </div>
    </div>
  <?php else: ?>
    <a class="btn" href="<?= $prefix ?>login.php">Sign in</a>
  <?php endif; ?>
</header>

<p class="notice" role="note">
  Neonbyte only lists open source projects and does not host files.
  Research every project before you download — anything you download is your own responsibility.
</p>

<div class="cols"><main id="main-content">
<?php }

function foot(): void {
    $script = $_SERVER['SCRIPT_FILENAME'];
    $base   = realpath(dirname(__DIR__));
    $rel    = str_replace($base, '', dirname($script));
    $depth2 = substr_count(ltrim($rel, '/'), '/') + (ltrim($rel,'/')==='' ? 0 : 1);
    $prefix = str_repeat('../', $depth2);
    if ($prefix === '') $prefix = './';
?>
<?php ad('ad-inline','90px'); ?>
</main>
<div class="rail"><?php ad('ad-side','600px'); ?></div>
</div>

<footer>
  &copy; <?= date('Y') ?> Neonbyte &middot;
  <a href="<?= $prefix ?>legal/terms.php">Terms</a>
  <a href="<?= $prefix ?>legal/privacy.php">Privacy</a>
  <a href="<?= $prefix ?>legal/cookies.php">Cookies</a>
  <a href="<?= $prefix ?>legal/dmca.php">Content removal</a>
  <a href="mailto:<?= defined('CONTACT_EMAIL') ? CONTACT_EMAIL : 'contact@example.com' ?>">Contact</a>
</footer>
<div id="cookie-banner" class="cookie" role="dialog" aria-label="Cookie consent" hidden>
  <p>We use essential cookies to keep you signed in and, if you agree, cookies for advertising. See our <a href="<?= $prefix ?>legal/cookies.php">Cookie Policy</a>.</p>
  <button class="btn" id="cookie-accept">Accept all</button> <button class="ghost" id="cookie-reject">Essential only</button>
</div>
<script>try{var c=localStorage.getItem('nb-consent'),b=document.getElementById('cookie-banner');if(!c)b.hidden=false;
b.addEventListener('click',function(e){var v=e.target.id==='cookie-accept'?'all':e.target.id==='cookie-reject'?'essential':null;if(v){localStorage.setItem('nb-consent',v);b.hidden=true;document.dispatchEvent(new CustomEvent('nb-consent',{detail:v}))}})}catch(e){}</script>

</div><!-- .shell -->
<script src="<?= $prefix ?>assets/js/app.js"></script>
</body>
</html>
<?php } ?>
