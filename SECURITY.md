# Security policy

## Reporting a vulnerability

Please **do not open a public issue** for security problems.
Use GitHub's private reporting: **Security tab → Report a vulnerability**. Include the affected file or page, steps to reproduce and the impact. You will get an answer as soon as possible, and credit if you want it once the fix is released.

Never include real user data in a report. Describe the problem with test accounts.

## What is protected

| Data | How it is stored |
|---|---|
| E-mail address | AES-256-GCM encrypted; lookups use an HMAC-SHA256 blind index |
| Password | `password_hash()` (bcrypt) |
| IP address | not stored; HMAC hash kept briefly for login rate limiting |
| Sessions | HttpOnly, SameSite=Strict, `Secure` when the site runs on HTTPS |

Other measures: prepared statements, output escaping, CSRF tokens, upload validation and a script-free upload folder, security headers and a CSP in `.htaccess`.

## Checklist for anyone running Neonbyte

- Use **HTTPS** and enable the HSTS line in `.htaccess` once it works.
- Set `APP_ENV=production`. Never leave PHP errors visible.
- Create a real key (`php -r "echo base64_encode(random_bytes(32)), PHP_EOL;"`) and store it **outside the repository**. Keep a backup of it separate from your database backups: without it the encrypted e-mails cannot be read.
- Keep `config.local.php` out of git (already in `.gitignore`) and out of the public web root if your host allows it.
- Do not import `database/demo_seed.sql` on a real site unless you want the demo catalog.
- Create the admin with `tools/create_admin.php` using a long passphrase. There is no default account.
- Give the database user only the privileges it needs, and keep PHP and MariaDB/MySQL updated.
- If the key or database leaks: restore from a clean backup if needed, run `tools/rotate_key.php`, and ask users to change their passwords.

## Out of scope

Reports about missing e-mail verification, password reset or 2FA are known gaps (see the README), not vulnerabilities. Social engineering, spam and denial-of-service by volume are out of scope.
