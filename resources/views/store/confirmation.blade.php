@extends('layouts.store', ['title' => 'Xác nhận đơn hàng | LUNAR JEWELS'])

@section('content')
@php
    $statusLabels = [
        'pending_confirmation' => 'Chờ xác nhận', 'confirmed' => 'Đã xác nhận', 'preparing' => 'Đang chuẩn bị',
        'shipping' => 'Đang giao', 'completed' => 'Hoàn tất', 'cancelled' => 'Đã hủy', 'returned' => 'Đã trả hàng',
    ];
    $paymentLabels = ['unpaid' => 'Chưa thanh toán', 'pending' => 'Đang xử lý', 'paid' => 'Đã thanh toán', 'partially_refunded' => 'Hoàn một phần', 'refunded' => 'Đã hoàn tiền', 'failed' => 'Thanh toán lỗi'];
@endphp

<section class="confirmation-page">
    <div class="confirmation-orbit" aria-hidden="true"><span></span><i>✦</i></div>
    <div class="shell confirmation-shell" data-reveal-group>
        <span class="eyebrow eyebrow-light" data-reveal="fade">Order received</span>
        <h1 class="display" data-reveal>CẢM ƠN<br><em>bạn.</em></h1>
        <p class="confirmation-lead" data-reveal>Đơn hàng đã được tạo thành công. LUNAR JEWELS sẽ tiếp tục cập nhật hành trình của đơn qua thông tin liên hệ bạn đã cung cấp.</p>

        <div class="confirmation-card" data-reveal="scale">
            <div class="confirmation-order-number"><span>Mã đơn hàng</span><b>{{ $order->order_number }}</b></div>
            <div class="confirmation-statuses">
                <span class="status-pill {{ $order->payment_status }}">{{ $paymentLabels[$order->payment_status] ?? $order->payment_status }}</span>
                <span class="status-pill status-{{ $order->status }}">{{ $statusLabels[$order->status] ?? str_replace('_', ' ', $order->status) }}</span>
            </div>
            <div class="confirmation-total"><span>Tổng cộng</span><b><x-money :amount="$order->total_amount" /></b></div>
        </div>

        @if($bankTransfer)
            <section class="transfer-instructions" aria-labelledby="transfer-title" data-reveal>
                <div class="transfer-copy">
                    <span class="eyebrow eyebrow-light">Bank transfer</span>
                    <h2 id="transfer-title">Quét mã để chuyển khoản</h2>
                    <p>Đơn sẽ được xác nhận sau khi cửa hàng đối soát giao dịch. Vui lòng chuyển đúng số tiền và nội dung bên dưới.</p>
                    <dl>
                        <div><dt>Ngân hàng</dt><dd>{{ $bankTransfer['bank_name'] }}</dd></div>
                        <div><dt>Số tài khoản</dt><dd>{{ $bankTransfer['account_number'] }}</dd></div>
                        <div><dt>Nội dung</dt><dd>{{ $bankTransfer['transfer_reference'] }}</dd></div>
                        <div><dt>Số tiền</dt><dd><x-money :amount="$order->total_amount" /></dd></div>
                    </dl>
                </div>
                <img class="transfer-qr" src="{{ $bankTransfer['qr_url'] }}" alt="Mã VietQR thanh toán đơn {{ $order->order_number }}">
            </section>
        @endif

        <div class="confirmation-actions" data-reveal="fade">
            @auth
                <a class="button button-light" href="{{ route('account.orders.show', $order) }}">Xem tiến trình đơn <span>→</span></a>
            @else
                <a class="button button-light" href="{{ route('tracking.form') }}">Theo dõi đơn hàng <span>→</span></a>
            @endauth
            @guest<a class="text-link text-link-light" href="{{ route('register') }}">Tạo tài khoản để quản lý đơn</a>@endguest
            @auth<a class="text-link text-link-light" href="{{ route('account.orders') }}">Xem đơn hàng của tôi</a>@endauth
        </div>
    </div>
</section>
@endsection
