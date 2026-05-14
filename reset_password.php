<?php
require_once __DIR__ . '/functions.php';
app_header('QuickCare - Reset Password');
?>
<body>
<div id="auth-screen" class="view active">
  <div class="auth-card">
    <div class="auth-logo">
      <div class="logo-icon auth-logo-mark">🏥</div>
      <h1>QuickCare</h1>
      <p>Clinic Booking System</p>
    </div>
    <form method="post" action="<?php echo e(app_url('action.php')); ?>">
      <input type="hidden" name="action" value="reset_password">
      <input type="hidden" name="token" value="<?php echo e($_GET['token'] ?? ''); ?>">
      <div class="form-group">
        <label>New Password</label>
        <input class="form-control" type="password" name="new_password" required>
      </div>
      <div class="form-group">
        <label>Confirm Password</label>
        <input class="form-control" type="password" name="confirm_password" required>
      </div>
      <button class="btn btn-primary" type="submit">Reset Password</button>
    </form>
  </div>
</div>
</body>
</html>
