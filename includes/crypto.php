<?php
// ══════════════════════════════════════════════════════════
//  NEONBYTE — AES-256-GCM encryption helpers
//  Used to encrypt personally-identifiable data (e-mails)
//  so that even a full DB dump reveals nothing readable.
// ══════════════════════════════════════════════════════════

/**
 * Derive a 256-bit key from the master key + purpose string.
 * Using HKDF (RFC 5869) so we can safely derive multiple
 * sub-keys from one master secret.
 */
function _nb_derive_key(string $purpose): string {
    $master = defined('ENCRYPTION_KEY') ? ENCRYPTION_KEY : '';
    if (strlen($master) < 32) {
        throw new RuntimeException('ENCRYPTION_KEY must be at least 32 bytes. Set it in config.php / environment variable.');
    }
    return hash_hkdf('sha256', $master, 32, $purpose, 'neonbyte-v1');
}

/**
 * Encrypt a string with AES-256-GCM.
 * Returns a URL-safe base64 string: nonce(12) + tag(16) + ciphertext.
 * Returns empty string for empty input (NULL-safe).
 */
function nb_encrypt(string $plaintext): string {
    if ($plaintext === '') return '';
    $key   = _nb_derive_key('email-encryption');
    $nonce = random_bytes(12);            // 96-bit nonce, unique per encryption
    $tag   = '';
    $ct    = openssl_encrypt($plaintext, 'aes-256-gcm', $key, OPENSSL_RAW_DATA, $nonce, $tag, '', 16);
    if ($ct === false) throw new RuntimeException('Encryption failed.');
    return base64_encode($nonce . $tag . $ct);
}

/**
 * Decrypt a value produced by nb_encrypt().
 * Throws RuntimeException if the ciphertext is tampered with (AEAD).
 */
function nb_decrypt(string $encoded): string {
    if ($encoded === '') return '';
    $raw   = base64_decode($encoded, true);
    if ($raw === false || strlen($raw) < 28) throw new RuntimeException('Invalid ciphertext.');
    $nonce = substr($raw, 0,  12);
    $tag   = substr($raw, 12, 16);
    $ct    = substr($raw, 28);
    $key   = _nb_derive_key('email-encryption');
    $pt    = openssl_decrypt($ct, 'aes-256-gcm', $key, OPENSSL_RAW_DATA, $nonce, $tag);
    if ($pt === false) throw new RuntimeException('Decryption failed — data may be tampered.');
    return $pt;
}

/**
 * One-way deterministic fingerprint of an e-mail for DB lookups.
 * Uses HMAC-SHA256 so it cannot be reversed without the key.
 * Returns 64 hex chars.
 */
function nb_email_hash(string $email): string {
    $key = _nb_derive_key('email-lookup-hash');
    return hash_hmac('sha256', strtolower(trim($email)), $key);
}

/**
 * One-way fingerprint of an IP address (for login_attempts).
 * 64 hex chars, cannot be reversed.
 */
function nb_ip_hash(string $ip): string {
    $key = _nb_derive_key('ip-hash');
    return hash_hmac('sha256', $ip, $key);
}

/**
 * Generate a cryptographically secure random token (URL-safe base64).
 * Default: 32 bytes = 256-bit entropy.
 */
function nb_random_token(int $bytes = 32): string {
    return rtrim(strtr(base64_encode(random_bytes($bytes)), '+/', '-_'), '=');
}

/**
 * Mask an encrypted e-mail for admin screens (m***@e***.com) so staff
 * never see full addresses unless they really need to.
 */
function nb_mask_email(string $encrypted): string {
    try { $e = nb_decrypt($encrypted); } catch (Throwable $t) { return '(unreadable)'; }
    if (!str_contains($e, '@')) return '(unknown)';
    [$l, $d] = explode('@', $e, 2);
    return substr($l, 0, 1) . '***@' . substr($d, 0, 1) . '***' . (strrchr($d, '.') ?: '');
}
