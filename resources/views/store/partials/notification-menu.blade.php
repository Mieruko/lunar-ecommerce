@php
    $headerNotificationCount = auth()->user()->unreadNotifications()->count();
    $headerNotifications = auth()->user()->notifications()->limit(5)->get();
@endphp
<details class="notification-menu">
    <summary class="nav-icon notification-trigger" aria-label="Thông báo">
        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M6.5 9.5a5.5 5.5 0 0 1 11 0c0 6 2.5 6.5 2.5 6.5H4s2.5-.5 2.5-6.5Z"></path><path d="M10 19h4"></path></svg>
        <span>Thông báo</span>
        @if($headerNotificationCount)<b class="notification-count">{{ $headerNotificationCount > 99 ? '99+' : $headerNotificationCount }}</b>@endif
    </summary>
    <div class="notification-popover">
        <div class="notification-popover-head">
            <div><span class="eyebrow">Client updates</span><h3>Thông báo</h3></div>
            @if($headerNotificationCount)
                <form method="POST" action="{{ route('account.notifications.read-all') }}">@csrf<button type="submit">Đọc tất cả</button></form>
            @endif
        </div>
        <div class="notification-popover-list">
            @forelse($headerNotifications as $notification)
                @include('store.partials.notification-row', ['notification' => $notification])
            @empty
                <div class="notification-empty"><span>○</span><p>Bạn chưa có thông báo nào.</p></div>
            @endforelse
        </div>
        <a class="notification-view-all" href="{{ route('account.notifications') }}">Xem tất cả thông báo <span>→</span></a>
    </div>
</details>
