/**
 * CHECKOUT VIỆT NAM — local MySQL, no runtime external API.
 * Tỉnh/Thành → Phường/Xã/Đặc khu → Shipping zone.
 * Backend is the source of truth: the browser never sends fee amounts;
 * this module only displays the quote the server returns.
 * Note: the backend exposes zone-based fees only (no km distance data).
 */
export const lunarFormatVnd = (value) =>
  `${new Intl.NumberFormat('vi-VN').format(Number(value || 0))} ₫`;

export const lunarFetchJson = async (url, params = {}) => {
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

const pulse = (element) => {
  if (!element) return;
  element.classList.remove('is-updated');
  window.requestAnimationFrame(() => element.classList.add('is-updated'));
  window.setTimeout(() => element.classList.remove('is-updated'), 700);
};

export default function initShipping() {
  document.querySelectorAll('[data-vn-address-form]').forEach((form) => {
    const province = form.querySelector('[data-vn-province]');
    const ward = form.querySelector('[data-vn-ward]');

    if (!province || !ward) return;

    const selectedProvince = province.dataset.selected || '';
    const selectedWard = ward.dataset.selected || '';

    const zoneBox = form.querySelector('[data-shipping-zone-quote]');
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

      zoneBox?.classList.add('is-loading');
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

        if (summaryFee) {
          summaryFee.textContent = feeText;
          pulse(summaryFee);
        }
        if (summaryTotal) {
          summaryTotal.textContent = lunarFormatVnd(data.total);
          pulse(summaryTotal);
        }
      } catch (error) {
        setQuote('Chưa tính được phí', error.message, '—');

        if (summaryFee) {
          summaryFee.textContent = 'Chưa xác định';
        }
      } finally {
        zoneBox?.classList.remove('is-loading');
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
}
