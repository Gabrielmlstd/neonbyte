<?php
require_once 'includes/layout.php';
require_once 'includes/auth.php';
require_once 'includes/db.php';
require_once 'includes/crypto.php';

if (logged_in()) redirect('index.php');

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $name  = trim($_POST['name']  ?? '');
    $email = trim($_POST['email'] ?? '');
    $pass  = $_POST['password']   ?? '';
    $pass2 = $_POST['password2']  ?? '';

    if (!$name || !$email || !$pass || !$pass2) {
        $error = 'Please fill in all fields.';
    } elseif (strlen($name) > 80) {
        $error = 'Name is too long (max 80 characters).';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Invalid e-mail address.';
    } elseif (strlen($email) > 190) {
        $error = 'E-mail address is too long.';
    } elseif (strlen($pass) < 8) {
        $error = 'Password must be at least 8 characters.';
    } elseif (strlen($pass) > 72) {
        // bcrypt silently truncates at 72 bytes — warn the user
        $error = 'Password is too long (max 72 characters).';
    } elseif ($pass !== $pass2) {
        $error = 'Passwords do not match.';
    } else {
        $db = db();

        // Check duplicate by email HMAC hash (never store plain email for lookup)
        $email_hash = nb_email_hash($email);
        $st = $db->prepare('SELECT id FROM users WHERE email_hash = ?');
        $st->execute([$email_hash]);
        if ($st->fetch()) {
            $error = 'This e-mail is already registered.';
        } else {
            // Encrypt email with AES-256-GCM (reversible for display/contact)
            $email_encrypted = nb_encrypt($email);

            $hash = password_hash($pass, PASSWORD_BCRYPT, ['cost' => 12]);
            $st   = $db->prepare(
                'INSERT INTO users (name, email_encrypted, email_hash, password_hash) VALUES (?,?,?,?)'
            );
            $st->execute([$name, $email_encrypted, $email_hash, $hash]);
            flash('success', 'Account created! You can now sign in.');
            redirect('login.php');
        }
    }
}

head('Create account');
?>
<div class="auth-wrap fade-up">
  <div class="auth-box">
    <div class="auth-logo"><?= logo_mark(30) ?> Neonbyte</div>
    <h1>Create account</h1>
    <p class="auth-sub">Join Neonbyte and share open source projects.</p>

    <?php if ($error): ?>
      <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="post" action="register.php" autocomplete="on">
      <?= csrf_field() ?>
      <label class="field-label" for="reg-name">Display name</label>
      <input id="reg-name" name="name" type="text" placeholder="Your name"
             value="<?= htmlspecialchars($_POST['name'] ?? '') ?>"
             required autocomplete="name" class="field-input" maxlength="80">

      <label class="field-label" for="reg-email">E-mail</label>
      <input id="reg-email" name="email" type="email" placeholder="you@example.com"
             value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
             required autocomplete="email" class="field-input">

      <label class="field-label" for="reg-pass">Password</label>
      <input id="reg-pass" name="password" type="password" placeholder="Min. 8 characters"
             required autocomplete="new-password" class="field-input" minlength="8" maxlength="72">

      <label class="field-label" for="reg-pass2">Confirm password</label>
      <input id="reg-pass2" name="password2" type="password" placeholder="Repeat password"
             required autocomplete="new-password" class="field-input" minlength="8" maxlength="72">

      <button type="submit" class="btn btn-full">Create account</button>
    </form>

    <p class="auth-footer">Already have an account? <a href="login.php">Sign in</a></p>
  </div>
</div>
<?php foot(); ?>
