<?php require_once '../includes/layout.php'; head('Terms of Service'); ?>
<div class="legal-page fade-up">
  <h1>Terms of Service</h1>
  <p class="page-sub">Last updated: <?= date('d F Y') ?></p>

  <h2>1. Acceptance</h2>
  <p>By accessing or using Neonbyte ("the Service"), you agree to be bound by these Terms. If you do not agree, do not use the Service.</p>

  <h2>2. Description</h2>
  <p>Neonbyte is a directory of open source software, with a focus on security research, reverse engineering, and related tooling. We do not host any files. We link to official repositories and release pages maintained by third parties.</p>

  <h2>3. Permitted Use</h2>
  <p>This platform is intended for:</p>
  <ul>
    <li>Security researchers, students, and professionals studying reverse engineering, binary analysis, and related fields.</li>
    <li>CTF (Capture The Flag) participants looking for open source tooling.</li>
    <li>Developers sharing open source tools under recognised open source licenses.</li>
  </ul>
  <p><strong>Prohibited use:</strong> You may not use this platform or any tools listed here for unauthorized access to systems, malicious activity, or any purpose prohibited by applicable law. Neonbyte does not endorse or facilitate illegal activity.</p>

  <h2>4. User Accounts</h2>
  <p>You must provide accurate information when creating an account. You are responsible for maintaining the security of your credentials. We reserve the right to suspend or terminate accounts that violate these Terms.</p>

  <h2>5. Content Policy</h2>
  <p>Only open source software with a recognised open source license may be listed. Projects must link to the official public repository. Misleading, harmful, or illegal content is prohibited. The site operator reserves the right to remove any project at any time.</p>

  <h2>6. No File Hosting &amp; No Endorsement</h2>
  <p>Neonbyte does not host, distribute, or verify the files linked from project pages. Any software you download is at your own risk. Always verify the source, checksum, and authenticity before executing software.</p>

  <h2>7. Intellectual Property</h2>
  <p>Each listed project retains its own license. The Neonbyte website design and code are proprietary. User-submitted content (descriptions, screenshots) remains owned by the submitter; you grant Neonbyte a non-exclusive licence to display it.</p>

  <h2>8. Limitation of Liability</h2>
  <p>THE SERVICE IS PROVIDED "AS IS" WITHOUT WARRANTIES OF ANY KIND. THE OPERATOR IS NOT RESPONSIBLE FOR THE CONTENT, SAFETY, OR RELIABILITY OF THIRD-PARTY SOFTWARE LINKED FROM THIS SITE, NOR FOR ANY DAMAGES RESULTING FROM YOUR USE OF TOOLS LISTED HERE.</p>

  <h2>9. Changes</h2>
  <p>We may update these Terms at any time. Continued use after changes constitutes acceptance.</p>

  <h2>10. Contact</h2>
  <p>Questions? <a href="mailto:<?= LEGAL_EMAIL ?>"><?= LEGAL_EMAIL ?></a></p>
</div>
<?php foot(); ?>
