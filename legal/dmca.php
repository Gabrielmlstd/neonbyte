<?php require_once '../includes/layout.php'; head('Content Removal (DMCA)'); ?>
<div class="legal-page fade-up">
  <h1>Content Removal / DMCA</h1>
  <p class="page-sub">Last updated: <?= date('d F Y') ?></p>

  <h2>Our policy</h2>
  <p>Neonbyte respects intellectual property rights. We do not host files; we only list metadata and links to public repositories. If you believe a listed project infringes your copyright, violates our <a href="terms.php">Terms of Service</a>, or is being used to facilitate illegal activity, you may request removal.</p>

  <h2>How to submit a removal request</h2>
  <p>Send an e-mail to <a href="mailto:<?= DMCA_EMAIL ?>"><?= DMCA_EMAIL ?></a> with:</p>
  <ol>
    <li>Your full name and contact information.</li>
    <li>A description of the copyrighted work or content at issue.</li>
    <li>The URL of the specific Neonbyte project page you wish to have removed.</li>
    <li>A statement that you have a good faith belief that the use is not authorised by the copyright owner.</li>
    <li>A statement that the information is accurate and, under penalty of perjury, that you are authorised to act on behalf of the copyright owner.</li>
    <li>Your electronic or physical signature.</li>
  </ol>

  <h2>Response time</h2>
  <p>We will respond within 5 business days. Valid requests result in the project being removed from our directory.</p>

  <h2>Counter-notice</h2>
  <p>If you believe your project was removed in error, you may send a counter-notice to the same address with your contact information and a statement that you consent to the jurisdiction of a competent court.</p>

  <h2>Contact</h2>
  <p><a href="mailto:<?= DMCA_EMAIL ?>"><?= DMCA_EMAIL ?></a></p>
</div>
<?php foot(); ?>
