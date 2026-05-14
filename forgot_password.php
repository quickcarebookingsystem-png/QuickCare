<?php
require_once __DIR__ . '/functions.php';
app_header('QuickCare - Forgot Password');
?>
<body>
<div id="auth-screen" class="view active">
  <div class="auth-card">
    <div class="auth-logo">
      <div class="logo-icon auth-logo-mark">🏥</div>
      <h1>QuickCare</h1>
      <p>Reset Your Password</p>
    </div>
    <form method="post" action="<?php echo e(app_url('action.php')); ?>">
      <input type="hidden" name="action" value="forgot_password">
      <div class="form-group">
        <label>Email Address</label>
        <input class="form-control" type="email" name="email" required>
      </div>
      <button class="btn btn-primary" type="submit">Send Reset Link</button>
      <div style="text-align:center; margin-top:12px">
        <a href="login.php" style="font-size:0.85rem; color:var(--primary); text-decoration:none" draggable = "false">Back to Login</a>
      </div>
    </form>
  </div>
</div>
</body>
</html>
