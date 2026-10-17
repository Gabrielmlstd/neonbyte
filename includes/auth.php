<?php
// ── Session bootstrap + helpers ─────────────────────────
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/crypto.php';

// Start session once
if (session_status() === PHP_SESSION_NONE) {
    session_name(SESSION_NAME);
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'secure'   => COOKIE_SECURE,
        'httponly' => true,
        'samesite' => COOKIE_SAMESITE,
    ]);
    session_start();
}

/* ── CSRF ────────────────────────────────────────────── */
function csrf_token(): string {
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = nb_random_token(24);
    }
    return $_SESSION['csrf'];
}
function csrf_field(): string {
    return '<input type="hidden" name="csrf" value="'.htmlspecialchars(csrf_token()).'">';
}
function csrf_verify(): void {
    $tok = $_POST['csrf'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
    if (!hash_equals(csrf_token(), (string)$tok)) {
        http_response_code(403);
        exit('Invalid CSRF token.');
    }
    // Rotate token after each verified POST (double-submit protection)
    unset($_SESSION['csrf']);
}

/* ── User helpers ────────────────────────────────────── */
function current_user(): ?array {
    return $_SESSION['user'] ?? null;
}
function logged_in(): bool {
    return isset($_SESSION['user']);
}
function is_admin(): bool {
    return ($_SESSION['user']['role'] ?? '') === 'admin';
}

/**
 * Validate a redirect destination is safe (same-site only).
 * Prevents Open Redirect attacks.
 */
function safe_redirect_url(string $url, string $fallback = 'index.php'): string {
    // Strip whitespace and control characters
    $url = trim($url);
    // Only allow relative paths (no scheme, no //)
    if (preg_match('/^(https?:\/\/|\/\/|javascript:|data:)/i', $url)) {
        return $fallback;
    }
    // Must not start with // (protocol-relative = external)
    if (str_starts_with($url, '//')) {
        return $fallback;
    }
    // Allow only printable ASCII, block null bytes
    if (!preg_match('/^[\x20-\x7E]+$/', $url)) {
        return $fallback;
    }
    return $url;
}

function require_login(string $fallback = 'index.php'): void {
    if (!logged_in()) {
        // Store current URL for post-login redirect (validated on use)
        $current = $_SERVER['REQUEST_URI'] ?? '';
        $_SESSION['after_login'] = safe_redirect_url($current, $fallback);
        header('Location: '.SITE_URL.'/login.php');
        exit;
    }
}
function require_admin(): void {
    require_login();
    if (!is_admin()) {
        http_response_code(403);
        exit('Access denied.');
    }
}
function login_user(array $user): void {
    session_regenerate_id(true);
    // Store minimal data in session — never store password_hash
    $_SESSION['user'] = [
        'id'               => (int)$user['id'],
        'name'             => $user['name'],
        'email_encrypted'  => $user['email_encrypted'] ?? '',
        'role'             => $user['role'],
        'avatar'           => $user['avatar'] ?? null,
        'screenshot_limit' => (int)($user['screenshot_limit'] ?? 10),
        'banned'           => (int)($user['banned'] ?? 0),
    ];
}
function logout_user(): void {
    session_unset();
    session_destroy();
    // Destroy session cookie
    if (isset($_COOKIE[SESSION_NAME])) {
        setcookie(SESSION_NAME, '', [
            'expires'  => time() - 3600,
            'path'     => '/',
            'secure'   => COOKIE_SECURE,
            'httponly' => true,
            'samesite' => COOKIE_SAMESITE,
        ]);
    }
}

/* ── Flash messages ──────────────────────────────────── */
function flash(string $key, string $msg): void {
    $_SESSION['flash'][$key] = $msg;
}
function get_flash(string $key): string {
    $msg = $_SESSION['flash'][$key] ?? '';
    unset($_SESSION['flash'][$key]);
    return $msg;
}

/* ── Redirect helper ─────────────────────────────────── */
function redirect(string $path): never {
    $safe = safe_redirect_url($path, 'index.php');
    // If it's already an absolute URL for our site, use as-is
    if (str_starts_with($safe, 'http')) {
        header('Location: '.$safe);
    } else {
        header('Location: '.SITE_URL.'/'.$safe);
    }
    exit;
}

/* ── JSON response helper (for API endpoints) ─────────── */
function json_response(mixed $data, int $code = 200): never {
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    // Prevent JSON-based XSS
    echo json_encode($data, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
    exit;
}

/* ── Rate limiter helper (uses hashed IPs) ───────────── */
/**
 * Check if IP is rate-limited. Returns true = blocked, false = allowed.
 * Automatically increments attempt count.
 */
function rate_limited(\PDO $db, string $ip, int $max = 5, int $window_minutes = 10): bool {
    $ip_hash = nb_ip_hash($ip);
    $table   = 'login_attempts';

    // Clean up old attempts
    $db->prepare("DELETE FROM $table WHERE created_at < (NOW() - INTERVAL {$window_minutes} MINUTE)")->execute();

    $q = $db->prepare("SELECT COUNT(*) FROM $table WHERE ip_hash = ? AND created_at > (NOW() - INTERVAL {$window_minutes} MINUTE)");
    $q->execute([$ip_hash]);
    $count = (int)$q->fetchColumn();

    if ($count >= $max) return true; // blocked

    $db->prepare("INSERT INTO $table (ip_hash) VALUES (?)")->execute([$ip_hash]);
    return false;
}

/**
 * Clear rate limit for IP (call on successful login).
 */
function rate_limit_clear(\PDO $db, string $ip): void {
    $ip_hash = nb_ip_hash($ip);
    $db->prepare('DELETE FROM login_attempts WHERE ip_hash = ?')->execute([$ip_hash]);
}
