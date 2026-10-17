<?php
require_once 'includes/layout.php';
require_once 'includes/auth.php';
require_once 'includes/db.php';
require_once 'includes/crypto.php';

if (logged_in()) redirect('index.php');

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $email = trim($_POST['email'] ?? '');
    $pass  = $_POST['password'] ?? '';

    $db  = db();
    $ip  = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';

    // Rate limit check (IP hashed — não armazena IP real)
    if (rate_limited($db, $ip, max: 5, window_minutes: 10)) {
        $error = 'Too many attempts. Try again in 10 minutes.';
    } elseif ($email && $pass) {
        // Look up by email HMAC hash (not plain email)
        $email_hash = nb_email_hash($email);
        $st = $db->prepare('SELECT * FROM users WHERE email_hash = ? AND banned = 0 LIMIT 1');
        $st->execute([$email_hash]);
        $user = $st->fetch();

        if ($user && password_verify($pass, $user['password_hash'])) {
            // Upgrade bcrypt cost if needed
            if (password_needs_rehash($user['password_hash'], PASSWORD_BCRYPT, ['cost' => 12])) {
                $new_hash = password_hash($pass, PASSWORD_BCRYPT, ['cost' => 12]);
                $db->prepare('UPDATE users SET password_hash=? WHERE id=?')->execute([$new_hash, $user['id']]);
            }
            rate_limit_clear($db, $ip);  // reset attempts on success
            login_user($user);
            $dest = safe_redirect_url($_SESSION['after_login'] ?? '', 'index.php');
            unset($_SESSION['after_login']);
            header('Location: '.SITE_URL.'/'.$dest);
            exit;
        }
        // Generic error — don't reveal whether email exists
        $error = 'Invalid e-mail or password.';
    } else {
        $error = 'Please fill in all fields.';
    }
}

head('Sign in');
?>
<div class="auth-wrap fade-up">
  <div class="auth-box">
    <div class="auth-logo"><?= logo_mark(30) ?> Neonbyte</div>
    <h1>Sign in</h1>
    <p class="auth-sub">Welcome back. Access your projects and library.</p>

    <?php if ($error): ?>
      <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    <?php $ok = get_flash('success'); if ($ok): ?>
      <div class="alert alert-ok"><?= htmlspecialchars($ok) ?></div>
    <?php endif; ?>

    <form method="post" action="login.php" autocomplete="on">
      <?= csrf_field() ?>
      <label class="field-label" for="email">E-mail</label>
      <input id="email" name="email" type="email" placeholder="you@example.com"
             value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
             required autocomplete="email" class="field-input">

      <label class="field-label" for="password">Password</label>
      <input id="password" name="password" type="password" placeholder="••••••••"
             required autocomplete="current-password" class="field-input">

      <button type="submit" class="btn btn-full">Sign in</button>
    </form>

    <p class="auth-footer">Don't have an account? <a href="register.php">Create one</a></p>
  </div>
</div>
<?php foot(); ?>
