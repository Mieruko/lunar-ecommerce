/**
 * Product gallery: thumbnails, prev/next, touch swipe, desktop hover zoom.
 */
export default function initProductGallery() {
  document.querySelectorAll('[data-product-gallery]').forEach((gallery) => {
    const image = gallery.querySelector('[data-main-image]');
    const thumbs = [...gallery.querySelectorAll('[data-thumb]')];
    const currentLabel = gallery.querySelector('[data-image-current]');
    let currentIndex = Math.max(0, thumbs.findIndex((thumb) => thumb.classList.contains('active')));

    if (!image || !thumbs.length) return;

    const selectImage = (requestedIndex) => {
      currentIndex = (requestedIndex + thumbs.length) % thumbs.length;
      const selected = thumbs[currentIndex];
      const nextImage = new Image();
      nextImage.src = selected.dataset.thumb;

      image.classList.add('is-switching');
      image.src = selected.dataset.thumb;
      image.alt = selected.dataset.thumbAlt || image.alt;
      window.requestAnimationFrame(() => image.classList.remove('is-switching'));

      thumbs.forEach((thumb, index) => {
        const active = index === currentIndex;
        thumb.classList.toggle('active', active);
        thumb.setAttribute('aria-pressed', String(active));
      });

      if (currentLabel) currentLabel.textContent = String(currentIndex + 1).padStart(2, '0');
    };

    thumbs.forEach((thumb, index) => thumb.addEventListener('click', () => selectImage(index)));
    gallery.querySelector('[data-gallery-prev]')?.addEventListener('click', () => selectImage(currentIndex - 1));
    gallery.querySelector('[data-gallery-next]')?.addEventListener('click', () => selectImage(currentIndex + 1));

    const stage = image.closest('.main-image') || image.parentElement;
    if (!stage) return;

    // Touch swipe (mobile).
    let touchStartX = null;
    stage.addEventListener('touchstart', (event) => {
      touchStartX = event.touches[0]?.clientX ?? null;
    }, { passive: true });
    stage.addEventListener('touchend', (event) => {
      if (touchStartX === null) return;
      const deltaX = (event.changedTouches[0]?.clientX ?? touchStartX) - touchStartX;
      touchStartX = null;
      if (Math.abs(deltaX) < 42) return;
      selectImage(deltaX < 0 ? currentIndex + 1 : currentIndex - 1);
    }, { passive: true });

    // Desktop hover zoom (pointer: fine only, respects reduced motion).
    const canZoom = window.matchMedia('(hover: hover) and (pointer: fine)').matches
      && !window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    if (!canZoom) return;

    stage.classList.add('has-zoom');
    stage.addEventListener('mousemove', (event) => {
      const rect = stage.getBoundingClientRect();
      const x = ((event.clientX - rect.left) / rect.width) * 100;
      const y = ((event.clientY - rect.top) / rect.height) * 100;
      image.style.transformOrigin = `${x}% ${y}%`;
    });
    stage.addEventListener('mouseenter', () => stage.classList.add('is-zooming'));
    stage.addEventListener('mouseleave', () => {
      stage.classList.remove('is-zooming');
      image.style.transformOrigin = '';
    });
  });
}
