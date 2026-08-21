@php($data = $notification->data)
<form class="customer-notification-row {{ $notification->read_at ? '' : 'is-unread' }}" method="POST" action="{{ route('account.notifications.read', $notification->id) }}">
    @csrf
    <button type="submit">
        <span class="notification-category notification-category-{{ $data['category'] ?? 'general' }}">{{ strtoupper(substr($data['category'] ?? 'LJ', 0, 2)) }}</span>
        <span class="notification-copy">
            <b>{{ $data['title'] ?? 'Thông báo từ Lunar Jewels' }}</b>
            <span>{{ $data['message'] ?? '' }}</span>
            <small>{{ $notification->created_at->diffForHumans() }}</small>
        </span>
        <span class="notification-arrow">→</span>
    </button>
</form>
