<?php
/**
 * Re-encrypt every stored e-mail with a NEW key (key rotation).
 *
 *   1. BACK UP YOUR DATABASE FIRST.
 *   2. OLD_KEY=<current base64 key> NEW_KEY=<new base64 key> php tools/rotate_key.php --dry-run
 *   3. Run again without --dry-run, then set NEONBYTE_KEY to the new key and discard the old one.
 *
 * Generate a key:  php -r "echo base64_encode(random_bytes(32)), PHP_EOL;"
 * Also rewrites the e-mail lookup hash and clears login_attempts (IP hashes depend on the key).
 */
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require __DIR__ . '/../config.php';
require __DIR__ . '/../includes/db.php';

$old = base64_decode((string)getenv('OLD_KEY'), true);
$new = base64_decode((string)getenv('NEW_KEY'), true);
if (!$old || !$new || strlen($old) < 32 || strlen($new) < 32 || $old === $new) {
    fwrite(STDERR, "Set OLD_KEY and NEW_KEY (different, base64, 32 bytes each).\n"); exit(1);
}
$dry = in_array('--dry-run', $argv, true);
$sub = fn(string $master, string $purpose) => hash_hkdf('sha256', $master, 32, $purpose, 'neonbyte-v1');
$dec = function (string $enc, string $key): string {
    $raw = base64_decode($enc, true);
    if ($raw === false || strlen($raw) < 29) throw new RuntimeException('bad ciphertext');
    $pt = openssl_decrypt(substr($raw, 28), 'aes-256-gcm', $key, OPENSSL_RAW_DATA, substr($raw, 0, 12), substr($raw, 12, 16));
    if ($pt === false) throw new RuntimeException('decrypt failed (wrong OLD_KEY?)');
    return $pt;
};
$encr = function (string $pt, string $key): string {
    $nonce = random_bytes(12); $tag = '';
    $ct = openssl_encrypt($pt, 'aes-256-gcm', $key, OPENSSL_RAW_DATA, $nonce, $tag, '', 16);
    return base64_encode($nonce . $tag . $ct);
};

$db = db(); $db->beginTransaction(); $n = 0;
try {
    foreach ($db->query("SELECT id, email_encrypted FROM users WHERE email_encrypted <> ''")->fetchAll() as $u) {
        $email = $dec($u['email_encrypted'], $sub($old, 'email-encryption'));
        $db->prepare('UPDATE users SET email_encrypted=?, email_hash=? WHERE id=?')->execute([
            $encr($email, $sub($new, 'email-encryption')),
            hash_hmac('sha256', strtolower(trim($email)), $sub($new, 'email-lookup-hash')),
            $u['id'],
        ]);
        $n++;
    }
    $db->exec('DELETE FROM login_attempts');
    $dry ? $db->rollBack() : $db->commit();
} catch (Throwable $e) { $db->rollBack(); fwrite(STDERR, 'Aborted, nothing changed: ' . $e->getMessage() . "\n"); exit(1); }
echo ($dry ? "[dry run] would re-encrypt" : "Re-encrypted") . " $n users. " . ($dry ? '' : "Now set NEONBYTE_KEY to the new key.") . "\n";
