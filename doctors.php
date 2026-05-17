<?php  
require_once __DIR__ . '/functions.php';
app_header('QuickCare - Our Doctors');
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
      <a class="landing-link" href="index.php#contact" draggable="false">Contact</a>
      <a class="btn btn-outline landing-nav-btn" href="login.php" draggable="false">Login</a>
      <a class="btn btn-primary landing-nav-btn" href="register.php" draggable="false">Register</a>
    </nav>
  </header>

  <main>
    <section class="landing-section">
      <div class="landing-section-heading">
        <span class="landing-kicker">Expert Medical Team</span>
        <h2>Meet Our Doctors</h2>
        <p>Our team of highly qualified specialists is dedicated to providing the best healthcare services for you and your family.</p>
      </div>

      <div class="doctor-grid" style="max-width: 1120px; margin: 0 auto;">
        <?php foreach ($DOCTORS as $d): ?>
          <div class="doctor-card">
            <div class="doctor-avatar"><?php echo $d['icon']; ?></div>
            <div class="doctor-name"><?php echo e($d['name']); ?></div>
            <div class="doctor-spec"><?php echo e($d['spec']); ?></div>
            <div class="doctor-avail">✅ Available <?php echo e($d['avail']); ?></div>
          </div>
        <?php endforeach; ?>
      </div>
    </section>

    <section class="landing-cta">
      <div>
        <h2>Consult with our specialists today</h2>
        <p>Register an account to book your next appointment with one of our expert doctors.</p>
      </div>
      <a class="btn btn-primary landing-cta-btn" href="register.php" draggable="false">Book Now</a>
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
        <a href="index.php#contact" draggable="false">Contact</a>
      </div>
      <div class="footer-nav-col">
        <h3>Our Services</h3>
        <a href="index.php#services" draggable="false">General Check-up</a>
        <a href="index.php#services" draggable="false">Dental Care</a>
        <a href="index.php#services" draggable="false">Eye Examination</a>
        <a href="index.php#services" draggable="false">Vaccination</a>
        <a href="index.php#services" draggable="false">Blood Test</a>
        <a href="index.php#services" draggable="false">Cardiology</a>
      </div>
    </div>
    <div class="footer-bottom">
      <p>&copy; <?php echo date('Y'); ?> QuickCare Clinic. All rights reserved.</p>
    </div>
  </footer>

  <a href="#" class="back-to-top" title="Back to top">↑</a>
</div>
</body>
</html>