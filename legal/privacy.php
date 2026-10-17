<?php require_once '../includes/layout.php'; head('Privacy Policy'); ?>
<div class="legal-page fade-up">
  <h1>Privacy Policy</h1>
  <p class="page-sub">Last updated: <?= date('d F Y') ?></p>

  <h2>1. Data We Collect</h2>
  <ul>
    <li><strong>Account data:</strong> display name, e-mail address (stored encrypted with AES-256-GCM), hashed password (bcrypt).</li>
    <li><strong>Usage data:</strong> project views, download link clicks (anonymised aggregate counts — no individual tracking).</li>
    <li><strong>Uploaded files:</strong> screenshots you submit are stored on our server.</li>
    <li><strong>Preferences:</strong> chosen colour theme (stored in your browser's localStorage — never sent to our server).</li>
    <li><strong>Security logs:</strong> failed login attempt records use a one-way cryptographic hash of your IP address — your real IP is never stored.</li>
  </ul>

  <h2>2. How We Protect Your E-mail</h2>
  <p>Your e-mail address is encrypted at rest using <strong>AES-256-GCM</strong> with a server-side key that is stored as an environment variable — not in the database. Even if the database were fully compromised, e-mail addresses would not be readable. The encryption key is never logged or committed to version control.</p>

  <h2>3. How We Use Data</h2>
  <ul>
    <li>To operate your account and authenticate you securely.</li>
    <li>To display your profile and project statistics to project authors.</li>
    <li>To investigate abuse reports (we may need to contact you).</li>
  </ul>

  <h2>4. Data Sharing</h2>
  <p>We do not sell your personal data. We do not share individual user data with third parties. We comply with valid legal requests from competent authorities.</p>

  <h2>5. Data Retention</h2>
  <p>Account data is retained while your account is active. Failed login records are automatically purged after 24 hours. You may request permanent deletion of your account by contacting us.</p>

  <h2>6. Your Rights (LGPD / GDPR)</h2>
  <p>You have the right to: access your personal data, correct inaccurate data, request deletion, withdraw consent, and receive your data in a portable format. Contact <a href="mailto:<?= PRIVACY_EMAIL ?>"><?= PRIVACY_EMAIL ?></a> to exercise these rights.</p>

  <h2>7. Security Measures</h2>
  <ul>
    <li>Passwords: bcrypt with cost 12 — one-way, cannot be reversed.</li>
    <li>E-mails: AES-256-GCM encryption at rest.</li>
    <li>All forms: CSRF token protection.</li>
    <li>All SQL: parameterized prepared statements (no SQL injection possible).</li>
    <li>File uploads: validated by MIME type using libmagic, not file extension.</li>
    <li>Sessions: HttpOnly, SameSite=Strict cookies.</li>
  </ul>

  <h2>8. Cookies</h2>
  <p>See our <a href="cookies.php">Cookie Policy</a>.</p>

  <h2>9. Contact</h2>
  <p><a href="mailto:<?= PRIVACY_EMAIL ?>"><?= PRIVACY_EMAIL ?></a></p>
</div>
<?php foot(); ?>
