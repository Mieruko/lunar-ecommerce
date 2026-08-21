@php
    $steps = [
        'pending_confirmation' => 'Tiếp nhận',
        'confirmed' => 'Xác nhận',
        'preparing' => 'Chuẩn bị',
        'shipping' => 'Đang giao',
        'completed' => 'Hoàn tất',
    ];
    $keys = array_keys($steps);
    $currentIndex = array_search($order->status, $keys, true);
    $historyByStatus = $order->statusHistory->keyBy('status');
@endphp

@if(in_array($order->status, ['cancelled', 'returned'], true))
    <div class="order-progress-alert status-{{ $order->status }}">
        {{ $order->status === 'cancelled' ? 'Đơn hàng đã bị hủy.' : 'Đơn hàng đã được trả lại.' }}
    </div>
@else
    <div class="order-progress" aria-label="Tiến trình đơn hàng">
        @foreach($steps as $status => $label)
            @php
                $stepIndex = array_search($status, $keys, true);
                $done = $currentIndex !== false && $stepIndex <= $currentIndex;
                $timestamp = $status === 'pending_confirmation'
                    ? $order->created_at
                    : $historyByStatus->get($status)?->created_at;
            @endphp
            <div @class(['order-progress-step', 'is-done' => $done, 'is-current' => $order->status === $status])>
                <span class="order-progress-dot">{{ str_pad((string)($loop->iteration), 2, '0', STR_PAD_LEFT) }}</span>
                <b>{{ $label }}</b>
                <small>{{ $timestamp?->format('d/m · H:i') ?: 'Đang chờ' }}</small>
            </div>
        @endforeach
    </div>
@endif
