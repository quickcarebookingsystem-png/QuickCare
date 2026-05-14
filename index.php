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
</div>
</body>
</html>
