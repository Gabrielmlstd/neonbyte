<?php
// Copy to config.local.php (git-ignored) and fill in your real values.
// On shared hosting without environment variables, this is the place for secrets.
return [
    'APP_ENV'       => 'production',                 // 'development' shows PHP errors
    'SITE_URL'      => 'https://your-domain.example',
    'DB_HOST'       => 'localhost',
    'DB_NAME'       => 'neonbyte',
    'DB_USER'       => 'neonbyte_user',
    'DB_PASS'       => 'change-me',
    'NEONBYTE_KEY'  => '',                           // php -r "echo base64_encode(random_bytes(32)), PHP_EOL;"
    'CONTACT_EMAIL' => 'contact@your-domain.example',
    'LEGAL_EMAIL'   => 'legal@your-domain.example',
    'PRIVACY_EMAIL' => 'privacy@your-domain.example',
    'DMCA_EMAIL'    => 'dmca@your-domain.example',
];
