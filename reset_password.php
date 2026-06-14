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
    <div id="js-notification-area"></div>
    <?php render_notification('auth'); ?>
    <form method="post" action="<?php echo e(app_url('action.php')); ?>" id="resetPasswordForm">
      <input type="hidden" name="action" value="reset_password">
      <input type="hidden" name="token" value="<?php echo e($_GET['token'] ?? ''); ?>">
      <div class="form-group">
        <label>New Password</label>
        <div class="password-field">
          <input class="form-control" type="password" name="new_password" id="resetNewPassword" required>
          <button class="password-toggle" type="button" aria-label="Show password" aria-pressed="false">
            <span class="password-toggle-eye" aria-hidden="true"></span>
          </button>
        </div>
        <div class="password-requirements" id="resetPasswordRequirements">
          <div id="reset-req-length"><span class="icon">○</span> Minimum 8 characters</div>
          <div id="reset-req-number"><span class="icon">○</span> Contains a number</div>
          <div id="reset-req-uppercase"><span class="icon">○</span> Contains uppercase letter</div>
          <div id="reset-req-special"><span class="icon">○</span> Contains special character</div>
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
const resetPasswordInput = document.getElementById('resetNewPassword');
function updateResetPasswordRequirements() {
  if (!resetPasswordInput) return true;
  document.getElementById('js-notification-area').innerHTML = '';

  const password = resetPasswordInput.value;
  const requirements = {
    'reset-req-length': password.length >= 8,
    'reset-req-number': /[0-9]/.test(password),
    'reset-req-uppercase': /[A-Z]/.test(password),
    'reset-req-special': /[^A-Za-z0-9]/.test(password)
  };

  Object.entries(requirements).forEach(function ([id, valid]) {
    const row = document.getElementById(id);
    const icon = row?.querySelector('.icon');
    if (!row || !icon) return;
    row.classList.toggle('valid', valid);
    icon.textContent = valid ? '✓' : '○';
  });

  return Object.values(requirements).every(Boolean);
}

resetPasswordInput?.addEventListener('input', updateResetPasswordRequirements);

document.getElementById('resetPasswordForm')?.addEventListener('submit', function (event) {
  if (!updateResetPasswordRequirements()) {
    event.preventDefault();
    document.getElementById('js-notification-area').innerHTML = '<div class="auth-notification error">Your password does not meet all the required criteria. Please check the requirements list and try again.</div>';
    window.scrollTo({ top: 0, behavior: 'smooth' });
  }
});

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
