<x-filament-panels::page>
    @php
        $conversation = $this->conversation();
        $orderUrl = $this->orderUrl();
        $statusLabel = \App\Filament\Resources\SupportConversations\SupportConversationResource::STATUSES[$conversation->status] ?? $conversation->status;
        $priorityLabel = \App\Filament\Resources\SupportConversations\SupportConversationResource::PRIORITIES[$conversation->priority] ?? $conversation->priority;
        $statusColor = \App\Filament\Resources\SupportConversations\SupportConversationResource::statusColor($conversation->status);
        $priorityColor = \App\Filament\Resources\SupportConversations\SupportConversationResource::priorityColor($conversation->priority);
    @endphp

    <style>
        .lj-support-layout {
            display: grid;
            grid-template-columns: minmax(0, 1fr) 19rem;
            gap: 1.25rem;
            align-items: start;
        }

        .lj-support-transcript {
            display: flex;
            min-height: 28rem;
            max-height: 65vh;
            flex-direction: column;
            gap: .85rem;
            overflow-y: auto;
            padding: .25rem;
            scroll-behavior: smooth;
        }

        .lj-support-message {
            width: min(82%, 44rem);
            border: 1px solid #e5e7eb;
            border-radius: 1rem;
            background: #fff;
            padding: .85rem 1rem;
            box-shadow: 0 1px 2px rgb(15 23 42 / 5%);
        }

        .lj-support-message--staff {
            align-self: flex-end;
            border-color: #bfdbfe;
            background: #eff6ff;
        }

        .lj-support-message--bot,
        .lj-support-message--system {
            align-self: center;
            width: min(92%, 48rem);
            border-style: dashed;
            background: #f8fafc;
        }

        .lj-support-message--internal {
            align-self: center;
            width: min(92%, 48rem);
            border-color: #fde68a;
            border-style: dashed;
            background: #fffbeb;
        }

        .lj-support-message__meta {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: .75rem;
            color: #64748b;
            font-size: .72rem;
            font-weight: 600;
        }

        .lj-support-message__body {
            margin-top: .45rem;
            color: #1f2937;
            font-size: .925rem;
            line-height: 1.6;
            overflow-wrap: anywhere;
            white-space: pre-wrap;
        }

        .lj-support-context {
            display: grid;
            gap: 1rem;
        }

        .lj-support-context-list {
            display: grid;
            gap: .85rem;
            margin: 0;
        }

        .lj-support-context-row dt {
            color: #64748b;
            font-size: .72rem;
            font-weight: 700;
            letter-spacing: .04em;
            text-transform: uppercase;
        }

        .lj-support-context-row dd {
            margin: .2rem 0 0;
            color: #111827;
            font-size: .9rem;
            overflow-wrap: anywhere;
        }

        .lj-support-empty {
            display: grid;
            min-height: 22rem;
            place-items: center;
            color: #64748b;
            text-align: center;
        }

        .lj-support-polling {
            margin-top: .75rem;
            color: #64748b;
            font-size: .75rem;
            text-align: center;
        }

        .dark .lj-support-message {
            border-color: #374151;
            background: #111827;
        }

        .dark .lj-support-message--staff {
            border-color: #1d4ed8;
            background: rgb(30 58 138 / 24%);
        }

        .dark .lj-support-message--bot,
        .dark .lj-support-message--system {
            background: #1f2937;
        }

        .dark .lj-support-message--internal {
            border-color: #a16207;
            background: rgb(113 63 18 / 28%);
        }

        .dark .lj-support-message__body,
        .dark .lj-support-context-row dd {
            color: #f3f4f6;
        }

        @media (max-width: 1024px) {
            .lj-support-layout {
                grid-template-columns: 1fr;
            }

            .lj-support-context {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        @media (max-width: 640px) {
            .lj-support-context {
                grid-template-columns: 1fr;
            }

            .lj-support-message,
            .lj-support-message--bot,
            .lj-support-message--system,
            .lj-support-message--internal {
                width: 100%;
            }
        }
    </style>

    <div class="lj-support-layout" wire:poll.5s="refreshConversation">
        <x-filament::section>
            <x-slot name="heading">
                <div style="display: flex; flex-wrap: wrap; align-items: center; gap: .6rem;">
                    <span>Trao đổi với khách hàng</span>
                    <x-filament::badge :color="$statusColor">{{ $statusLabel }}</x-filament::badge>
                    <x-filament::badge :color="$priorityColor">Ưu tiên: {{ $priorityLabel }}</x-filament::badge>
                </div>
            </x-slot>

            <div
                class="lj-support-transcript"
                role="log"
                aria-label="Lịch sử hội thoại hỗ trợ"
                aria-live="polite"
                x-data
                x-init="$el.scrollTop = $el.scrollHeight"
            >
                @forelse ($conversation->messages as $message)
                    @php
                        $isInternal = $message->kind === 'internal_note';
                        $messageClass = $isInternal
                            ? 'lj-support-message--internal'
                            : match ($message->sender_type) {
                                \App\Models\SupportMessage::SENDER_STAFF => 'lj-support-message--staff',
                                \App\Models\SupportMessage::SENDER_BOT => 'lj-support-message--bot',
                                \App\Models\SupportMessage::SENDER_SYSTEM => 'lj-support-message--system',
                                default => 'lj-support-message--customer',
                            };
                        $senderLabel = $isInternal
                            ? 'Ghi chú nội bộ · '.($message->sender?->name ?? 'Nhân viên')
                            : match ($message->sender_type) {
                                \App\Models\SupportMessage::SENDER_STAFF => $message->sender?->name ?? 'Nhân viên CSKH',
                                \App\Models\SupportMessage::SENDER_BOT => 'LUNAR Bot',
                                \App\Models\SupportMessage::SENDER_SYSTEM => 'Hệ thống',
                                default => $conversation->user?->name ?? 'Khách hàng',
                            };
                    @endphp

                    <article
                        class="lj-support-message {{ $messageClass }}"
                        wire:key="support-message-{{ $message->id }}"
                    >
                        <div class="lj-support-message__meta">
                            <span>{{ $senderLabel }}</span>
                            <time datetime="{{ $message->created_at?->toIso8601String() }}">
                                {{ $message->created_at?->format('d/m/Y H:i') }}
                            </time>
                        </div>
                        <div class="lj-support-message__body">{{ $message->body }}</div>
                    </article>
                @empty
                    <div class="lj-support-empty">
                        <div>
                            <strong>Chưa có tin nhắn</strong>
                            <p>Cuộc hội thoại chưa có nội dung để hiển thị.</p>
                        </div>
                    </div>
                @endforelse
            </div>

            <p class="lj-support-polling">
                Nội dung tự cập nhật mỗi 5 giây · Tin nhắn mới của khách được đánh dấu đã đọc khi mở trang này.
            </p>
        </x-filament::section>

        <aside class="lj-support-context" aria-label="Ngữ cảnh hội thoại">
            <x-filament::section heading="Khách hàng">
                <dl class="lj-support-context-list">
                    <div class="lj-support-context-row">
                        <dt>Họ tên</dt>
                        <dd>{{ $conversation->user?->name ?? 'Khách vãng lai' }}</dd>
                    </div>
                    <div class="lj-support-context-row">
                        <dt>Email</dt>
                        <dd>{{ $conversation->user?->email ?? 'Không có tài khoản' }}</dd>
                    </div>
                    <div class="lj-support-context-row">
                        <dt>Phụ trách</dt>
                        <dd>{{ $conversation->assignee?->name ?? 'Chưa có nhân viên nhận' }}</dd>
                    </div>
                </dl>
            </x-filament::section>

            <x-filament::section heading="Yêu cầu">
                <dl class="lj-support-context-list">
                    <div class="lj-support-context-row">
                        <dt>Mã hội thoại</dt>
                        <dd>#{{ strtoupper(substr($conversation->uuid, 0, 8)) }}</dd>
                    </div>
                    <div class="lj-support-context-row">
                        <dt>Danh mục</dt>
                        <dd>{{ $conversation->category ?: 'Chưa phân loại' }}</dd>
                    </div>
                    <div class="lj-support-context-row">
                        <dt>Chuyển nhân viên lúc</dt>
                        <dd>{{ $conversation->handed_off_at?->format('d/m/Y H:i') ?? '—' }}</dd>
                    </div>
                </dl>
            </x-filament::section>

            <x-filament::section heading="Đơn hàng liên quan">
                @if ($conversation->order)
                    <dl class="lj-support-context-list">
                        <div class="lj-support-context-row">
                            <dt>Mã đơn</dt>
                            <dd>
                                @if ($orderUrl)
                                    <a href="{{ $orderUrl }}" style="color: #2563eb; font-weight: 700;">
                                        {{ $conversation->order->order_number }}
                                    </a>
                                @else
                                    {{ $conversation->order->order_number }}
                                @endif
                            </dd>
                        </div>
                        <div class="lj-support-context-row">
                            <dt>Trạng thái</dt>
                            <dd>{{ $conversation->order->status }}</dd>
                        </div>
                        <div class="lj-support-context-row">
                            <dt>Tổng tiền</dt>
                            <dd>{{ number_format($conversation->order->total_amount, 0, ',', '.') }} ₫</dd>
                        </div>
                    </dl>
                @else
                    <p style="color: #64748b; font-size: .875rem;">
                        Hội thoại này chưa được gắn với đơn hàng.
                    </p>
                @endif
            </x-filament::section>
        </aside>
    </div>
</x-filament-panels::page>
