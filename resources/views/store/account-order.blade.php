@extends('layouts.store', ['title' => $order->order_number.' | LUNAR JEWELS'])

@section('content')
@php
    $statusLabels = [
        'pending_confirmation' => 'Chờ xác nhận', 'confirmed' => 'Đã xác nhận', 'preparing' => 'Đang chuẩn bị',
        'shipping' => 'Đang giao', 'completed' => 'Hoàn tất', 'cancelled' => 'Đã hủy', 'returned' => 'Đã trả hàng',
    ];
    $paymentLabels = ['unpaid' => 'Chưa thanh toán', 'pending' => 'Đang xử lý', 'paid' => 'Đã thanh toán', 'partially_refunded' => 'Hoàn một phần', 'refunded' => 'Đã hoàn tiền', 'failed' => 'Thanh toán lỗi'];
@endphp
<section class="order-detail-hero">
    <div class="shell">
        <div class="breadcrumb breadcrumb-light"><a href="{{ route('account.orders') }}">Đơn hàng của tôi</a><span>/</span>{{ $order->order_number }}</div>
        <div class="order-detail-title-row">
            <div><span class="eyebrow eyebrow-light">Order detail</span><h1>{{ $order->order_number }}</h1></div>
            <div class="order-detail-meta"><span>Ngày đặt</span><b>{{ $order->created_at->format('d/m/Y H:i') }}</b></div>
        </div>
    </div>
</section>

<section class="page">
    <div class="shell order-detail-content">
        <section class="order-timeline-panel">
            <div class="dashboard-panel-head"><div><span class="eyebrow">Live progress</span><h2>Tiến trình đơn hàng</h2></div><span class="status-pill status-{{ $order->status }}">{{ $statusLabels[$order->status] ?? $order->status }}</span></div>
            @include('store.partials.order-progress', ['order' => $order])
        </section>

        <div class="order-detail-layout">
            <section class="order-items-panel">
                <div class="panel-heading"><span class="panel-index">01</span><div><span class="eyebrow">Selected pieces</span><h2>Sản phẩm</h2></div></div>
                <div class="order-item-lines">
                    @foreach($order->items as $item)
                        <article class="order-product-line">
                            <div class="order-item-image">@if($item->image_path)<img src="{{ $item->image_path }}" alt="{{ $item->product_name }}">@else<span>LJ</span>@endif</div>
                            <div><span class="order-item-sku">{{ $item->sku }}</span><h3>{{ $item->product_name }}</h3>@if($item->variant_name)<p>{{ $item->variant_name }}</p>@endif<small>Số lượng: {{ $item->quantity }}</small>@foreach($item->warranties as $warranty)<small class="order-item-sku">Bảo hành: <b>{{ $warranty->warranty_number }}</b> · đến {{ $warranty->ends_at->format('d/m/Y') }}</small>@endforeach</div>
                            <strong><x-money :amount="$item->total_amount" /></strong>
                        </article>
                    @endforeach
                </div>

                @if($order->statusHistory->isNotEmpty())
                    <div class="order-history-list">
                        <span class="eyebrow">Status history</span>
                        @foreach($order->statusHistory->sortByDesc('created_at') as $history)
                            <div><span></span><p><b>{{ $statusLabels[$history->status] ?? $history->status }}</b>@if($history->comment)<small>{{ $history->comment }}</small>@endif</p><time>{{ $history->created_at->format('d/m/Y H:i') }}</time></div>
                        @endforeach
                    </div>
                @endif
            </section>

            <aside class="order-status-panel">
                <div class="summary-kicker"><span>02</span> Status</div><h2>Thông tin đơn</h2>
                <div class="order-status-stack">
                    <div><span>Đơn hàng</span><b class="status-pill status-{{ $order->status }}">{{ $statusLabels[$order->status] ?? $order->status }}</b></div>
                    <div><span>Thanh toán</span><b class="status-pill {{ $order->payment_status }}">{{ $paymentLabels[$order->payment_status] ?? $order->payment_status }}</b></div>
                </div>
                @if($order->shipments->isNotEmpty())
                    <div class="shipment-block"><span class="eyebrow">Delivery</span>@foreach($order->shipments as $shipment)<div class="shipment-line"><span>{{ $shipment->carrier ?: 'Đơn vị vận chuyển' }}</span><b>{{ $shipment->tracking_number ?: 'Đang chuẩn bị' }}</b><small>{{ strtoupper($shipment->status) }}</small></div>@endforeach</div>
                @endif
                <div class="order-money-breakdown">
                    <div><span>Tạm tính</span><b><x-money :amount="$order->subtotal_amount" /></b></div>
                    @if($order->discount_amount > 0)<div><span>Giảm giá</span><b>−<x-money :amount="$order->discount_amount" /></b></div>@endif
                    <div><span>Vận chuyển</span><b>@if($order->shipping_amount)<x-money :amount="$order->shipping_amount" />@else Miễn phí @endif</b></div>
                    <div class="total"><span>Tổng cộng</span><b><x-money :amount="$order->total_amount" /></b></div>
                </div>
                <a class="button-outline order-track-button" href="{{ route('account.after-sales') }}">Đổi trả & bảo hành</a>
            </aside>
        </div>
    </div>
</section>
@endsection
