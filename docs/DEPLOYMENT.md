# Deploying Neonbyte (shared hosting or VPS)

These steps also work on free or shared PHP hosting where you only get FTP and phpMyAdmin.

## 1. Prepare the server

- PHP 8.1 or newer with `pdo_mysql`, `mbstring`, `openssl` and `fileinfo`.
- A MySQL or MariaDB database and user.
- Apache with `.htaccess` enabled (`mod_headers` and `mod_rewrite` recommended).
- HTTPS enabled (most hosts offer a free certificate).

## 2. Upload and create the database

1. Upload every file of the repository (except `.git`).
2. In phpMyAdmin select your database and **import `database/schema.sql`**. If your host does not let you create databases, remove the first two lines (`CREATE DATABASE` and `USE`).
3. Optional: import `database/demo_seed.sql` for a demo catalog.

## 3. Configure secrets

Copy `config.local.example.php` to `config.local.php` and fill it in:

```php
return [
    'APP_ENV'      => 'production',
    'SITE_URL'     => 'https://your-domain.example',
    'DB_HOST'      => 'localhost',
    'DB_NAME'      => 'your_db',
    'DB_USER'      => 'your_user',
    'DB_PASS'      => 'your_password',
    'NEONBYTE_KEY' => '<output of: php -r "echo base64_encode(random_bytes(32)), PHP_EOL;">',
];
```

**Back up `NEONBYTE_KEY` somewhere safe and separate from the database.** If you lose it, stored e-mails can no longer be decrypted and users cannot log in with their e-mail. `config.local.php` is git-ignored and blocked from web access by `.htaccess`, but never commit or share it.

## 4. Create the first admin

With SSH or a local PHP install pointing at the same database:

```bash
php tools/create_admin.php "Your Name" you@example.com
```

Without shell access: register normally on the site, then in phpMyAdmin run
`UPDATE users SET role='admin' WHERE id = <your id>;`.

## 5. Final checks

- Open the site over `https://` and confirm you stay logged in after a refresh.
- Try an upload on a test project and confirm the image loads.
- In `.htaccess`, enable the HTTPS redirect and, once everything works, the HSTS header.
- Replace the placeholder e-mail addresses (`CONTACT_EMAIL`, `LEGAL_EMAIL`, `PRIVACY_EMAIL`, `DMCA_EMAIL`) and read the Terms and Privacy pages so they match how you run the site.

## Rotating the key

```bash
OLD_KEY=<current> NEW_KEY=<new> php tools/rotate_key.php --dry-run   # check first
OLD_KEY=<current> NEW_KEY=<new> php tools/rotate_key.php             # apply, then set NEONBYTE_KEY=<new>
```

Take a database backup before running it. A running site should be in maintenance mode during rotation.

## Updating

Upload the new files over the old ones, keeping `config.local.php` and the `uploads/` folder. Check the release notes for database changes.
