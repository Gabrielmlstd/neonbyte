<?php
// ── Data layer — MySQL version (Block 2) ─────
// All project data now comes from the database.
// This file keeps helper functions used by multiple pages.
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/icons.php';

/**
 * Fetch all published projects with platforms concatenated.
 */
function projects(): array {
    $st = db()->query('
        SELECT p.*, (SELECT s.path_or_url FROM screenshots s WHERE s.project_id=p.id ORDER BY s.id LIMIT 1) AS shot, (SELECT s.is_upload FROM screenshots s WHERE s.project_id=p.id ORDER BY s.id LIMIT 1) AS shot_up, c.name AS cat, c.slug AS cat_slug,
               GROUP_CONCAT(pp.platform ORDER BY pp.platform SEPARATOR \', \') AS plat_str
        FROM projects p
        JOIN categories c ON c.id = p.category_id
        LEFT JOIN project_platforms pp ON pp.project_id = p.id
        WHERE p.status = "published"
        GROUP BY p.id
        ORDER BY p.likes_count DESC
    ');
    $rows = $st->fetchAll();
    // Normalise to the same shape the templates expect
    return array_map(function(array $r): array {
        return [
            'id'    => (int)$r['id'],
            'name'  => $r['name'],
            'cat'   => $r['cat'],
            'lic'   => $r['license'],
            'plat'  => $r['plat_str'] ? explode(', ', $r['plat_str']) : [],
            'likes' => (int)$r['likes_count'],
            'hue'   => (int)$r['hue'],
            'desc'  => $r['description'],
            'help'  => (bool)$r['needs_help'],
            'new'   => (strtotime($r['created_at']) > strtotime('-30 days')),
            // Extra fields used in project.php
            'views'        => (int)$r['views'],
            'clicks'       => (int)$r['clicks'],
            'repo_url'     => $r['repo_url'],
            'download_url' => $r['download_url'],
            'video_url'    => $r['video_url'] ?? null,
            'featured'     => (bool)$r['featured'],
            'user_id'      => (int)$r['user_id'],
            'cover'        => $r['cover'] ?? null,
            'shot'         => $r['shot'] ?? null,
            'shot_up'      => (int)($r['shot_up'] ?? 0),
        ];
    }, $rows);
}

/**
 * Cover gradient CSS (shared by hero slides and cards).
 */
function shot_src(string $path, $isUpload): string {
    $u = $isUpload ? UPLOAD_URL . $path : $path;
    return strtr($u, ["'"=>'%27', '"'=>'%22', '('=>'%28', ')'=>'%29', ' '=>'%20', chr(92)=>'%5C']);
}
function cover_css(array $p): string {
    $h = (int)$p['hue']; $h2 = ($h + 60) % 360;
    $g = "linear-gradient(135deg,hsl($h,90%,22%),hsl($h2,80%,10%))";
    $u = '';
    if (!empty($p['shot'])) $u = shot_src($p['shot'], $p['shot_up'] ?? 0);
    elseif (!empty($p['cover'])) $u = shot_src((defined('SITE_BASE') ? SITE_BASE : SITE_URL) . '/' . $p['cover'], 0);
    if ($u !== '') {
        return "background:linear-gradient(180deg,rgba(0,0,0,0) 50%,rgba(0,0,0,.6)),url('$u') center/cover no-repeat,$g";
    }
    return "background:$g";
}
function cover(array $p): string { return cover_css($p); }

/**
 * Render one card <a> element.
 */
function card(array $p): string {
    $pl   = implode(' / ', array_map('htmlspecialchars', $p['plat']));
    $desc = htmlspecialchars($p['desc']);
    return '<a class="card" href="project.php?id='.$p['id'].'">'
         . '<div class="cover" style="'.cover($p).'"><span class="lic">'.htmlspecialchars($p['lic']).'</span></div>'
         . '<div class="meta">'
             . '<h3>'.htmlspecialchars($p['name']).'</h3>'
             . '<p class="mono">'.htmlspecialchars($p['cat']).' | '.$pl.'</p>'
             . '<p class="likes">'.icon('heart').' '.number_format($p['likes']).'</p>'
         . '</div>'
         . '<div class="card-preview"><p>'.$desc.'</p></div>'
         . '</a>';
}
