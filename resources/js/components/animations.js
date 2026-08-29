/**
 * Lunar motion system — scroll reveal choreography.
 * Adds `.js-motion` on <html> so CSS entrance states only apply when JS runs,
 * then reveals `[data-reveal]` elements with IntersectionObserver.
 * Honors prefers-reduced-motion (no observer, everything visible).
 */
export default function initAnimations() {
  const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

  if (reduceMotion || !('IntersectionObserver' in window)) {
    document.querySelectorAll('[data-reveal]').forEach((el) => el.classList.add('is-revealed'));
    return;
  }

  document.documentElement.classList.add('js-motion');

  const observer = new IntersectionObserver((entries) => {
    entries.forEach((entry) => {
      if (!entry.isIntersecting) return;
      entry.target.classList.add('is-revealed');
      observer.unobserve(entry.target);
    });
  }, { rootMargin: '0px 0px -8% 0px', threshold: 0.08 });

  document.querySelectorAll('[data-reveal]').forEach((el, index) => {
    // Stagger siblings inside a [data-reveal-group] container.
    const group = el.closest('[data-reveal-group]');
    if (group && !el.style.getPropertyValue('--reveal-delay')) {
      const siblings = [...group.querySelectorAll('[data-reveal]')];
      const position = siblings.indexOf(el);
      el.style.setProperty('--reveal-delay', `${Math.min(position, 7) * 70}ms`);
    }
    observer.observe(el);
  });
}
