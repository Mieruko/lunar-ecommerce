<div
    class="support-chat"
    data-support-chat
    data-chat-url="{{ url('/support/chat') }}"
    data-message-url="{{ url('/support/chat/messages') }}"
    data-handoff-url="{{ url('/support/chat/handoff') }}"
    data-read-url="{{ url('/support/chat/read') }}"
>
    <button
        class="support-chat-launcher"
        type="button"
        data-chat-toggle
        aria-expanded="false"
        aria-controls="lunar-support-dialog"
    >
        <span class="support-chat-launcher-icon" aria-hidden="true">
            <svg viewBox="0 0 24 24" focusable="false">
                <path d="M5.2 5.5h13.6v9.7H11l-4.6 3.3 1.1-3.3H5.2V5.5Z"></path>
                <path d="M8.4 9.2h7.2M8.4 12h4.8"></path>
            </svg>
        </span>
        <span class="support-chat-launcher-copy">
            <small>Concierge</small>
            <strong>Hỗ trợ</strong>
        </span>
        <span class="support-chat-badge" data-chat-badge hidden aria-label="0 tin nhắn chưa đọc">0</span>
    </button>

    <section
        id="lunar-support-dialog"
        class="support-chat-panel"
        data-chat-panel
        role="dialog"
        aria-labelledby="lunar-support-title"
        aria-describedby="lunar-support-privacy"
        aria-modal="false"
        aria-hidden="true"
        tabindex="-1"
        hidden
    >
        <header class="support-chat-header">
            <div class="support-chat-brand" aria-hidden="true">
                <span>L</span>
            </div>
            <div class="support-chat-heading">
                <span class="support-chat-kicker">Lunar concierge</span>
                <h2 id="lunar-support-title">Chúng tôi có thể giúp gì?</h2>
                <p class="support-chat-status" data-chat-status data-status="bot">
                    <span aria-hidden="true"></span>
                    <b>Trợ lý tự động</b>
                </p>
            </div>
            <button class="support-chat-close" type="button" data-chat-close aria-label="Đóng cửa sổ hỗ trợ">
                <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false">
                    <path d="m7 7 10 10M17 7 7 17"></path>
                </svg>
            </button>
        </header>

        <div class="support-chat-body">
            <p id="lunar-support-privacy" class="support-chat-privacy">
                Tin nhắn được lưu để hỗ trợ yêu cầu của bạn. Không gửi mật khẩu hoặc thông tin thẻ thanh toán.
            </p>

            <div class="support-chat-error" data-chat-error role="alert" hidden>
                <span data-chat-error-text>Không thể kết nối dịch vụ hỗ trợ.</span>
                <button type="button" data-chat-retry>Thử lại</button>
            </div>

            <div
                class="support-chat-messages"
                data-chat-messages
                role="log"
                aria-live="polite"
                aria-relevant="additions text"
                aria-busy="false"
            >
                <div class="support-chat-loading" data-chat-loading>
                    <span aria-hidden="true"></span>
                    <p>Đang kết nối với Lunar Concierge…</p>
                </div>
                <div class="support-chat-empty" data-chat-empty hidden>
                    <span class="support-chat-empty-mark" aria-hidden="true">✦</span>
                    <strong>Chào mừng bạn đến LUNAR JEWELS.</strong>
                    <p>Chọn một chủ đề bên dưới hoặc gửi câu hỏi để bắt đầu.</p>
                </div>
            </div>

            <div class="support-chat-suggestions" data-chat-suggestions hidden aria-label="Gợi ý hỗ trợ"></div>
        </div>

        <form class="support-chat-composer" data-chat-form novalidate>
            <label class="sr-only" for="lunar-support-message">Nhập tin nhắn hỗ trợ</label>
            <div class="support-chat-compose-row">
                <textarea
                    id="lunar-support-message"
                    data-chat-input
                    rows="1"
                    maxlength="2000"
                    placeholder="Nhập câu hỏi của bạn…"
                    autocomplete="off"
                ></textarea>
                <button class="support-chat-send" type="submit" data-chat-send aria-label="Gửi tin nhắn" disabled>
                    <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false">
                        <path d="m4.5 5 15 7-15 7 2.3-7L4.5 5Z"></path>
                        <path d="M7 12h12"></path>
                    </svg>
                </button>
            </div>
            <div class="support-chat-actions">
                <button type="button" data-chat-handoff>
                    <span aria-hidden="true"></span>
                    Gặp chuyên viên
                </button>
                <small data-chat-composer-note>Tự động trả lời · Có thể chuyển nhân viên bất cứ lúc nào</small>
            </div>
        </form>

        <p class="sr-only" data-chat-announcer aria-live="assertive" aria-atomic="true"></p>
    </section>
</div>
