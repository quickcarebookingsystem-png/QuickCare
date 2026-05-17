<?php  
require_once __DIR__ . '/functions.php';
app_header('QuickCare - Clinic Booking System');
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
        <a class="landing-link" href="#services" draggable="false">Services <span class="dropdown-arrow">▾</span></a>
        <div class="landing-dropdown-content">
          <a href="#services" draggable="false">General Check-up</a>
          <a href="#services" draggable="false">Dental Care</a>
          <a href="#services" draggable="false">Eye Examination</a>
          <a href="#services" draggable="false">Vaccination</a>
          <a href="#services" draggable="false">Blood Test</a>
          <a href="#services" draggable="false">Cardiology</a>
        </div>
      </div>
      <a class="landing-link" href="#contact" draggable="false">Contact</a>
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

    <section class="landing-section">
      <div class="landing-section-heading">
        <h2>What QuickCare Provides</h2>
        <p>Focused tools for patients who want a smoother clinic booking experience.</p>
      </div>

      <div class="services-grid landing-services">
        <article class="service-card">
          <span class="service-icon">+</span>
          <div class="service-name">Patient Registration</div>
          <p class="service-desc">Create a profile so your clinic details are ready when needed.</p>
        </article>

        <article class="service-card">
          <span class="service-icon">+</span>
          <div class="service-name">Appointment Booking</div>
          <p class="service-desc">Request visits and keep appointment information organized.</p>
        </article>

        <article class="service-card">
          <span class="service-icon">+</span>
          <div class="service-name">Clinic Access</div>
          <p class="service-desc">Use one website to start and continue your healthcare journey.</p>
        </article>
      </div>
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
        <a class="landing-brand" href="index.php" draggable="false">
          <span class="logo-icon landing-logo-mark">🏥</span>
          <span>QuickCare</span>
        </a>
        <p>Providing accessible healthcare through simplified appointment booking and modern management tools.</p>
      </div>
      <div class="footer-nav-col">
        <h3>Quick Links</h3>
        <a href="index.php" draggable="false">Home</a>
        <a href="#about" draggable="false">About Us</a>
        <a href="#services" draggable="false">Services</a>
        <a href="#contact" draggable="false">Contact</a>
      </div>
      <div class="footer-nav-col">
        <h3>Our Services</h3>
        <a href="#services" draggable="false">General Check-up</a>
        <a href="#services" draggable="false">Dental Care</a>
        <a href="#services" draggable="false">Eye Examination</a>
        <a href="#services" draggable="false">Vaccination</a>
        <a href="#services" draggable="false">Blood Test</a>
        <a href="#services" draggable="false">Cardiology</a>
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
