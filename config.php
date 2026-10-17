<?php
/**
 * Neonbyte configuration — safe to commit, contains NO secrets.
 *
 * Real values are read from, in this order:
 *   1. config.local.php   (git-ignored; copy config.local.example.php)
 *   2. environment variables
 */
function nb_cfg(string $key, ?string $default = null): ?string {
    static $local = null;
    if ($local === null) {
        $f = __DIR__ . '/config.local.php';
        $local = is_file($f) ? (array)require $f : [];
    }
    if (isset($local[$key]) && $local[$key] !== '') return (string)$local[$key];
    $env = getenv($key);
    return ($env !== false && $env !== '') ? $env : $default;
}

// ── Environment ───────────────────────────────────────────
define('ENV', nb_cfg('APP_ENV', 'development'));
define('IS_PROD', ENV === 'production');
ini_set('display_errors', IS_PROD ? '0' : '1');
ini_set('log_errors', '1');
error_reporting(E_ALL);

// ── Database ──────────────────────────────────────────────
define('DB_HOST',    nb_cfg('DB_HOST', 'localhost'));
define('DB_NAME',    nb_cfg('DB_NAME', 'neonbyte'));
define('DB_USER',    nb_cfg('DB_USER', 'root'));
define('DB_PASS',    nb_cfg('DB_PASS', ''));
define('DB_CHARSET', 'utf8mb4');

// ── Site ──────────────────────────────────────────────────
$__site = nb_cfg('SITE_URL');
if (!$__site) {   // auto-detect (handy on localhost / XAMPP)
    $root = rtrim(str_replace(chr(92), '/', $_SERVER['DOCUMENT_ROOT'] ?? ''), '/');
    $dir  = str_replace(chr(92), '/', __DIR__);
    $base = ($root && stripos($dir, $root) === 0) ? substr($dir, strlen($root)) : '/neonbyte';
    $https = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
    $__site = isset($_SERVER['HTTP_HOST']) ? ($https ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . $base : 'http://localhost/neonbyte';
}
define('SITE_URL',  rtrim($__site, '/'));
define('SITE_NAME', 'Neonbyte');
define('SITE_BASE', SITE_URL);   // base for relative asset paths (cover images)

// ── Encryption key (AES-256-GCM, used for e-mails and IP hashes) ──
// Generate one:  php -r "echo base64_encode(random_bytes(32)), PHP_EOL;"
// Keep it OUT of git. Losing it means encrypted e-mails cannot be read again.
$__key = nb_cfg('NEONBYTE_KEY');
if (!$__key) {
    if (IS_PROD) { http_response_code(500); exit('Server misconfigured: NEONBYTE_KEY is not set.'); }
    // Development only: create a random per-install key in a git-ignored file.
    $kf = __DIR__ . '/.dev-key';
    if (!is_file($kf)) { file_put_contents($kf, base64_encode(random_bytes(32))); @chmod($kf, 0600); }
    $__key = trim((string)file_get_contents($kf));
}
define('ENCRYPTION_KEY', base64_decode($__key));

// ── Uploads ───────────────────────────────────────────────
define('MAX_UPLOAD_BYTES', 4 * 1024 * 1024);   // 4 MB
define('UPLOAD_DIR', __DIR__ . '/uploads/screenshots/');
define('UPLOAD_URL', SITE_URL . '/uploads/screenshots/');
define('ALLOWED_MIME', ['image/jpeg', 'image/png', 'image/webp', 'image/gif']);

// ── Sessions ──────────────────────────────────────────────
define('SESSION_NAME', 'nb_sess');
define('COOKIE_SECURE', str_starts_with(SITE_URL, 'https'));
define('COOKIE_SAMESITE', 'Strict');

// ── Contact addresses shown on the legal pages ────────────
define('CONTACT_EMAIL', nb_cfg('CONTACT_EMAIL', 'contact@example.com'));
define('LEGAL_EMAIL',   nb_cfg('LEGAL_EMAIL',   'legal@example.com'));
define('PRIVACY_EMAIL', nb_cfg('PRIVACY_EMAIL', 'privacy@example.com'));
define('DMCA_EMAIL',    nb_cfg('DMCA_EMAIL',    'dmca@example.com'));
