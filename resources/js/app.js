const mobileMenu = document.querySelector('[data-mobile-menu]');
const mobileMenuToggle = document.querySelector('[data-menu-toggle]');
const closeMobileMenu = () => {
  mobileMenu?.classList.remove('open');
  document.body.classList.remove('menu-open');
  mobileMenuToggle?.setAttribute('aria-expanded', 'false');
};
mobileMenuToggle?.addEventListener('click', () => {
  const opening = !mobileMenu?.classList.contains('open');
  mobileMenu?.classList.toggle('open', opening);
  document.body.classList.toggle('menu-open', opening);
  mobileMenuToggle.setAttribute('aria-expanded', String(opening));
});
document.querySelectorAll('[data-menu-close]').forEach((button) => button.addEventListener('click', closeMobileMenu));
mobileMenu?.querySelectorAll('a').forEach((link) => link.addEventListener('click', closeMobileMenu));

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
  if (event.key !== 'Escape') return;
  closeMobileMenu();
  closeFilters();
});
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
});
document.querySelectorAll('[data-quantity]').forEach((container) => { const input = container.querySelector('input'); container.querySelector('[data-minus]')?.addEventListener('click', () => input.value = Math.max(1, Number(input.value) - 1)); container.querySelector('[data-plus]')?.addEventListener('click', () => input.value = Math.min(10, Number(input.value) + 1)); });
document.querySelectorAll('[data-copy-code]').forEach((button) => button.addEventListener('click', async () => {
  try {
    await navigator.clipboard.writeText(button.dataset.copyCode);
    const label = button.querySelector('span');
    if (label) {
      const original = label.textContent;
      label.textContent = 'Đã sao chép';
      window.setTimeout(() => { label.textContent = original; }, 1600);
    }
  } catch (_) {
    // Clipboard access can be denied on non-HTTPS local environments.
  }
}));

/* =========================================================
   CHECKOUT VIỆT NAM — local MySQL, no runtime external API
   Tỉnh/Thành → Phường/Xã/Đặc khu → Shipping zone
   ========================================================= */

const lunarFormatVnd = (value) =>
    `${new Intl.NumberFormat('vi-VN').format(Number(value || 0))} ₫`;

const lunarFetchJson = async (url, params = {}) => {
    const requestUrl = new URL(url, window.location.origin);

    Object.entries(params).forEach(([key, value]) => {
        if (value !== '' && value !== null && value !== undefined) {
            requestUrl.searchParams.set(key, value);
        }
    });

    const response = await fetch(requestUrl, {
        headers: {
            Accept: 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
        },
    });

    const payload = await response.json().catch(() => ({}));

    if (!response.ok) {
        throw new Error(payload.message || 'Không thể tải dữ liệu địa chỉ.');
    }

    return payload;
};

const lunarFillSelect = (select, items, placeholder, selected = '') => {
    select.innerHTML = '';

    const first = document.createElement('option');
    first.value = '';
    first.textContent = placeholder;
    select.appendChild(first);

    items.forEach((item) => {
        const option = document.createElement('option');
        option.value = String(item.code);
        option.textContent = item.name;

        if (String(item.code) === String(selected)) {
            option.selected = true;
        }

        select.appendChild(option);
    });

    select.disabled = false;
};

document.querySelectorAll('[data-vn-address-form]').forEach((form) => {
    const province = form.querySelector('[data-vn-province]');
    const ward = form.querySelector('[data-vn-ward]');

    if (!province || !ward) return;

    const selectedProvince = province.dataset.selected || '';
    const selectedWard = ward.dataset.selected || '';

    const zoneTitle = form.querySelector('[data-shipping-zone-title]');
    const zoneMessage = form.querySelector('[data-shipping-zone-message]');
    const zoneFee = form.querySelector('[data-shipping-zone-fee]');

    const summaryFee = document.querySelector('[data-shipping-fee]');
    const summaryTotal = document.querySelector('[data-checkout-total]');

    const setQuote = (title, message, fee = '—') => {
        if (zoneTitle) zoneTitle.textContent = title;
        if (zoneMessage) zoneMessage.textContent = message;
        if (zoneFee) zoneFee.textContent = fee;
    };

    const resetQuote = () => {
        setQuote(
            'Chọn địa chỉ để tính phí',
            'Hệ thống sẽ xác định khu vực từ Tỉnh/Thành và Phường/Xã.',
            '—'
        );

        if (summaryFee) summaryFee.textContent = 'Chọn địa chỉ';
    };

    const loadQuote = async () => {
        if (!form.dataset.quoteUrl) return;
        if (!province.value || !ward.value) {
            resetQuote();
            return;
        }

        setQuote('Đang xác định khu vực…', 'Đang tính phí vận chuyển.', '…');

        try {
            const data = await lunarFetchJson(form.dataset.quoteUrl, {
                province_code: province.value,
                ward_code: ward.value,
            });

            const feeText = data.shipping_fee > 0
                ? lunarFormatVnd(data.shipping_fee)
                : 'Miễn phí';

            if (data.free_shipping) {
                setQuote(
                    data.zone_name,
                    `Đơn hàng đạt mức miễn phí vận chuyển ${lunarFormatVnd(data.free_shipping_threshold)}.`,
                    feeText
                );
            } else {
                setQuote(
                    data.zone_name,
                    'Phí được tính từ khu vực giao hàng đã cấu hình trong hệ thống.',
                    feeText
                );
            }

            if (summaryFee) summaryFee.textContent = feeText;
            if (summaryTotal) summaryTotal.textContent = lunarFormatVnd(data.total);
        } catch (error) {
            setQuote('Chưa tính được phí', error.message, '—');

            if (summaryFee) {
                summaryFee.textContent = 'Chưa xác định';
            }
        }
    };

    const loadWards = async (provinceCode, selected = '') => {
        ward.disabled = true;
        ward.innerHTML = '<option value="">Đang tải Phường / Xã...</option>';
        resetQuote();

        if (!provinceCode) {
            ward.innerHTML = '<option value="">Chọn Tỉnh / Thành trước</option>';
            return;
        }

        try {
            const payload = await lunarFetchJson(form.dataset.wardsUrl, {
                province_code: provinceCode,
            });

            lunarFillSelect(
                ward,
                payload.data || [],
                'Chọn Phường / Xã / Đặc khu',
                selected
            );

            if (selected && ward.value) {
                await loadQuote();
            }
        } catch (error) {
            ward.innerHTML = '<option value="">Không tải được Phường / Xã</option>';
            setQuote('Không tải được địa chỉ', error.message, '—');
        }
    };

    const loadProvinces = async () => {
        province.disabled = true;
        province.innerHTML = '<option value="">Đang tải Tỉnh / Thành...</option>';

        try {
            const payload = await lunarFetchJson(form.dataset.provincesUrl);

            lunarFillSelect(
                province,
                payload.data || [],
                'Chọn Tỉnh / Thành',
                selectedProvince
            );

            if (selectedProvince && province.value) {
                await loadWards(province.value, selectedWard);
            }
        } catch (error) {
            province.innerHTML = '<option value="">Chưa có dữ liệu địa chỉ</option>';

            setQuote(
                'Chưa có dữ liệu hành chính',
                'Hãy chạy: php artisan lunar:sync-vietnam-addresses',
                '—'
            );
        }
    };

    province.addEventListener('change', () => {
        loadWards(province.value);
    });

    ward.addEventListener('change', loadQuote);

    loadProvinces();
});
