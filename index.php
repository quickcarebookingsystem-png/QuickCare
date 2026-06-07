<?php  
require_once __DIR__ . '/functions.php';
app_header('QuickCare - Clinic Booking System');
$landingServices = get_services($conn);
?>
<body>
<div class="landing-page" id="top">
  <header class="landing-header">
    <a class="landing-brand" href="#top" draggable="false">
      <span class="logo-icon landing-logo-mark">🏥</span>
      <span>QuickCare</span>
    </a>
    <nav class="landing-nav" aria-label="Main navigation">
      <a class="landing-link" href="#top" draggable="false">Home</a>
      <div class="landing-dropdown">
        <a class="landing-link" href="#about" draggable="false">About Us <span class="dropdown-arrow">▾</span></a>
        <div class="landing-dropdown-content">
          <a href="#doctors" draggable="false">Our Doctors</a>
        </div>
      </div>
      <div class="landing-dropdown">
        <a class="landing-link" href="#services" draggable="false">Services <span class="dropdown-arrow">▾</span></a>
        <div class="landing-dropdown-content">
          <?php foreach ($landingServices as $service): ?>
            <a href="javascript:void(0)" onclick="openServiceDetailByName(<?php echo e(json_encode($service['service_name'])); ?>)" draggable="false"><?php echo e($service['service_name']); ?></a>
          <?php endforeach; ?>
        </div>
      </div>
      <a class="landing-link" href="contact.php" draggable="false">Contact</a>
      <a class="btn btn-outline landing-nav-btn" href="login.php" draggable="false">Login</a>
      <a class="btn btn-primary landing-nav-btn" href="register.php" draggable="false">Register</a>
    </nav>
  </header>

  <main>
    <section class="landing-hero">
      <div class="landing-hero-content">
        <span class="landing-kicker">Clinic Booking System</span>
        <h1>Simple clinic appointments, all in one place.</h1>
        <p>
          QuickCare helps patients create an account, book appointments, manage
          visits, and stay connected with clinic services through a clean online system.
        </p>
        <div class="landing-actions">
          <a class="btn btn-primary landing-hero-btn" href="register.php" draggable="false">Create Account</a>
          <a class="btn btn-outline landing-hero-btn" href="login.php" draggable="false">Log In</a>
        </div>
      </div>

      <div class="auth-card landing-card">
        <div class="auth-logo">
          <div class="logo-icon auth-logo-mark">🏥</div>
          <h1>QuickCare</h1>
          <p>Patient Access</p>
        </div>
        <div class="landing-checklist">
          <div class="landing-check-item">
            <span>01</span>
            <div>
              <h3>Register Online</h3>
              <p>Create your patient account before booking a visit.</p>
            </div>
          </div>
          <div class="landing-check-item">
            <span>02</span>
            <div>
              <h3>Book Faster</h3>
              <p>Send appointment requests without repeating your details.</p>
            </div>
          </div>
          <div class="landing-check-item">
            <span>03</span>
            <div>
              <h3>Manage Care</h3>
              <p>Keep your clinic activity organized from one dashboard.</p>
            </div>
          </div>
        </div>
      </div>
    </section>

    <section class="landing-section" id="about">
      <div class="landing-section-heading">
        <h2>System Features</h2>
        <p>Focused tools for patients who want a smoother clinic booking experience.</p>
      </div>
      <?php render_features(); ?>
    </section>

    <section class="landing-section" id="doctors">
      <div class="landing-section-heading">
        <span class="landing-kicker">Expert Medical Team</span>
        <h2>Meet Our Doctors</h2>
        <p>Our team of highly qualified specialists is dedicated to providing the best healthcare services for you and your family.</p>
      </div>
      <div style="max-width: 1120px; margin: 0 auto;">
        <?php render_doctors('guest', false); ?>
      </div>
    </section>

    <section class="landing-section" id="services">
      <div class="landing-section-heading">
        <span class="landing-kicker">Our Specializations</span>
        <h2>Clinic Services</h2>
        <p>Comprehensive healthcare solutions tailored to your needs.</p>
      </div>
      <?php render_services('guest', false); ?>
    </section>

    <section class="landing-cta">
      <div>
        <h2>Ready to continue?</h2>
        <p>Register as a new patient or log in to access your QuickCare account.</p>
      </div>
      <a class="btn btn-primary landing-cta-btn" href="register.php" draggable="false">Get Started</a>
    </section>
  </main>

  <footer class="landing-footer">
    <div class="footer-container">
      <div class="footer-brand">
        <a class="landing-brand" href="#top" draggable="false">
          <span class="logo-icon landing-logo-mark">🏥</span>
          <span>QuickCare</span>
        </a>
        <p>Providing accessible healthcare through simplified appointment booking and modern management tools.</p>
      </div>
      <div class="footer-nav-col">
        <h3>Quick Links</h3>
        <a href="#top" draggable="false">Home</a>
        <a href="#about" draggable="false">About Us</a>
        <a href="#services" draggable="false">Services</a>
        <a href="contact.php" draggable="false">Contact</a>
      </div>
      <div class="footer-nav-col">
        <h3>Our Services</h3>
        <?php foreach ($landingServices as $service): ?>
          <a href="javascript:void(0)" onclick="openServiceDetailByName(<?php echo e(json_encode($service['service_name'])); ?>)" draggable="false"><?php echo e($service['service_name']); ?></a>
        <?php endforeach; ?>
      </div>
    </div>
    <div class="footer-bottom">
      <p>&copy; <?php echo date('Y'); ?> QuickCare Clinic. All rights reserved.</p>
    </div>
  </footer>

  <a href="#" class="back-to-top" title="Back to top">↑</a>
  <a class="landing-whatsapp-btn" href="https://wa.me/601110807180" target="_blank" rel="noopener" aria-label="Chat with customer service on WhatsApp" title="Customer Service WhatsApp" draggable="false">
    <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false">
      <path d="M12 3a8 8 0 0 0-8 8v3a3 3 0 0 0 3 3h1v-6H6a6 6 0 0 1 12 0h-2v6h1a3 3 0 0 0 3-3v-3a8 8 0 0 0-8-8Z"/>
      <path d="M9 18h2.2c.3.9 1.1 1.5 2.1 1.5H15a1 1 0 1 0 0-2h-1.7a.5.5 0 0 1-.5-.5v-.2H9V18Z"/>
    </svg>
  </a>
</div>

<script>
function openServiceDetailByName(serviceName) {
    // Find the corresponding service card in the grid rendered by render_services()
    const cards = document.querySelectorAll('.service-card');
    for (const card of cards) {
        if (card.dataset.name && card.dataset.name.trim() === serviceName) {
            // Trigger the existing showServiceDetails function defined in render_services()
            if (typeof showServiceDetails === 'function') {
                showServiceDetails(card);
            }
            return;
        }
    }
}

const backToTop = document.querySelector('.back-to-top');
function updateBackToTop() {
    if (!backToTop) return;
    backToTop.classList.toggle('visible', window.scrollY > 220);
}
window.addEventListener('scroll', updateBackToTop, { passive: true });
updateBackToTop();
</script>
</body>
</html>
