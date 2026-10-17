<?php require_once '../includes/layout.php'; head('Cookie Policy'); ?>
<div class="legal-page fade-up">
  <h1>Cookie Policy</h1>
  <p class="page-sub">Last updated: <?= date('d F Y') ?></p>

  <h2>What are cookies?</h2>
  <p>Cookies are small text files stored on your device by your browser. We use a minimal set strictly necessary to operate the site.</p>

  <h2>Cookies we use</h2>
  <table class="legal-table">
    <thead><tr><th>Name</th><th>Purpose</th><th>Type</th><th>Duration</th></tr></thead>
    <tbody>
      <tr><td>nb_sess</td><td>Keeps you logged in; stores session reference (CSRF token, user ID). Your real data is on the server, not in the cookie.</td><td>Essential</td><td>Browser session</td></tr>
      <tr><td>nb-theme (localStorage)</td><td>Remembers your chosen colour theme. Stored locally on your device — never sent to our server.</td><td>Preference</td><td>Persistent (localStorage)</td></tr>
      <tr><td>nb-nav (localStorage)</td><td>Remembers sidebar collapsed state.</td><td>Preference</td><td>Persistent (localStorage)</td></tr>
    </tbody>
  </table>

  <h2>What we do NOT use</h2>
  <ul>
    <li>No advertising cookies or tracking pixels.</li>
    <li>No third-party analytics (Google Analytics, etc.).</li>
    <li>No fingerprinting.</li>
  </ul>

  <h2>Managing cookies</h2>
  <p>You can delete or block cookies in your browser settings. Blocking the <code>nb_sess</code> cookie will prevent you from signing in.</p>
  <p>To clear localStorage preferences, open your browser's Developer Tools → Application → Local Storage → clear entries prefixed with <code>nb-</code>.</p>

  <h2>Contact</h2>
  <p><a href="mailto:<?= PRIVACY_EMAIL ?>"><?= PRIVACY_EMAIL ?></a></p>
</div>
<?php foot(); ?>
