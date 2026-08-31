/**
 * Lunar toast system. Exposes window.lunarToast(message, type) and converts
 * server-rendered flash messages (.notice/.error) into toasts on load.
 */
let stack = null;

const ensureStack = () => {
  if (stack) return stack;
  stack = document.createElement('div');
  stack.className = 'toast-stack';
  stack.setAttribute('aria-live', 'polite');
  document.body.appendChild(stack);
  return stack;
};

export const showToast = (message, type = 'info', duration = 4200) => {
  const text = String(message ?? '').trim();
  if (!text) return;

  const host = ensureStack();
  const toast = document.createElement('div');
  toast.className = `toast is-${['success', 'error', 'info'].includes(type) ? type : 'info'}`;
  toast.setAttribute('role', type === 'error' ? 'alert' : 'status');

  const icon = document.createElement('span');
  icon.className = 'toast-icon';
  icon.setAttribute('aria-hidden', 'true');
  icon.textContent = type === 'success' ? '✓' : type === 'error' ? '!' : '✦';

  const body = document.createElement('p');
  body.style.margin = '0';
  body.textContent = text;

  const close = document.createElement('button');
  close.type = 'button';
  close.className = 'toast-close';
  close.setAttribute('aria-label', document.documentElement.lang === 'en' ? 'Close notification' : 'Đóng thông báo');
  close.textContent = '×';

  toast.append(icon, body, close);
  host.appendChild(toast);
  window.requestAnimationFrame(() => toast.classList.add('is-visible'));

  let hideTimer = null;
  const dismiss = () => {
    window.clearTimeout(hideTimer);
    toast.classList.add('is-leaving');
    window.setTimeout(() => toast.remove(), 340);
  };
  close.addEventListener('click', dismiss);
  hideTimer = window.setTimeout(dismiss, duration);
};

export default function initToasts() {
  window.lunarToast = showToast;

  // Promote server flash messages into toasts (backend messages, verbatim).
  const flashStack = document.querySelector('.flash-stack');
  if (!flashStack) return;

  const notices = [...flashStack.querySelectorAll('.notice')];
  const errors = [...flashStack.querySelectorAll('.error')];
  if (!notices.length && !errors.length) return;

  flashStack.classList.add('is-toasted');
  notices.forEach((el) => showToast(el.textContent, 'success', 5200));
  errors.forEach((el) => showToast(el.textContent, 'error', 6500));
}
