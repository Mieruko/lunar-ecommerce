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

/* =========================================================
   LUNAR CONCIERGE — storefront support chat
   Deterministic support flow with an optional staff handoff.
   ========================================================= */

document.querySelectorAll('[data-support-chat]').forEach((root) => {
    const launcher = root.querySelector('[data-chat-toggle]');
    const panel = root.querySelector('[data-chat-panel]');
    const closeButton = root.querySelector('[data-chat-close]');
    const messagesElement = root.querySelector('[data-chat-messages]');
    const loadingElement = root.querySelector('[data-chat-loading]');
    const emptyElement = root.querySelector('[data-chat-empty]');
    const suggestionsElement = root.querySelector('[data-chat-suggestions]');
    const statusElement = root.querySelector('[data-chat-status]');
    const statusLabel = statusElement?.querySelector('b');
    const form = root.querySelector('[data-chat-form]');
    const input = root.querySelector('[data-chat-input]');
    const sendButton = root.querySelector('[data-chat-send]');
    const handoffButton = root.querySelector('[data-chat-handoff]');
    const composerNote = root.querySelector('[data-chat-composer-note]');
    const errorElement = root.querySelector('[data-chat-error]');
    const errorText = root.querySelector('[data-chat-error-text]');
    const retryButton = root.querySelector('[data-chat-retry]');
    const badge = root.querySelector('[data-chat-badge]');
    const announcer = root.querySelector('[data-chat-announcer]');

    if (!launcher || !panel || !messagesElement || !form || !input || !sendButton) {
        return;
    }

    const endpoints = {
        chat: root.dataset.chatUrl,
        message: root.dataset.messageUrl,
        handoff: root.dataset.handoffUrl,
        read: root.dataset.readUrl,
    };

    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';
    const renderedMessageKeys = new Set();
    const requestControllers = {
        refresh: null,
        submit: null,
        handoff: null,
        read: null,
    };

    let isOpen = false;
    let afterId = 0;
    let conversationReference = null;
    let conversationStatus = 'bot';
    let retryAction = null;
    let pollTimer = null;
    let readTimer = null;
    let closeTimer = null;
    let unreadCount = 0;

    const isRecord = (value) => value !== null && typeof value === 'object' && !Array.isArray(value);
    const hasOwn = (object, property) => Object.prototype.hasOwnProperty.call(object, property);

    const announce = (message) => {
        if (!announcer) return;
        announcer.textContent = '';
        window.requestAnimationFrame(() => {
            announcer.textContent = message;
        });
    };

    const setBadge = (value) => {
        unreadCount = Math.max(0, Number(value) || 0);
        if (!badge) return;

        badge.hidden = unreadCount === 0;
        badge.textContent = unreadCount > 99 ? '99+' : String(unreadCount);
        badge.setAttribute('aria-label', `${unreadCount} tin nhắn chưa đọc`);
    };

    const setLoading = (loading) => {
        messagesElement.setAttribute('aria-busy', String(loading));
        if (loadingElement) {
            loadingElement.hidden = !loading;
        }
    };

    const updateEmptyState = () => {
        if (!emptyElement) return;
        emptyElement.hidden = Boolean(messagesElement.querySelector('.support-chat-message'));
    };

    const showError = (message, onRetry = null) => {
        retryAction = onRetry;
        if (!errorElement || !errorText) return;

        errorText.textContent = message;
        errorElement.hidden = false;
        if (retryButton) {
            retryButton.hidden = typeof onRetry !== 'function';
        }
    };

    const hideError = () => {
        retryAction = null;
        if (errorElement) errorElement.hidden = true;
    };

    const getErrorMessage = (payload, fallback) => {
        if (typeof payload?.message === 'string' && payload.message.trim()) {
            return payload.message.trim();
        }

        if (isRecord(payload?.errors)) {
            const firstError = Object.values(payload.errors).flat().find((value) => typeof value === 'string');
            if (firstError) return firstError;
        }

        return fallback;
    };

    const requestJson = async (url, options = {}) => {
        const response = await fetch(url, {
            credentials: 'same-origin',
            ...options,
            headers: {
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                ...(csrfToken ? { 'X-CSRF-TOKEN': csrfToken } : {}),
                ...(options.body ? { 'Content-Type': 'application/json' } : {}),
                ...(options.headers || {}),
            },
        });

        const payload = response.status === 204
            ? {}
            : await response.json().catch(() => ({}));

        if (!response.ok) {
            let message = 'Dịch vụ hỗ trợ đang tạm gián đoạn.';
            if (response.status === 429) {
                message = 'Bạn thao tác hơi nhanh. Vui lòng thử lại sau ít phút.';
            } else if (response.status < 500) {
                message = getErrorMessage(payload, message);
            }

            const error = new Error(message);
            error.status = response.status;
            throw error;
        }

        return payload;
    };

    const unwrapPayload = (payload) => {
        const base = isRecord(payload) ? payload : {};
        const nested = isRecord(base.data) ? base.data : {};
        const source = { ...base, ...nested };
        let payloadMessages = Array.isArray(source.messages) ? source.messages : [];

        if (!payloadMessages.length && isRecord(source.message)) {
            payloadMessages = [source.message];
        }

        return {
            source,
            messages: payloadMessages,
            suggestions: Array.isArray(source.suggestions) ? source.suggestions : [],
            hasSuggestions: hasOwn(source, 'suggestions'),
            hasConversation: hasOwn(source, 'conversation'),
            conversation: isRecord(source.conversation) ? source.conversation : null,
        };
    };

    const normalizeSender = (rawSender) => {
        const sender = String(rawSender || 'bot').toLowerCase();

        if (['customer', 'user', 'visitor', 'end_user', 'client'].includes(sender)) return 'customer';
        if (['staff', 'agent', 'human', 'admin', 'employee'].includes(sender)) return 'staff';
        if (sender === 'system') return 'system';
        return 'bot';
    };

    const formatMessageTime = (rawDate) => {
        if (!rawDate) return '';
        const date = new Date(rawDate);
        if (Number.isNaN(date.getTime())) return '';

        return new Intl.DateTimeFormat('vi-VN', {
            hour: '2-digit',
            minute: '2-digit',
        }).format(date);
    };

    const formatProductPrice = (product) => {
        const amount = Number(product?.price_amount);
        if (!Number.isFinite(amount) || amount < 0) return '';

        const currency = String(product?.currency || 'VND').toUpperCase();
        if (currency === 'VND') {
            return `${new Intl.NumberFormat('vi-VN').format(amount)} ₫`;
        }

        try {
            return new Intl.NumberFormat('vi-VN', {
                style: 'currency',
                currency,
            }).format(amount);
        } catch (_) {
            return `${new Intl.NumberFormat('vi-VN').format(amount)} ${currency}`;
        }
    };

    const createProductCards = (rawProducts) => {
        if (!Array.isArray(rawProducts) || !rawProducts.length) return null;

        const list = document.createElement('div');
        list.className = 'support-chat-products';
        list.setAttribute('aria-label', 'Sản phẩm được tư vấn');

        rawProducts.slice(0, 3).forEach((product) => {
            if (!isRecord(product)) return;

            const name = String(product.name || '').trim();
            if (!name) return;

            const productUrl = safeLink(product.url);
            const card = document.createElement(productUrl ? 'a' : 'article');
            card.className = 'support-chat-product';

            if (productUrl) {
                card.href = productUrl.href;
                card.setAttribute('aria-label', `Xem sản phẩm ${name}`);
                if (productUrl.origin !== window.location.origin) {
                    card.target = '_blank';
                    card.rel = 'noopener noreferrer';
                }
            }

            const media = document.createElement('span');
            media.className = 'support-chat-product-media';
            const imageUrl = safeLink(product.image_url);

            if (imageUrl) {
                const image = document.createElement('img');
                image.src = imageUrl.href;
                image.alt = name;
                image.loading = 'lazy';
                media.appendChild(image);
            } else {
                const placeholder = document.createElement('span');
                placeholder.textContent = 'LJ';
                placeholder.setAttribute('aria-hidden', 'true');
                media.appendChild(placeholder);
            }

            const details = document.createElement('span');
            details.className = 'support-chat-product-details';

            const brand = document.createElement('small');
            brand.textContent = String(product.brand || 'LUNAR JEWELS').trim();

            const title = document.createElement('strong');
            title.textContent = name;

            const footer = document.createElement('span');
            footer.className = 'support-chat-product-footer';

            const price = document.createElement('b');
            price.textContent = formatProductPrice(product);

            const stock = document.createElement('small');
            const stockStatus = ['in_stock', 'low_stock', 'out_of_stock'].includes(product.stock_status)
                ? product.stock_status
                : 'out_of_stock';
            stock.className = `is-${stockStatus}`;
            stock.textContent = String(product.stock_label || (stockStatus === 'in_stock' ? 'Sẵn hàng' : 'Tạm hết'));

            footer.append(price, stock);
            details.append(brand, title, footer);
            card.append(media, details);
            list.appendChild(card);
        });

        return list.childElementCount ? list : null;
    };

    const messageKey = (message, sender, body, index) => {
        if (message.id !== undefined && message.id !== null) {
            return `id:${message.id}`;
        }

        const timestamp = message.sent_at || message.created_at || message.timestamp || index;
        return `message:${sender}:${timestamp}:${body}`;
    };

    const createMessageElement = (message, index) => {
        if (!isRecord(message) || message.is_internal || message.visibility === 'internal') {
            return null;
        }

        const body = String(message.body ?? message.message ?? message.text ?? message.content ?? '').trim();
        if (!body) return null;

        const sender = normalizeSender(message.sender ?? message.sender_type ?? message.role ?? message.author_type);
        const key = messageKey(message, sender, body, index);
        if (renderedMessageKeys.has(key)) return null;
        renderedMessageKeys.add(key);

        const numericId = Number(message.id);
        if (Number.isFinite(numericId)) {
            afterId = Math.max(afterId, numericId);
        }

        const article = document.createElement('article');
        article.className = `support-chat-message is-${sender}`;
        article.dataset.messageKey = key;

        const avatar = document.createElement('span');
        avatar.className = 'support-chat-message-avatar';
        avatar.setAttribute('aria-hidden', 'true');
        avatar.textContent = sender === 'customer' ? 'B' : sender === 'staff' ? 'L' : '✦';

        const content = document.createElement('div');
        content.className = 'support-chat-message-content';

        const meta = document.createElement('div');
        meta.className = 'support-chat-message-meta';

        const author = document.createElement('strong');
        author.textContent = sender === 'customer'
            ? 'Bạn'
            : sender === 'staff'
                ? 'Chuyên viên LUNAR'
                : sender === 'system'
                    ? 'Cập nhật'
                    : 'Lunar Concierge';
        meta.appendChild(author);

        const displayTime = formatMessageTime(message.sent_at || message.created_at || message.timestamp);
        if (displayTime) {
            const time = document.createElement('time');
            time.dateTime = message.sent_at || message.created_at || message.timestamp;
            time.textContent = displayTime;
            meta.appendChild(time);
        }

        const bubble = document.createElement('p');
        bubble.textContent = body;

        content.append(meta, bubble);
        const productCards = createProductCards(message.metadata?.products);
        if (productCards) content.appendChild(productCards);
        article.append(avatar, content);
        return { element: article, sender };
    };

    const appendMessages = (messages) => {
        if (!Array.isArray(messages) || !messages.length) {
            updateEmptyState();
            return 0;
        }

        const wasNearBottom = messagesElement.scrollHeight - messagesElement.scrollTop - messagesElement.clientHeight < 90;
        const fragment = document.createDocumentFragment();
        let incomingCount = 0;
        let appendedCount = 0;

        messages.forEach((message, index) => {
            const rendered = createMessageElement(message, index);
            if (!rendered) return;

            fragment.appendChild(rendered.element);
            appendedCount += 1;
            if (rendered.sender !== 'customer' && rendered.sender !== 'system') {
                incomingCount += 1;
            }
        });

        if (!appendedCount) {
            updateEmptyState();
            return 0;
        }

        messagesElement.appendChild(fragment);
        updateEmptyState();

        if (wasNearBottom || renderedMessageKeys.size === appendedCount) {
            window.requestAnimationFrame(() => {
                messagesElement.scrollTop = messagesElement.scrollHeight;
            });
        }

        if (!isOpen || document.visibilityState !== 'visible') {
            setBadge(unreadCount + incomingCount);
        }

        return incomingCount;
    };

    const safeLink = (value) => {
        if (typeof value !== 'string' || !value.trim()) return null;

        try {
            const url = new URL(value, window.location.origin);
            if (!['http:', 'https:'].includes(url.protocol)) return null;
            return url;
        } catch (_) {
            return null;
        }
    };

    const updateControls = () => {
        const isSubmitting = Boolean(requestControllers.submit);
        const isHandingOff = Boolean(requestControllers.handoff);
        sendButton.disabled = isSubmitting || !input.value.trim();
        form.toggleAttribute('aria-busy', isSubmitting);

        suggestionsElement?.querySelectorAll('button').forEach((button) => {
            button.disabled = isSubmitting || isHandingOff;
        });

        if (handoffButton) {
            const alreadyWithStaff = ['unassigned', 'queued', 'assigned', 'human', 'waiting_customer'].includes(conversationStatus);
            handoffButton.disabled = isSubmitting || isHandingOff || alreadyWithStaff;
        }
    };

    const renderSuggestions = (suggestions) => {
        if (!suggestionsElement) return;
        suggestionsElement.replaceChildren();

        suggestions.forEach((suggestion) => {
            const item = typeof suggestion === 'string'
                ? { type: 'message', label: suggestion, value: suggestion }
                : suggestion;
            if (!isRecord(item)) return;

            const label = String(item.label ?? item.title ?? item.value ?? item.text ?? '').trim();
            if (!label) return;

            const type = String(item.type ?? item.action ?? (item.url || item.href ? 'url' : 'message')).toLowerCase();

            if (['url', 'link'].includes(type)) {
                const url = safeLink(item.url ?? item.href ?? item.value);
                if (!url) return;

                const link = document.createElement('a');
                link.className = 'support-chat-suggestion';
                link.href = url.href;
                link.textContent = label;
                if (url.origin !== window.location.origin) {
                    link.target = '_blank';
                    link.rel = 'noopener noreferrer';
                }
                suggestionsElement.appendChild(link);
                return;
            }

            const button = document.createElement('button');
            button.className = 'support-chat-suggestion';
            button.type = 'button';
            button.textContent = label;

            if (['handoff', 'human', 'agent'].includes(type)) {
                button.addEventListener('click', () => requestHandoff());
            } else {
                const value = String(item.value ?? item.message ?? item.text ?? label).trim();
                button.addEventListener('click', () => submitMessage(value));
            }

            suggestionsElement.appendChild(button);
        });

        suggestionsElement.hidden = suggestionsElement.childElementCount === 0;
        updateControls();
    };

    const normalizeConversationStatus = (conversation, responseStatus) => {
        const rawStatus = String(conversation?.status ?? responseStatus ?? 'bot').toLowerCase();
        const isHuman = Boolean(conversation?.is_human || conversation?.assigned);

        if (['resolved', 'closed', 'completed'].includes(rawStatus)) return 'resolved';
        if (['waiting_customer', 'customer_reply'].includes(rawStatus)) return 'waiting_customer';
        if (['unassigned', 'pending_handoff', 'handoff_requested', 'queued', 'queue'].includes(rawStatus)) return 'unassigned';
        if (isHuman || ['assigned', 'human', 'staff', 'open_human'].includes(rawStatus)) return 'assigned';
        return 'bot';
    };

    const updateConversationStatus = (conversation, responseStatus) => {
        conversationStatus = normalizeConversationStatus(conversation, responseStatus);

        const labels = {
            bot: 'Trợ lý tự động',
            unassigned: 'Đang chờ chuyên viên',
            assigned: 'Chuyên viên đang hỗ trợ',
            waiting_customer: 'Chờ phản hồi của bạn',
            resolved: 'Yêu cầu đã hoàn tất',
        };

        if (statusElement) statusElement.dataset.status = conversationStatus;
        if (statusLabel) statusLabel.textContent = labels[conversationStatus] || labels.bot;
        root.dataset.chatStatus = conversationStatus;

        if (composerNote) {
            if (conversationStatus === 'unassigned') {
                composerNote.textContent = 'Đã chuyển yêu cầu · Chuyên viên sẽ xem toàn bộ nội dung trước đó';
            } else if (['assigned', 'waiting_customer'].includes(conversationStatus)) {
                composerNote.textContent = 'Đang trao đổi với chuyên viên chăm sóc khách hàng';
            } else if (conversationStatus === 'resolved') {
                composerNote.textContent = 'Gửi tin nhắn mới nếu bạn cần mở lại yêu cầu';
            } else {
                composerNote.textContent = 'Tự động trả lời · Có thể chuyển nhân viên bất cứ lúc nào';
            }
        }

        updateControls();
    };

    const clearConversationMessages = () => {
        messagesElement.querySelectorAll('.support-chat-message').forEach((message) => message.remove());
        renderedMessageKeys.clear();
        afterId = 0;
        updateEmptyState();
    };

    const queueRead = () => {
        window.clearTimeout(readTimer);
        if (!isOpen || document.visibilityState !== 'visible' || !endpoints.read) return;

        readTimer = window.setTimeout(async () => {
            if (requestControllers.read) return;
            const controller = new AbortController();
            requestControllers.read = controller;

            try {
                await requestJson(endpoints.read, {
                    method: 'POST',
                    body: JSON.stringify({}),
                    signal: controller.signal,
                });
                setBadge(0);
            } catch (error) {
                if (error.name !== 'AbortError') {
                    // Read receipts are best-effort and must not interrupt the conversation.
                }
            } finally {
                if (requestControllers.read === controller) requestControllers.read = null;
            }
        }, 350);
    };

    const handlePayload = (payload) => {
        const normalized = unwrapPayload(payload);
        const { source, conversation, messages, suggestions } = normalized;

        if (normalized.hasConversation) {
            const nextReference = conversation?.reference ?? conversation?.public_id ?? conversation?.id ?? null;
            if (conversationReference && nextReference && conversationReference !== String(nextReference)) {
                clearConversationMessages();
            }
            conversationReference = nextReference === null ? null : String(nextReference);
        }

        if (normalized.hasConversation || typeof source.status === 'string') {
            updateConversationStatus(conversation, source.status);
        }
        const incomingCount = appendMessages(messages);
        if (normalized.hasSuggestions) renderSuggestions(suggestions);

        const serverUnread = Number(source.unread_count ?? conversation?.unread_count);
        if (!isOpen && Number.isFinite(serverUnread)) {
            setBadge(serverUnread);
        }

        if (isOpen && (incomingCount > 0 || (Number.isFinite(serverUnread) && serverUnread > 0))) {
            queueRead();
        }
    };

    const clearPoll = () => {
        window.clearTimeout(pollTimer);
        pollTimer = null;
    };

    const schedulePoll = () => {
        clearPoll();
        if (!isOpen || document.visibilityState !== 'visible') return;
        pollTimer = window.setTimeout(() => refreshMessages(), 5000);
    };

    const refreshMessages = async ({ initial = false } = {}) => {
        if (!isOpen || document.visibilityState !== 'visible' || !endpoints.chat) return;

        requestControllers.refresh?.abort();
        const controller = new AbortController();
        requestControllers.refresh = controller;
        clearPoll();

        if (initial && renderedMessageKeys.size === 0) setLoading(true);

        try {
            const url = new URL(endpoints.chat, window.location.origin);
            url.searchParams.set('after_id', String(afterId));
            const payload = await requestJson(url, { signal: controller.signal });
            handlePayload(payload);
            hideError();
        } catch (error) {
            if (error.name !== 'AbortError') {
                const message = error.status === 419
                    ? 'Phiên làm việc đã hết hạn. Vui lòng tải lại trang.'
                    : error.message;
                showError(message, () => refreshMessages({ initial: true }));
            }
        } finally {
            if (requestControllers.refresh === controller) requestControllers.refresh = null;
            setLoading(false);
            updateEmptyState();
            schedulePoll();
        }
    };

    const submitMessage = async (rawMessage) => {
        const message = String(rawMessage ?? '').trim().slice(0, 2000);
        if (!message || requestControllers.submit || !endpoints.message) return;

        const controller = new AbortController();
        requestControllers.submit = controller;
        hideError();
        updateControls();

        try {
            const payload = await requestJson(endpoints.message, {
                method: 'POST',
                body: JSON.stringify({ message }),
                signal: controller.signal,
            });

            input.value = '';
            input.style.height = '';
            handlePayload(payload);
            announce('Tin nhắn đã được gửi.');

            const returnedMessages = unwrapPayload(payload).messages;
            if (!returnedMessages.length) {
                await refreshMessages();
            }
        } catch (error) {
            if (error.name !== 'AbortError') {
                const errorMessage = error.status === 419
                    ? 'Phiên làm việc đã hết hạn. Vui lòng tải lại trang trước khi gửi lại.'
                    : error.message || 'Chưa gửi được tin nhắn.';
                showError(errorMessage, () => submitMessage(message));
                announce('Chưa gửi được tin nhắn.');
            }
        } finally {
            if (requestControllers.submit === controller) requestControllers.submit = null;
            updateControls();
            schedulePoll();
            if (isOpen) input.focus({ preventScroll: true });
        }
    };

    const requestHandoff = async () => {
        if (requestControllers.handoff || !endpoints.handoff) return;

        const controller = new AbortController();
        requestControllers.handoff = controller;
        hideError();
        updateControls();

        try {
            const payload = await requestJson(endpoints.handoff, {
                method: 'POST',
                body: JSON.stringify({}),
                signal: controller.signal,
            });
            handlePayload(payload);
            updateConversationStatus(
                unwrapPayload(payload).conversation || { status: 'unassigned' },
                'unassigned'
            );
            announce('Yêu cầu đã được chuyển tới chuyên viên chăm sóc khách hàng.');
        } catch (error) {
            if (error.name !== 'AbortError') {
                showError(error.message || 'Chưa thể chuyển tới chuyên viên.', requestHandoff);
                announce('Chưa thể chuyển tới chuyên viên.');
            }
        } finally {
            if (requestControllers.handoff === controller) requestControllers.handoff = null;
            updateControls();
            schedulePoll();
        }
    };

    const resizeInput = () => {
        input.style.height = 'auto';
        input.style.height = `${Math.min(input.scrollHeight, 104)}px`;
        updateControls();
    };

    const openChat = () => {
        if (isOpen) return;
        isOpen = true;
        window.clearTimeout(closeTimer);
        panel.hidden = false;
        panel.setAttribute('aria-hidden', 'false');
        launcher.setAttribute('aria-expanded', 'true');
        setBadge(0);

        window.requestAnimationFrame(() => {
            root.classList.add('is-open');
            panel.focus({ preventScroll: true });
        });

        refreshMessages({ initial: renderedMessageKeys.size === 0 });
    };

    const closeChat = () => {
        if (!isOpen) return;
        isOpen = false;
        root.classList.remove('is-open');
        panel.setAttribute('aria-hidden', 'true');
        launcher.setAttribute('aria-expanded', 'false');
        clearPoll();
        window.clearTimeout(readTimer);
        requestControllers.refresh?.abort();
        requestControllers.refresh = null;

        closeTimer = window.setTimeout(() => {
            if (!isOpen) panel.hidden = true;
        }, 220);

        launcher.focus({ preventScroll: true });
    };

    const getFocusableElements = () => [...panel.querySelectorAll(
        'a[href], button:not([disabled]), textarea:not([disabled]), input:not([disabled]), [tabindex]:not([tabindex="-1"])'
    )].filter((element) => !element.hidden && element.getClientRects().length > 0);

    launcher.addEventListener('click', () => {
        if (isOpen) closeChat();
        else openChat();
    });
    closeButton?.addEventListener('click', closeChat);

    retryButton?.addEventListener('click', () => {
        const action = retryAction;
        hideError();
        if (typeof action === 'function') action();
    });

    form.addEventListener('submit', (event) => {
        event.preventDefault();
        submitMessage(input.value);
    });

    input.addEventListener('input', resizeInput);
    input.addEventListener('keydown', (event) => {
        if (event.key === 'Enter' && !event.shiftKey && !event.isComposing) {
            event.preventDefault();
            form.requestSubmit();
        }
    });

    handoffButton?.addEventListener('click', requestHandoff);

    panel.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') {
            event.preventDefault();
            closeChat();
            return;
        }

        if (event.key !== 'Tab') return;
        const focusable = getFocusableElements();
        if (!focusable.length) {
            event.preventDefault();
            panel.focus();
            return;
        }

        const first = focusable[0];
        const last = focusable[focusable.length - 1];
        if (event.shiftKey && document.activeElement === first) {
            event.preventDefault();
            last.focus();
        } else if (!event.shiftKey && document.activeElement === last) {
            event.preventDefault();
            first.focus();
        }
    });

    document.addEventListener('visibilitychange', () => {
        if (!isOpen) return;
        if (document.visibilityState === 'visible') {
            refreshMessages();
        } else {
            clearPoll();
            requestControllers.refresh?.abort();
            requestControllers.refresh = null;
        }
    });

    window.addEventListener('online', () => {
        if (!isOpen) return;
        hideError();
        announce('Đã kết nối lại dịch vụ hỗ trợ.');
        refreshMessages();
    });

    window.addEventListener('offline', () => {
        if (!isOpen) return;
        clearPoll();
        showError('Bạn đang ngoại tuyến. Tin nhắn chưa gửi sẽ được giữ lại.', () => refreshMessages({ initial: true }));
    });

    window.addEventListener('pagehide', () => {
        clearPoll();
        window.clearTimeout(readTimer);
        Object.values(requestControllers).forEach((controller) => controller?.abort());
    });

    const openFromSupportUrl = () => {
        const url = new URL(window.location.href);
        const requestedByQuery = url.searchParams.get('support') === 'chat';
        const requestedByHash = url.hash === '#support-chat';
        if (!requestedByQuery && !requestedByHash) return;

        openChat();
        if (requestedByQuery) url.searchParams.delete('support');
        if (requestedByHash) url.hash = '';
        window.history.replaceState(window.history.state, '', `${url.pathname}${url.search}${url.hash}`);
    };

    window.addEventListener('hashchange', openFromSupportUrl);

    updateConversationStatus(null, 'bot');
    updateControls();
    openFromSupportUrl();
});
