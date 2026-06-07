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
