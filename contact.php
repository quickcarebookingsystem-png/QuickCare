<?php
require_once __DIR__ . '/functions.php';
app_header('QuickCare - Contact Us');
?>
<body>
<div class="landing-page">
  <header class="landing-header">
    <a class="landing-brand" href="index.php" draggable="false">
      <span class="logo-icon landing-logo-mark">🏥</span>
      <span>QuickCare</span>
    </a>
    <nav class="landing-nav" aria-label="Main navigation">
      <a class="landing-link" href="index.php" draggable="false">Home</a>
      <div class="landing-dropdown">
        <a class="landing-link" href="doctors.php" draggable="false">About Us <span class="dropdown-arrow">▾</span></a>
        <div class="landing-dropdown-content">
          <a href="doctors.php" draggable="false">Our Doctors</a>
        </div>
      </div>
      <div class="landing-dropdown">
        <a class="landing-link" href="index.php#services" draggable="false">Services <span class="dropdown-arrow">▾</span></a>
        <div class="landing-dropdown-content">
          <a href="index.php#services" draggable="false">General Check-up</a>
          <a href="index.php#services" draggable="false">Dental Care</a>
          <a href="index.php#services" draggable="false">Eye Examination</a>
          <a href="index.php#services" draggable="false">Vaccination</a>
          <a href="index.php#services" draggable="false">Blood Test</a>
          <a href="index.php#services" draggable="false">Cardiology</a>
        </div>
      </div>
      <a class="landing-link" href="contact.php" draggable="false">Contact</a>
      <a class="btn btn-outline landing-nav-btn" href="login.php" draggable="false">Login</a>
      <a class="btn btn-primary landing-nav-btn" href="register.php" draggable="false">Register</a>
    </nav>
  </header>

  <main>
    <section class="landing-section">
      <div class="landing-section-heading">
        <span class="landing-kicker">Reach Out To Us</span>
        <h2>Contact Details</h2>
        <p>We are available through multiple channels to assist with your healthcare needs.</p>
      </div>

      <div class="card" style="max-width: 700px; margin: 0 auto; padding: 32px;">
        <div style="display: flex; flex-direction: column; gap: 24px;">
          <div style="display: flex; align-items: flex-start; gap: 16px;">
            <span style="font-size: 1.5rem;">📍</span>
            <div>
              <h4 style="margin: 0 0 4px 0; color: var(--primary);">Address</h4>
              <p style="margin: 0; line-height: 1.5;">54 Jalan Padi 1, Bandar Baru Uda, 81200 Johor Bahru, Johor</p>
            </div>
          </div>
          <div style="display: flex; align-items: flex-start; gap: 16px;">
            <span style="font-size: 1.5rem;">☎️</span>
            <div>
              <h4 style="margin: 0 0 4px 0; color: var(--primary);">Hotline</h4>
              <p style="margin: 0; line-height: 1.5;">07-235 6202 (24 Hours)</p>
            </div>
          </div>
          <div style="display: flex; align-items: flex-start; gap: 16px;">
            <span style="font-size: 1.5rem;">🏢</span>
            <div>
              <h4 style="margin: 0 0 4px 0; color: var(--primary);">Office</h4>
              <p style="margin: 0; line-height: 1.5;">07-238 7675 / 07-238 7677<br><small class="text-muted">Mon-Fri | 8am-5pm</small></p>
            </div>
          </div>
          <div style="display: flex; align-items: flex-start; gap: 16px;">
            <span style="font-size: 1.5rem;">💬</span>
            <div>
              <h4 style="margin: 0 0 4px 0; color: var(--primary);">WhatsApp</h4>
              <p style="margin: 0; line-height: 1.5;">016-4165175</p>
            </div>
          </div>
          <div style="display: flex; align-items: flex-start; gap: 16px;">
            <span style="font-size: 1.5rem;">🎧</span>
            <div>
              <h4 style="margin: 0 0 4px 0; color: var(--primary);">Customer Service</h4>
              <p style="margin: 0; line-height: 1.5;">011-15171015<br><small class="text-muted">Mon-Fri | 8am-5pm</small></p>
            </div>
          </div>
          <div style="display: flex; align-items: flex-start; gap: 16px;">
            <span style="font-size: 1.5rem;">📧</span>
            <div>
              <h4 style="margin: 0 0 4px 0; color: var(--primary);">Email</h4>
              <p style="margin: 0; line-height: 1.5;">quickcare@gmail.com</p>
            </div>
          </div>
        </div>
      </div>
    </section>
  </main>

  <footer class="landing-footer">
    <div class="footer-container">
      <div class="footer-brand">
        <a class="landing-brand" href="index.php" draggable="false">
          <span class="logo-icon landing-logo-mark">🏥</span>
          <span>QuickCare</span>
        </a>
        <p>Providing accessible healthcare through simplified appointment booking and modern management tools.</p>
      </div>
      <div class="footer-nav-col">
        <h3>Quick Links</h3>
        <a href="index.php" draggable="false">Home</a>
        <a href="doctors.php" draggable="false">About Us</a>
        <a href="index.php#services" draggable="false">Services</a>
        <a href="contact.php" draggable="false">Contact</a>
      </div>
    </div>
  </footer>
</div>
</body>
</html>