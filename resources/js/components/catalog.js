/**
 * Catalog / shop page interactions:
 * - Filter drawer open/close (existing behavior)
 * - Loading state when filters/sort submit (grid dims + shimmer instead of a hard flash)
 */
export default function initCatalog() {
  const filterPanel = document.querySelector('[data-filter-panel]');

  const closeFilters = () => {
    filterPanel?.classList.remove('open');
    document.body.classList.remove('filters-open');
  };

  document.querySelectorAll('[data-filter-toggle]').forEach((button) => button.addEventListener('click', () => {
    const opening = !filterPanel?.classList.contains('open');
    filterPanel?.classList.toggle('open', opening);
    document.body.classList.toggle('filters-open', opening);
  }));
  document.querySelectorAll('[data-filter-close]').forEach((button) => button.addEventListener('click', closeFilters));
  document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape') closeFilters();
  });

  // Loading choreography for filter/sort submissions (full page nav still happens;
  // this only communicates state while the server responds).
  const grid = document.querySelector('[data-catalog-grid]');
  if (!grid) return;

  const markLoading = () => grid.classList.add('is-grid-loading');
  document.querySelectorAll('[data-catalog-form]').forEach((form) => {
    form.addEventListener('submit', markLoading);
  });
  document.querySelectorAll('[data-catalog-sort]').forEach((select) => {
    select.addEventListener('change', markLoading);
  });
  document.querySelectorAll('.filter-chips a, .pagination a').forEach((link) => {
    link.addEventListener('click', markLoading);
  });
}
