function openModal(id) {
  const modal = document.getElementById(id);
  if (modal) modal.classList.add('active');
}

function closeModal(id) {
  const modal = document.getElementById(id);
  if (modal) modal.classList.remove('active');
}

function selectPM(el) {
  document.querySelectorAll('.payment-method').forEach(p => p.classList.remove('selected'));
  el.classList.add('selected');
}

document.querySelectorAll('.modal-overlay').forEach(el => {
  el.addEventListener('click', e => {
    if (e.target === el) return;
  });
});

function formatPhoneInput(value) {
  let digits = value.replace(/\D/g, '');
  if (digits.startsWith('60')) digits = digits.slice(2);
  if (digits.startsWith('0')) digits = digits.slice(1);
  digits = digits.slice(0, 11);
  if (!digits) return '';
  if (digits.length <= 2) return `+60 ${digits}`;
  if (digits.length <= 5) return `+60 ${digits.slice(0, 2)}-${digits.slice(2)}`;
  return `+60 ${digits.slice(0, 2)}-${digits.slice(2, 5)} ${digits.slice(5)}`;
}

document.querySelectorAll('[data-phone-format]').forEach(input => {
  input.value = formatPhoneInput(input.value);
  input.addEventListener('input', () => {
    input.value = formatPhoneInput(input.value);
  });
});

function initSidebarToggle() {
  const app = document.getElementById('app');
  const sidebar = document.getElementById('sidebar');
  const toggle = document.querySelector('.sidebar-toggle');
  const backdrop = document.getElementById('sidebarBackdrop');

  if (!app || !sidebar || !toggle || !backdrop) return;
  if (toggle.dataset.sidebarReady === 'true') return;
  toggle.dataset.sidebarReady = 'true';

  const isMobileSidebar = () => window.matchMedia('(max-width: 900px)').matches;

  const setSidebarOpen = (isOpen) => {
    sidebar.classList.toggle('open', isOpen);
    backdrop.classList.toggle('active', isOpen);
    document.body.classList.toggle('sidebar-open', isOpen && isMobileSidebar());
    toggle.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
    toggle.setAttribute('aria-label', isOpen ? 'Close menu' : 'Open menu');
  };

  const setSidebarCollapsed = (isCollapsed) => {
    app.classList.toggle('sidebar-collapsed', isCollapsed);
    toggle.setAttribute('aria-expanded', isCollapsed ? 'false' : 'true');
    toggle.setAttribute('aria-label', isCollapsed ? 'Show sidebar' : 'Hide sidebar');
  };

  toggle.addEventListener('click', () => {
    if (isMobileSidebar()) {
      setSidebarOpen(!sidebar.classList.contains('open'));
      return;
    }

    setSidebarCollapsed(!app.classList.contains('sidebar-collapsed'));
  });

  backdrop.addEventListener('click', () => {
    setSidebarOpen(false);
  });

  sidebar.querySelectorAll('a').forEach((link) => {
    link.addEventListener('click', () => {
      if (isMobileSidebar()) {
        setSidebarOpen(false);
      }
    });
  });

  window.addEventListener('resize', () => {
    if (!isMobileSidebar()) {
      setSidebarOpen(false);
      document.body.classList.remove('sidebar-open');
    }
  });
}

if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', initSidebarToggle);
} else {
  initSidebarToggle();
}
