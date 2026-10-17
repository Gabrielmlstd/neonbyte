# Contributing to Neonbyte

Thanks for helping! Bug reports, fixes, translations and new ideas are all welcome.

## Before you start

- Check existing issues so work is not duplicated. For big changes, open an issue first.
- **Never paste real user data, database dumps, keys or `config.local.php` into issues or pull requests.**
- Be kind. See the [Code of Conduct](CODE_OF_CONDUCT.md).

## Set up

Follow *Quick start* in the [README](README.md). `database/demo_seed.sql` gives you sample projects, and `tools/create_admin.php` creates a local admin.

## Code guidelines

- PHP 8.1+, no framework. Keep functions small and files readable.
- Always use **PDO prepared statements**. Escape every value you print with `htmlspecialchars()`.
- Every state-changing form or endpoint needs a **CSRF check** (see `includes/auth.php`).
- Do not store plain-text e-mails or IPs. Use `includes/crypto.php`.
- User-facing text is in **English**.
- Icons come from `includes/icons.php` (inline SVG). No emojis in the UI.
- Styling lives in `assets/css/style.css` and `assets/css/hydra.css`. Use the CSS variables so all five themes keep working, and honor `prefers-reduced-motion`.

## Pull requests

1. Fork and create a branch: `fix/login-error` or `feat/collections`.
2. Run `find . -name '*.php' -print0 | xargs -0 -n1 php -l`. CI runs the same check.
3. Describe what changed and how you tested it. Screenshots help for UI changes.
4. Keep PRs focused: one change per PR.

## Content policy

What may be listed on Neonbyte is defined in [docs/CONTENT_POLICY.md](docs/CONTENT_POLICY.md). Changes to it need discussion in an issue.

By contributing you agree your work is released under the [MIT license](LICENSE).
