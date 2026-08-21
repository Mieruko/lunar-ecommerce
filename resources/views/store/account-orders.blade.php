@extends('layouts.store', ['title' => 'Đơn hàng | LUNAR JEWELS'])

@section('content')
@php
    $statusLabels = [
        'pending_confirmation' => 'Chờ xác nhận', 'confirmed' => 'Đã xác nhận', 'preparing' => 'Đang chuẩn bị',
        'shipping' => 'Đang giao', 'completed' => 'Hoàn tất', 'cancelled' => 'Đã hủy', 'returned' => 'Đã trả hàng',
    ];
    $paymentLabels = ['unpaid' => 'Chưa thanh toán', 'pending' => 'Đang xử lý', 'paid' => 'Đã thanh toán', 'partially_refunded' => 'Hoàn một phần', 'refunded' => 'Đã hoàn tiền', 'failed' => 'Thanh toán lỗi'];
@endphp
<section class="account-hero">
    <div class="shell account-hero-inner">
        <div><span class="eyebrow eyebrow-light">Purchase history</span><h1 class="display section-title">ĐƠN HÀNG</h1></div>
        <p>Xem tiến trình và chi tiết mọi đơn đã đặt bằng tài khoản này.</p>
    </div>
</section>

<section class="page">
    <div class="shell account-layout lunar-account-layout">
        @include('store.partials.account-nav')
        <div class="account-content">
            <section class="account-section orders-section">
                <div class="account-section-head"><span>02</span><div><span class="eyebrow">Purchase history</span><h2>Lịch sử đơn hàng</h2></div></div>
                <nav class="order-filter-tabs" aria-label="Lọc đơn hàng">
                    <a @class(['active' => !request('status')]) href="{{ route('account.orders') }}">Tất cả</a>
                    <a @class(['active' => request('status') === 'active']) href="{{ route('account.orders', ['status' => 'active']) }}">Đang xử lý</a>
                    <a @class(['active' => request('status') === 'completed']) href="{{ route('account.orders', ['status' => 'completed']) }}">Hoàn tất</a>
                    <a @class(['active' => request('status') === 'closed']) href="{{ route('account.orders', ['status' => 'closed']) }}">Đã đóng</a>
                </nav>
                <div class="orders-list">
                    @forelse($orders as $order)
                        <a class="lunar-order-row" href="{{ route('account.orders.show', $order) }}">
                            <div class="order-main"><span class="order-date">{{ $order->created_at->format('d · m · Y') }}</span><h3>{{ $order->order_number }}</h3><small>{{ $order->items->sum('quantity') }} sản phẩm</small></div>
                            <div class="order-statuses"><span class="status-pill status-{{ $order->status }}">{{ $statusLabels[$order->status] ?? $order->status }}</span><span class="status-pill {{ $order->payment_status }}">{{ $paymentLabels[$order->payment_status] ?? $order->payment_status }}</span></div>
                            <div class="order-total"><span>Tổng cộng</span><b><x-money :amount="$order->total_amount" /></b></div>
                            <span class="order-arrow">→</span>
                        </a>
                    @empty
                        <div class="lunar-empty account-orders-empty"><span class="empty-orbit" aria-hidden="true"></span><h2>Không có đơn phù hợp.</h2><p>Thử chọn bộ lọc khác hoặc khám phá bộ sưu tập Lunar Jewels.</p><a class="button" href="{{ route('shop') }}">Mua sắm</a></div>
                    @endforelse
                </div>
                <div class="pagination lunar-pagination">{{ $orders->links() }}</div>
            </section>
        </div>
    </div>
</section>
@endsection
