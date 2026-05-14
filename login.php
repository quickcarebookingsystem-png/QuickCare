<?php
require_once __DIR__ . '/functions.php';
guest_only();
app_header('QuickCare - Login');
?>
<body>
<div id="auth-screen" class="view active">
  <a href="index.php" class="btn btn-outline" style="position:absolute; top:24px; right:24px; z-index:2; background:rgba(255,255,255,0.92)" draggable = "false">Back to Home</a>
  <div class="auth-card">
    <div class="auth-logo">
      <div class="logo-icon auth-logo-mark">🏥</div>
      <h1>QuickCare</h1>
      <p>Clinic Booking System</p>
    </div>
    <form method="post" action="<?php echo e(app_url('action.php')); ?>">
      <input type="hidden" name="action" value="login">
      <div class="form-group">
        <label>Email Address</label>
        <input class="form-control" type="email" name="email" required>
      </div>
      <div class="form-group">
        <label>Password</label>
        <input class="form-control" type="password" name="password" required>
      </div>
      <div class="forgot-link">
        <a href="forgot_password.php" draggable = "false">Forgot Password?</a>
      </div>
      <button class="btn btn-primary" type="submit">Login</button>
      <div style="text-align:center; margin-top:12px"><a href="register.php" style="font-size:0.85rem; color:var(--primary); text-decoration:none" draggable = "false">Sign up</a></div>
    </form>
  </div>
</div>
</body>
</html>
