/**
 * Cart & purchase micro-interactions:
 * - Quantity steppers
 * - Copy voucher code buttons (with toast)
 * - Submit loading states (add to cart / place order / apply voucher)
 * - Cart line removal choreography
 * - Cart badge bump after a successful add (server flash present)
 */
import { showToast } from './toast.js';
import { bumpCartBadge } from './header.js';

export default function initCart() {
  document.querySelectorAll('[data-quantity]').forEach((container) => {
    const input = container.querySelector('input');
    if (!input) return;
    container.querySelector('[data-minus]')?.addEventListener('click', () => {
      input.value = Math.max(1, Number(input.value) - 1);
      input.dispatchEvent(new Event('change', { bubbles: true }));
    });
    container.querySelector('[data-plus]')?.addEventListener('click', () => {
      input.value = Math.min(10, Number(input.value) + 1);
      input.dispatchEvent(new Event('change', { bubbles: true }));
    });
  });

  document.querySelectorAll('[data-copy-code]').forEach((button) => button.addEventListener('click', async () => {
    try {
      await navigator.clipboard.writeText(button.dataset.copyCode);
      const label = button.querySelector('span');
      if (label) {
        const original = label.textContent;
        label.textContent = 'Đã sao chép';
        window.setTimeout(() => { label.textContent = original; }, 1600);
      }
      showToast(`Đã sao chép mã ${button.dataset.copyCode}`, 'success', 2600);
    } catch (_) {
      // Clipboard access can be denied on non-HTTPS local environments.
    }
  }));

  // Loading state on submit buttons — server-side flow is untouched.
  document.querySelectorAll('form[data-loading-form]').forEach((form) => {
    form.addEventListener('submit', () => {
      const submit = form.querySelector('button[type="submit"], .button');
      if (submit && !submit.classList.contains('is-loading-button')) {
        // Delay a frame so the click ripple is visible before lock.
        window.requestAnimationFrame(() => submit.classList.add('is-loading-button'));
      }
    });
  });

  // Cart line removal: fade + slide the row while the DELETE request navigates.
  document.querySelectorAll('form[data-remove-line]').forEach((form) => {
    form.addEventListener('submit', () => {
      form.closest('.lunar-cart-item')?.classList.add('is-removing');
    });
  });

  // After an add-to-cart redirect the server renders a success flash — bump the badge.
  if (document.querySelector('.flash-stack .notice')) {
    bumpCartBadge();
  }
}
