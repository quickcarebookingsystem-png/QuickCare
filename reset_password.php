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
        <div class="password-field">
          <input class="form-control" type="password" name="new_password" required>
          <button class="password-toggle" type="button" aria-label="Show password" aria-pressed="false">
            <span class="password-toggle-eye" aria-hidden="true"></span>
          </button>
        </div>
      </div>
      <div class="form-group">
        <label>Confirm Password</label>
        <input class="form-control" type="password" name="confirm_password" required>
      </div>
      <button class="btn btn-primary" type="submit">Reset Password</button>
    </form>
  </div>
</div>
<script>
document.querySelectorAll('.password-toggle').forEach(function (button) {
  var input = button.closest('.password-field').querySelector('input');

  button.addEventListener('mousedown', function (event) {
    event.preventDefault();
  });

  button.addEventListener('click', function () {
    var showPassword = input.type === 'password';
    input.type = showPassword ? 'text' : 'password';
    button.setAttribute('aria-pressed', showPassword ? 'true' : 'false');
    button.setAttribute('aria-label', showPassword ? 'Hide password' : 'Show password');
    input.focus();
  });
});
</script>
</body>
</html>
