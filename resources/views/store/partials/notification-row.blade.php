@php
    $data = $notification->data;
    $context = $data['context'] ?? [];
    $translationKey = $data['translation_key'] ?? null;
    $translationParams = $data['translation_params'] ?? [];

    // Notifications created before locale support stored Vietnamese copy only.
    // Map those known titles to the new locale-aware message catalogue.
    $legacyKeys = [
        'Đơn hàng đang chờ xác nhận' => 'notifications.content.order.pending_confirmation',
        'Đơn hàng đã được xác nhận' => 'notifications.content.order.confirmed',
        'Đơn hàng đang được chuẩn bị' => 'notifications.content.order.preparing',
        'Đơn hàng đang trên đường giao' => 'notifications.content.order.shipping',
        'Đơn hàng đã giao thành công' => 'notifications.content.order.completed',
        'Đơn hàng đã bị hủy' => 'notifications.content.order.cancelled',
        'Đơn hàng đã được trả lại' => 'notifications.content.order.returned',
        'Đã tiếp nhận đơn hàng' => 'notifications.content.order.generic',
        'Trạng thái đơn hàng đã thay đổi' => 'notifications.content.order.generic',
        'Đơn hàng chưa thanh toán' => 'notifications.content.payment.unpaid',
        'Thanh toán đang được xử lý' => 'notifications.content.payment.pending',
        'Thanh toán thành công' => 'notifications.content.payment.paid',
        'Đã hoàn một phần tiền' => 'notifications.content.payment.partially_refunded',
        'Đã hoàn tiền' => 'notifications.content.payment.refunded',
        'Thanh toán chưa thành công' => 'notifications.content.payment.failed',
        'Thanh toán đã được cập nhật' => 'notifications.content.payment.generic',
        'Đã tiếp nhận yêu cầu đổi trả' => 'notifications.content.return.requested',
        'Yêu cầu đổi trả đã được chấp nhận' => 'notifications.content.return.approved',
        'Yêu cầu đổi trả chưa được chấp nhận' => 'notifications.content.return.rejected',
        'Lunar Jewels đã nhận sản phẩm đổi trả' => 'notifications.content.return.received',
        'Yêu cầu đổi trả đã được hoàn tiền' => 'notifications.content.return.refunded',
        'Yêu cầu đổi trả đã đóng' => 'notifications.content.return.closed',
        'Yêu cầu đổi trả đã được cập nhật' => 'notifications.content.return.generic',
        'Đã tiếp nhận yêu cầu bảo hành' => 'notifications.content.warranty.submitted',
        'Yêu cầu bảo hành đã được tiếp nhận xử lý' => 'notifications.content.warranty.approved',
        'Sản phẩm đang được bảo hành' => 'notifications.content.warranty.in_repair',
        'Yêu cầu bảo hành đã hoàn tất' => 'notifications.content.warranty.resolved',
        'Yêu cầu bảo hành chưa được chấp nhận' => 'notifications.content.warranty.rejected',
        'Yêu cầu bảo hành đã được cập nhật' => 'notifications.content.warranty.generic',
        'Đơn hàng đã đóng gói' => 'notifications.content.shipment.packed',
        'Giao hàng chưa thành công' => 'notifications.content.shipment.failed',
        'Vận đơn đang hoàn về' => 'notifications.content.shipment.returned',
        'Vận đơn đã hủy' => 'notifications.content.shipment.cancelled',
        'Đã có mã vận đơn' => 'notifications.content.shipment.tracking_added',
        'LUNAR Care đã phản hồi' => 'notifications.content.support',
    ];

    $translationKey ??= $legacyKeys[$data['title'] ?? ''] ?? 'notifications.content.general';
    if (! isset($translationParams['order']) && isset($context['order_number'])) {
        $translationParams['order'] = $context['order_number'];
    }

    // Backfill identifiers for notifications created before params were stored.
    if (str_contains($translationKey, '.return.')) {
        preg_match('/Phiếu\s+([^·]+)\s+·\s+đơn\s+([^\.]+)/u', $data['message'] ?? '', $matches);
        $translationParams['return'] ??= trim($matches[1] ?? '—');
        $translationParams['order'] ??= trim($matches[2] ?? '—');
    } elseif (str_contains($translationKey, '.warranty.')) {
        preg_match('/Phiếu\s+([^·]+)\s+·\s+đơn\s+([^\.]+)/u', $data['message'] ?? '', $matches);
        $translationParams['claim'] ??= trim($matches[1] ?? '—');
        $translationParams['order'] ??= trim($matches[2] ?? '—');
        preg_match('/Kết quả:\s*(.+)$/u', $data['message'] ?? '', $resolution);
        $translationParams['resolution'] ??= trim($resolution[1] ?? '—');
    } elseif (str_contains($translationKey, '.shipment.tracking_added')) {
        preg_match('/^(.+?)\s+·\s+(.+?)\s+Mã đơn\s+([^\.]+)\.?$/u', $data['message'] ?? '', $matches);
        $translationParams['carrier'] ??= trim($matches[1] ?? '—');
        $translationParams['tracking'] ??= trim($matches[2] ?? '—');
        $translationParams['order'] ??= trim($matches[3] ?? '—');
    }

    $translationPath = 'store.'.$translationKey;
    $hasTranslation = \Illuminate\Support\Facades\Lang::has($translationPath.'.title') && \Illuminate\Support\Facades\Lang::has($translationPath.'.message');
    $title = $hasTranslation ? __($translationPath.'.title', $translationParams) : ($data['title'] ?? __('store.notifications.title'));
    $message = $hasTranslation ? __($translationPath.'.message', $translationParams) : ($data['message'] ?? '');
@endphp
<form class="customer-notification-row {{ $notification->read_at ? '' : 'is-unread' }}" method="POST" action="{{ route('account.notifications.read', $notification->id) }}">
    @csrf
    <button type="submit">
        <span class="notification-category notification-category-{{ $data['category'] ?? 'general' }}">{{ strtoupper(substr($data['category'] ?? 'LJ', 0, 2)) }}</span>
        <span class="notification-copy">
            <b>{{ $title }}</b>
            <span>{{ $message }}</span>
            <small>{{ $notification->created_at->diffForHumans() }}</small>
        </span>
        <span class="notification-arrow">→</span>
    </button>
</form>
