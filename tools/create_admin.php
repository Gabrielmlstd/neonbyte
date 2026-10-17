<?php
/**
 * Create (or promote) an administrator from the command line.
 *
 *   php tools/create_admin.php "Your Name" you@example.com
 *   ADMIN_PASSWORD='long passphrase' php tools/create_admin.php "Your Name" you@example.com
 *
 * No default admin account ships with Neonbyte, so there are no known credentials.
 */
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require __DIR__ . '/../includes/db.php';
require __DIR__ . '/../includes/crypto.php';

[$self, $name, $email] = array_pad($argv, 3, '');
if ($name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    fwrite(STDERR, "Usage: php tools/create_admin.php \"Name\" email@example.com\n"); exit(1);
}
$pass = getenv('ADMIN_PASSWORD') ?: '';
if ($pass === '') {
    fwrite(STDOUT, 'Password (min 12 chars): ');
    if (DIRECTORY_SEPARATOR === '/') @system('stty -echo');
    $pass = rtrim((string)fgets(STDIN), "\r\n");
    if (DIRECTORY_SEPARATOR === '/') { @system('stty echo'); echo "\n"; }
}
if (strlen($pass) < 12) { fwrite(STDERR, "Password must have at least 12 characters.\n"); exit(1); }

$db   = db();
$hash = nb_email_hash($email);
$pw   = password_hash($pass, PASSWORD_DEFAULT);
$st   = $db->prepare('SELECT id FROM users WHERE email_hash = ?');
$st->execute([$hash]);
if ($id = $st->fetchColumn()) {
    $db->prepare("UPDATE users SET role='admin', banned=0, password_hash=? WHERE id=?")->execute([$pw, $id]);
    echo "Existing user promoted to admin and password updated.\n";
} else {
    $db->prepare("INSERT INTO users (name,email_encrypted,email_hash,password_hash,role) VALUES (?,?,?,?, 'admin')")
       ->execute([$name, nb_encrypt($email), $hash, $pw]);
    echo "Admin created.\n";
}
