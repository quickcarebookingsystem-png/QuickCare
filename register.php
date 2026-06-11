<?php
require_once __DIR__ . '/functions.php'; 
app_header('QuickCare - Register');
?>
<body>
<div id="auth-screen" class="view active">
  <a href="index.php" class="btn btn-outline" style="position:absolute; top:24px; right:24px; z-index:2; background:rgba(255,255,255,0.92)" draggable = "false">Back to Home</a>
  <div class="auth-card">
    <div class="auth-logo">
      <div class="logo-icon auth-logo-mark">🏥</div>
      <h1>QuickCare</h1>
      <p>Create New Account</p>
    </div>
    <div id="js-notification-area"></div>
    <?php render_notification('auth'); ?>
    <form method="post" action="<?php echo e(app_url('action.php')); ?>" id="registerForm">
      <input type="hidden" name="action" value="register">
      <div class="form-group"><label>Full Name</label><input class="form-control" name="name" required></div>
      <div class="form-group"><label>Email Address</label><input class="form-control" type="email" name="email" required></div>
      <div class="form-group">
        <label>Password</label>
        <div class="password-field">
          <input class="form-control" type="password" name="password" id="registerPassword" required>
          <button class="password-toggle" type="button" aria-label="Show password" aria-pressed="false">
            <span class="password-toggle-eye" aria-hidden="true"></span>
          </button>
        </div>
        <div id="passwordRequirements" style="margin-top: 10px; background: var(--surface2); padding: 12px; border-radius: 8px; border: 1px solid var(--border);">
          <div id="req-length" style="font-size: 0.85rem; color: var(--text-muted); margin-bottom: 4px;">
            <span class="icon">○</span> Minimum 8 characters
          </div>
          <div id="req-number" style="font-size: 0.85rem; color: var(--text-muted); margin-bottom: 4px;">
            <span class="icon">○</span> Contains a number
          </div>
          <div id="req-uppercase" style="font-size: 0.85rem; color: var(--text-muted); margin-bottom: 4px;">
            <span class="icon">○</span> Contains uppercase letter
          </div>
          <div id="req-special" style="font-size: 0.85rem; color: var(--text-muted); margin-bottom: 4px;">
            <span class="icon">○</span> Contains special character
          </div>
        </div>
      </div>
      <button class="btn btn-primary" type="submit" id="registerSubmit">Sign up</button>
      <div style="text-align:center; margin-top:12px"><a href="login.php" style="font-size:0.85rem; color:var(--primary); text-decoration:none" draggable = "false">Log In</a></div>
    </form>
  </div>
</div>

<script>
let isPasswordValid = false;
document.getElementById('registerPassword').addEventListener('input', function() {
    // Clear the custom notification when the user starts typing
    document.getElementById('js-notification-area').innerHTML = '';

    const password = this.value;
    const requirements = {
        'req-length': password.length >= 8,
        'req-number': /[0-9]/.test(password),
        'req-uppercase': /[A-Z]/.test(password),
        'req-special': /[^A-Za-z0-9]/.test(password)
    };

    for (const [id, isValid] of Object.entries(requirements)) {
        const el = document.getElementById(id);
        const icon = el.querySelector('.icon');
        if (isValid) {
            el.style.color = '#28a745'; // Green for success
            icon.textContent = '✓';
        } else {
            el.style.color = 'var(--text-muted)';
            icon.textContent = '○';
        }
    }

    isPasswordValid = Object.values(requirements).every(Boolean);
});

document.getElementById('registerForm').addEventListener('submit', function(e) {
    if (!isPasswordValid) {
        e.preventDefault();
        const notificationArea = document.getElementById('js-notification-area');
        notificationArea.innerHTML = '<div class="auth-notification error">Your password does not meet all the required criteria. Please check the requirements list and try again.</div>';
        
        // Smooth scroll to the top so the user sees the notification
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
