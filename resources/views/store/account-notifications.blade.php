@extends('layouts.store', ['title' => 'Thông báo | LUNAR JEWELS'])

@section('content')
<section class="account-hero">
    <div class="shell account-hero-inner">
        <div><span class="eyebrow eyebrow-light">Client updates</span><h1 class="display section-title">THÔNG BÁO</h1></div>
        <p>Theo dõi đơn hàng, thanh toán và hậu mãi tại một nơi.</p>
    </div>
</section>
<section class="page">
    <div class="shell account-layout lunar-account-layout">
        @include('store.partials.account-nav')
        <div class="account-content">
            <section class="account-section notifications-page-panel">
                <div class="account-section-head notification-page-head">
                    <span>03</span>
                    <div><span class="eyebrow">Notification centre</span><h2>Cập nhật dành cho bạn</h2></div>
                    @if(auth()->user()->unreadNotifications()->exists())
                        <form method="POST" action="{{ route('account.notifications.read-all') }}">@csrf<button class="button-outline" type="submit">Đánh dấu đã đọc</button></form>
                    @endif
                </div>
                <div class="notifications-page-list">
                    @forelse($notifications as $notification)
                        @include('store.partials.notification-row', ['notification' => $notification])
                    @empty
                        <div class="account-simple-empty"><span>○</span><h3>Chưa có thông báo</h3><p>Trạng thái đơn hàng, thanh toán và yêu cầu hậu mãi sẽ được cập nhật tại đây.</p></div>
                    @endforelse
                </div>
                <div class="pagination lunar-pagination">{{ $notifications->links() }}</div>
            </section>
        </div>
    </div>
</section>
@endsection
