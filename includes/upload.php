<?php
// ── Screenshot upload handler ──────────────────
require_once __DIR__ . '/../config.php';

/**
 * Handle one uploaded screenshot file.
 * Returns ['ok'=>true,'path'=>'filename.webp'] or ['ok'=>false,'error'=>'...']
 */
function handle_screenshot_upload(array $file): array {
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return ['ok'=>false,'error'=>'Upload error code '.$file['error']];
    }
    if ($file['size'] > MAX_UPLOAD_BYTES) {
        return ['ok'=>false,'error'=>'File too large (max 4 MB)'];
    }
    // Check real MIME via finfo
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime  = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    if (!in_array($mime, ALLOWED_MIME, true)) {
        return ['ok'=>false,'error'=>'Invalid file type ('.$mime.')'];
    }
    $ext = match($mime) {
        'image/jpeg' => 'jpg',
        'image/png'  => 'png',
        'image/webp' => 'webp',
        'image/gif'  => 'gif',
        default      => 'bin',
    };
    if (!is_dir(UPLOAD_DIR)) { @mkdir(UPLOAD_DIR, 0755, true); }
    if (!file_exists(UPLOAD_DIR.'.htaccess')) {
        @file_put_contents(UPLOAD_DIR.'.htaccess', "php_flag engine off\nRemoveHandler .php .phtml .php5\nOptions -Indexes\n");
        @file_put_contents(UPLOAD_DIR.'index.html', '');
    }
    $name = uniqid('ss_', true).'.'.$ext;
    $dest = UPLOAD_DIR.$name;
    if (!move_uploaded_file($file['tmp_name'], $dest)) {
        return ['ok'=>false,'error'=>'Could not save file'];
    }
    return ['ok'=>true,'path'=>$name,'is_upload'=>1];
}

/**
 * Validate an external URL as screenshot.
 * Returns ['ok'=>true,'path'=>$url,'is_upload'=>0] or error.
 */
function handle_screenshot_url(string $url): array {
    $url = trim($url);
    if (!filter_var($url, FILTER_VALIDATE_URL)) {
        return ['ok'=>false,'error'=>'Invalid URL'];
    }
    if (!preg_match('/^https?:\/\//i', $url)) {
        return ['ok'=>false,'error'=>'Only http/https URLs allowed'];
    }
    return ['ok'=>true,'path'=>$url,'is_upload'=>0];
}
