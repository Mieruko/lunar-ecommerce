@extends('layouts.store', ['title' => 'My Lunar | LUNAR JEWELS'])

@section('content')
@php
    $statusLabels = [
        'pending_confirmation' => 'Chờ xác nhận', 'confirmed' => 'Đã xác nhận', 'preparing' => 'Đang chuẩn bị',
        'shipping' => 'Đang giao', 'completed' => 'Hoàn tất', 'cancelled' => 'Đã hủy', 'returned' => 'Đã trả hàng',
    ];
@endphp
<section class="account-hero account-dashboard-hero">
    <div class="shell account-hero-inner">
        <div><span class="eyebrow eyebrow-light">Private client area</span><h1 class="display section-title">MY LUNAR</h1></div>
        <div class="account-identity"><span>Xin chào</span><b>{{ $user->name }}</b><small>Khách hàng từ {{ $user->created_at->format('m/Y') }}</small></div>
    </div>
</section>

<section class="page account-dashboard-page">
    <div class="shell client-summary-card">
        <div class="client-avatar">{{ mb_strtoupper(mb_substr($user->name, 0, 1)) }}</div>
        <div class="client-welcome"><span>Thành viên Lunar Jewels</span><b>{{ $user->name }}</b><small>{{ $user->email }}</small></div>
        <div class="client-stat"><span>Đơn hàng</span><b>{{ $stats['orders'] }}</b><small>Tổng số đơn đã tạo</small></div>
        <div class="client-stat"><span>Tổng chi tiêu</span><b><x-money :amount="$stats['spent']" /></b><small>Đã trừ khoản hoàn tiền</small></div>
        <div class="client-stat"><span>Bảo hành</span><b>{{ $stats['warranties'] }}</b><small>Phiếu đang hiệu lực</small></div>
    </div>

    <div class="shell account-layout lunar-account-layout account-dashboard-layout">
        @include('store.partials.account-nav')

        <div class="account-content dashboard-content">
            <nav class="account-quick-links" aria-label="Truy cập nhanh">
                <a href="{{ route('account.orders') }}"><span>↗</span><b>Lịch sử mua hàng</b><small>{{ $stats['orders'] }} đơn</small></a>
                <a href="{{ route('account.notifications') }}"><span>◎</span><b>{{ __('store.notifications.title') }}</b><small>{{ __('store.notifications.unread_count', ['count' => $stats['notifications']]) }}</small></a>
                <a href="{{ route('account.benefits') }}"><span>◇</span><b>Mã ưu đãi</b><small>{{ $coupons->count() }} đang hiển thị</small></a>
                <a href="{{ route('account.wishlist') }}"><span>♡</span><b>Yêu thích</b><small>{{ $stats['wishlist'] }} sản phẩm</small></a>
                <a href="{{ route('account.after-sales') }}"><span>⌁</span><b>Hậu mãi</b><small>Đổi trả & bảo hành</small></a>
            </nav>

            @if($activeOrder)
                <section class="dashboard-panel active-order-panel">
                    <div class="dashboard-panel-head">
                        <div><span class="eyebrow">Active order</span><h2>Đơn đang xử lý</h2></div>
                        <a href="{{ route('account.orders.show', $activeOrder) }}">Chi tiết <span>→</span></a>
                    </div>
                    <div class="active-order-heading">
                        <div><span>{{ $activeOrder->created_at->format('d/m/Y') }}</span><b>{{ $activeOrder->order_number }}</b></div>
                        <span class="status-pill status-{{ $activeOrder->status }}">{{ $statusLabels[$activeOrder->status] ?? $activeOrder->status }}</span>
                        <strong><x-money :amount="$activeOrder->total_amount" /></strong>
                    </div>
                    @include('store.partials.order-progress', ['order' => $activeOrder])
                </section>
            @endif

            <div class="dashboard-columns">
                <section class="dashboard-panel">
                    <div class="dashboard-panel-head">
                        <div><span class="eyebrow">Purchase history</span><h2>Đơn gần đây</h2></div>
                        <a href="{{ route('account.orders') }}">Xem tất cả <span>→</span></a>
                    </div>
                    <div class="dashboard-order-list">
                        @forelse($recentOrders as $order)
                            <a href="{{ route('account.orders.show', $order) }}">
                                <div><span>{{ $order->order_number }}</span><b>{{ $order->items->first()?->product_name ?: 'Đơn hàng Lunar Jewels' }}</b><small>{{ $order->created_at->format('d/m/Y') }} · {{ $order->items->sum('quantity') }} sản phẩm</small></div>
                                <div><span class="status-pill status-{{ $order->status }}">{{ $statusLabels[$order->status] ?? $order->status }}</span><strong><x-money :amount="$order->total_amount" /></strong></div>
                            </a>
                        @empty
                            <div class="dashboard-empty"><p>Bạn chưa có đơn hàng nào.</p><a class="text-link" href="{{ route('shop') }}">Khám phá bộ sưu tập</a></div>
                        @endforelse
                    </div>
                </section>

                <section class="dashboard-panel">
                    <div class="dashboard-panel-head">
                        <div><span class="eyebrow">Your privileges</span><h2>Ưu đãi của bạn</h2></div>
                        <a href="{{ route('account.benefits') }}">Xem tất cả <span>→</span></a>
                    </div>
                    <div class="dashboard-coupon-list">
                        @forelse($coupons as $coupon)
                            <article>
                                <div class="coupon-value">{{ $coupon->discount_type === 'percent' ? $coupon->discount_value.'%' : number_format($coupon->discount_value, 0, ',', '.').'đ' }}</div>
                                <div><b>{{ $coupon->name }}</b><code>{{ $coupon->code }}</code><small>@if($coupon->ends_at)Đến {{ $coupon->ends_at->format('d/m/Y') }}@else Không giới hạn thời gian @endif</small></div>
                            </article>
                        @empty
                            <div class="dashboard-empty coupon-empty"><span>◇</span><p>Hiện chưa có ưu đãi khả dụng.</p></div>
                        @endforelse
                    </div>
                </section>
            </div>

            <section class="dashboard-panel dashboard-notifications">
                <div class="dashboard-panel-head">
                    <div><span class="eyebrow">{{ __('store.notifications.latest') }}</span><h2>{{ __('store.notifications.title') }}</h2></div>
                    <a href="{{ route('account.notifications') }}">{{ __('store.notifications.view_all') }} <span>→</span></a>
                </div>
                <div>
                    @forelse($notifications as $notification)
                        @include('store.partials.notification-row', ['notification' => $notification])
                    @empty
                        <div class="dashboard-empty"><p>{{ __('store.notifications.latest_empty') }}</p></div>
                    @endforelse
                </div>
            </section>
        </div>
    </div>
</section>
@endsection
