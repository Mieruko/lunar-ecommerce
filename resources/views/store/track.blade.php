@extends('layouts.store', ['title' => 'Theo dõi đơn hàng | LUNAR JEWELS'])

@section('content')
@php
    $statusLabels = [
        'pending_confirmation' => 'Chờ xác nhận', 'confirmed' => 'Đã xác nhận', 'preparing' => 'Đang chuẩn bị',
        'shipping' => 'Đang giao', 'completed' => 'Hoàn tất', 'cancelled' => 'Đã hủy', 'returned' => 'Đã trả hàng',
    ];
    $paymentLabels = ['unpaid' => 'Chưa thanh toán', 'pending' => 'Đang xử lý', 'paid' => 'Đã thanh toán', 'partially_refunded' => 'Hoàn một phần', 'refunded' => 'Đã hoàn tiền', 'failed' => 'Thanh toán lỗi'];
@endphp

<section class="tracking-page">
    <div class="shell tracking-grid">
        <div class="tracking-intro">
            <span class="eyebrow eyebrow-light">Guest order tracking</span>
            <h1 class="display">FOLLOW<br><em>the journey.</em></h1>
            <p>Nhập mã đơn và số điện thoại đã sử dụng khi thanh toán để xem hành trình của đơn hàng.</p>
            <div class="tracking-orbit" aria-hidden="true"><span></span></div>
        </div>

        <div class="tracking-panel">
            <div class="tracking-panel-head"><span>01</span><div><span class="eyebrow">Find your order</span><h2>Theo dõi đơn hàng</h2></div></div>
            <form class="tracking-form" method="POST" action="{{ route('tracking.search') }}">
                @csrf
                <label class="field"><span>Mã đơn hàng</span><input name="order_number" value="{{ old('order_number') }}" placeholder="VD: LJ-260814-ABC123" required></label>
                <label class="field"><span>Số điện thoại</span><input name="phone" value="{{ old('phone') }}" autocomplete="tel" required></label>
                <button class="button" type="submit">Tra cứu đơn hàng <span>→</span></button>
            </form>

            @isset($order)
                <div class="tracking-result">
                    <div class="tracking-result-head"><span class="eyebrow">Order found</span><h3>{{ $order->order_number }}</h3></div>
                    <div class="tracking-result-grid">
                        <div><span>Trạng thái</span><b class="status-pill status-{{ $order->status }}">{{ $statusLabels[$order->status] ?? str_replace('_', ' ', $order->status) }}</b></div>
                        <div><span>Thanh toán</span><b class="status-pill {{ $order->payment_status }}">{{ $paymentLabels[$order->payment_status] ?? $order->payment_status }}</b></div>
                        <div><span>Tổng đơn</span><b><x-money :amount="$order->total_amount" /></b></div>
                    </div>
                    @foreach($order->shipments as $shipment)
                        <div class="tracking-shipment"><span>Vận chuyển</span><p><b>{{ $shipment->carrier ?: 'Đang chuẩn bị' }}</b><small>{{ $shipment->tracking_number ?: 'Chưa có mã vận đơn' }}</small></p></div>
                    @endforeach
                </div>
            @endisset
        </div>
    </div>
</section>
@endsection
