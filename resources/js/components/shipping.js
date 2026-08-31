/**
 * CHECKOUT VIỆT NAM — local MySQL, no runtime external API.
 * Tỉnh/Thành → Phường/Xã/Đặc khu → Shipping zone.
 * Backend is the source of truth: the browser never sends fee amounts;
 * this module only displays the quote the server returns.
 * Note: the backend exposes zone-based fees only (no km distance data).
 */
export const lunarFormatVnd = (value) =>
  `${new Intl.NumberFormat('vi-VN').format(Number(value || 0))} ₫`;

export const lunarFetchJson = async (url, params = {}, fallbackMessage = 'Unable to load address data.') => {
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
    throw new Error(payload.message || fallbackMessage);
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
    const vi = document.documentElement.lang === 'vi';
    const defaults = vi ? {
      addressError: 'Không thể tải dữ liệu địa chỉ.', selectAddress: 'Chọn địa chỉ để tính phí',
      selectAddressHelp: 'Hệ thống sẽ xác định khu vực từ Tỉnh/Thành và Phường/Xã.', chooseAddress: 'Chọn địa chỉ',
      determiningZone: 'Đang xác định khu vực…', calculatingShipping: 'Đang tính phí vận chuyển.', free: 'Miễn phí',
      freeShippingThreshold: 'Đơn hàng đạt mức miễn phí vận chuyển :amount.', configuredFee: 'Phí được tính từ khu vực giao hàng đã cấu hình trong hệ thống.',
      feeUnavailable: 'Chưa tính được phí', undetermined: 'Chưa xác định', loadingWard: 'Đang tải Phường / Xã...',
      selectProvinceFirst: 'Chọn Tỉnh / Thành trước', selectWard: 'Chọn Phường / Xã / Đặc khu', wardLoadError: 'Không tải được Phường / Xã',
      addressLoadError: 'Không tải được địa chỉ', loadingProvince: 'Đang tải Tỉnh / Thành...', selectProvince: 'Chọn Tỉnh / Thành',
      addressDataMissing: 'Chưa có dữ liệu địa chỉ', administrativeDataMissing: 'Chưa có dữ liệu hành chính',
    } : {
      addressError: 'Unable to load address data.', selectAddress: 'Select an address to calculate shipping',
      selectAddressHelp: 'Select a province/city and ward/commune so the system can determine your shipping zone.', chooseAddress: 'Choose an address',
      determiningZone: 'Determining shipping zone…', calculatingShipping: 'Calculating shipping.', free: 'Complimentary',
      freeShippingThreshold: 'Your order qualifies for complimentary delivery at :amount.', configuredFee: 'The fee is calculated from the configured shipping zone.',
      feeUnavailable: 'Shipping unavailable', undetermined: 'Not determined', loadingWard: 'Loading wards / communes...',
      selectProvinceFirst: 'Select a province / city first', selectWard: 'Select a ward / commune / special zone', wardLoadError: 'Unable to load wards / communes',
      addressLoadError: 'Unable to load the address', loadingProvince: 'Loading provinces / cities...', selectProvince: 'Select a province / city',
      addressDataMissing: 'Address data is unavailable', administrativeDataMissing: 'Administrative data is unavailable',
    };
    const labels = { ...defaults, ...JSON.parse(form.dataset.i18n || '{}') };
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
        labels.selectAddress,
        labels.selectAddressHelp,
        '—'
      );

      if (summaryFee) summaryFee.textContent = labels.chooseAddress;
    };

    const loadQuote = async () => {
      if (!form.dataset.quoteUrl) return;
      if (!province.value || !ward.value) {
        resetQuote();
        return;
      }

      zoneBox?.classList.add('is-loading');
      setQuote(labels.determiningZone, labels.calculatingShipping, '…');

      try {
        const data = await lunarFetchJson(form.dataset.quoteUrl, {
          province_code: province.value,
          ward_code: ward.value,
        }, labels.addressError);

        const feeText = data.shipping_fee > 0
          ? lunarFormatVnd(data.shipping_fee)
          : labels.free;

        if (data.free_shipping) {
          setQuote(
            data.zone_name,
            labels.freeShippingThreshold.replace(':amount', lunarFormatVnd(data.free_shipping_threshold)),
            feeText
          );
        } else {
          setQuote(
            data.zone_name,
            labels.configuredFee,
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
        setQuote(labels.feeUnavailable, error.message, '—');

        if (summaryFee) {
          summaryFee.textContent = labels.undetermined;
        }
      } finally {
        zoneBox?.classList.remove('is-loading');
      }
    };

    const loadWards = async (provinceCode, selected = '') => {
      ward.disabled = true;
      ward.innerHTML = `<option value="">${labels.loadingWard}</option>`;
      resetQuote();

      if (!provinceCode) {
        ward.innerHTML = `<option value="">${labels.selectProvinceFirst}</option>`;
        return;
      }

      try {
        const payload = await lunarFetchJson(form.dataset.wardsUrl, {
          province_code: provinceCode,
        }, labels.addressError);

        lunarFillSelect(
          ward,
          payload.data || [],
          labels.selectWard,
          selected
        );

        if (selected && ward.value) {
          await loadQuote();
        }
      } catch (error) {
        ward.innerHTML = `<option value="">${labels.wardLoadError}</option>`;
        setQuote(labels.addressLoadError, error.message, '—');
      }
    };

    const loadProvinces = async () => {
      province.disabled = true;
      province.innerHTML = `<option value="">${labels.loadingProvince}</option>`;

      try {
        const payload = await lunarFetchJson(form.dataset.provincesUrl, {}, labels.addressError);

        lunarFillSelect(
          province,
          payload.data || [],
          labels.selectProvince,
          selectedProvince
        );

        if (selectedProvince && province.value) {
          await loadWards(province.value, selectedWard);
        }
      } catch (error) {
        province.innerHTML = `<option value="">${labels.addressDataMissing}</option>`;

        setQuote(
          labels.administrativeDataMissing,
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
