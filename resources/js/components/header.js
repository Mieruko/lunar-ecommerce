/**
 * Header: mobile drawer + scroll-aware condensed state.
 * Scroll state uses a passive listener + rAF guard (no layout thrashing).
 */
export default function initHeader() {
  const mobileMenu = document.querySelector('[data-mobile-menu]');
  const toggle = document.querySelector('[data-menu-toggle]');

  const closeMenu = () => {
    mobileMenu?.classList.remove('open');
    document.body.classList.remove('menu-open');
    toggle?.setAttribute('aria-expanded', 'false');
  };

  toggle?.addEventListener('click', () => {
    const opening = !mobileMenu?.classList.contains('open');
    mobileMenu?.classList.toggle('open', opening);
    document.body.classList.toggle('menu-open', opening);
    toggle.setAttribute('aria-expanded', String(opening));
  });

  document.querySelectorAll('[data-menu-close]').forEach((button) => button.addEventListener('click', closeMenu));
  mobileMenu?.querySelectorAll('a').forEach((link) => link.addEventListener('click', closeMenu));
  document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape') closeMenu();
  });

  // Condensed header on scroll.
  const header = document.querySelector('.site-header');
  if (!header) return;

  let ticking = false;
  const applyState = () => {
    header.classList.toggle('is-condensed', window.scrollY > 40);
    ticking = false;
  };
  window.addEventListener('scroll', () => {
    if (ticking) return;
    ticking = true;
    window.requestAnimationFrame(applyState);
  }, { passive: true });
  applyState();
}

/** Bump the cart badge (used after add-to-cart redirects via session flag). */
export const bumpCartBadge = () => {
  const counter = document.querySelector('.bag-link .counter');
  if (!counter) return;
  counter.classList.remove('is-bumped');
  window.requestAnimationFrame(() => counter.classList.add('is-bumped'));
  window.setTimeout(() => counter.classList.remove('is-bumped'), 600);
};
