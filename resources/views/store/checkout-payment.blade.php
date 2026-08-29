@extends('layouts.store', ['title' => 'Thanh toán | LUNAR JEWELS'])

@section('content')
<section class="checkout-hero">
    <div class="shell checkout-hero-inner">
        <a class="logo checkout-logo" href="{{ route('home') }}">
            <span class="logo-mark">LUNAR</span>
            <span class="logo-sub">JEWELS</span>
        </a>

        <div class="checkout-progress" aria-label="Tiến trình thanh toán">
            <a class="checkout-progress-item complete" href="{{ route('checkout.shipping') }}"><b>01</b> Giao hàng</a>
            <span class="checkout-progress-line complete"></span>
            <span class="checkout-progress-item active"><b>02</b> Thanh toán</span>
            <span class="checkout-progress-line"></span>
            <span class="checkout-progress-item"><b>03</b> Hoàn tất</span>
        </div>
    </div>
</section>

<section class="page checkout-page">
    <div class="shell checkout-layout lunar-checkout-layout">
        <form class="checkout-form-panel" method="POST" action="{{ route('checkout.place') }}" data-loading-form>
            @csrf
            <input type="hidden" name="shipping_method" value="standard">

            <div class="checkout-section-head">
                <span class="eyebrow">Secure checkout</span>
                <h1>Giao hàng & thanh toán</h1>
                <p>
                    Địa chỉ và phí giao hàng đã được xác nhận trong hệ thống.
                    Chọn phương thức thanh toán để hoàn tất đơn.
                </p>
            </div>

            <div class="checkout-block">
                <div class="checkout-block-title">
                    <span>01</span>
                    <h2>Khu vực giao hàng</h2>
                </div>

                <div class="shipping-method-confirmed">
                    <div class="shipping-method-mark">VN</div>

                    <div>
                        <b>{{ $shippingQuote['zone_name'] }}</b>
                        <small>{{ $shipping['ward'] }}, {{ $shipping['province'] }}</small>
                    </div>

                    <strong>
                        @if($totals['shipping'])
                            <x-money :amount="$totals['shipping']" />
                        @else
                            Miễn phí
                        @endif
                    </strong>
                </div>
            </div>

            <div class="checkout-block">
                <div class="checkout-block-title">
                    <span>02</span>
                    <h2>Phương thức thanh toán</h2>
                </div>

                <div class="payment-options lunar-payment-options">
                    @if($paymentMethods['bank_transfer'])
                        <label class="payment-option lunar-payment-option">
                            <input type="radio" name="payment_method" value="bank_transfer" checked>
                            <span class="payment-radio"></span>
                            <span class="payment-copy">
                                <b>Chuyển khoản ngân hàng</b>
                                <small>Quét VietQR sau khi đặt hàng; đơn được xử lý khi cửa hàng xác nhận đã nhận tiền.</small>
                            </span>
                            <span class="payment-code">VIETQR</span>
                        </label>
                    @endif

                    @if($paymentMethods['vnpay_debit'])
                    <label class="payment-option lunar-payment-option">
                        <input type="radio" name="payment_method" value="vnpay_debit" @checked(! $paymentMethods['bank_transfer'])>
                        <span class="payment-radio"></span>
                        <span class="payment-copy">
                            <b>Thẻ ghi nợ / ATM</b>
                            <small>Thanh toán bảo mật qua VNPAY</small>
                        </span>
                        <span class="payment-code">VNPAY</span>
                    </label>

                    <label class="payment-option lunar-payment-option">
                        <input type="radio" name="payment_method" value="vnpay_credit">
                        <span class="payment-radio"></span>
                        <span class="payment-copy">
                            <b>Thẻ tín dụng</b>
                            <small>Visa / Mastercard thông qua VNPAY</small>
                        </span>
                        <span class="payment-code">CARD</span>
                    </label>

                    <label class="payment-option lunar-payment-option">
                        <input type="radio" name="payment_method" value="vnpay_qr">
                        <span class="payment-radio"></span>
                        <span class="payment-copy">
                            <b>QR Code VNPAY</b>
                            <small>Quét mã trên cổng thanh toán bảo mật</small>
                        </span>
                        <span class="payment-code">QR</span>
                    </label>
                    @endif

                    @if($paymentMethods['paypal'])
                    <label class="payment-option lunar-payment-option">
                        <input type="radio" name="payment_method" value="paypal">
                        <span class="payment-radio"></span>
                        <span class="payment-copy">
                            <b>PayPal</b>
                            <small>Tương đương ${{ number_format($paypalUsd, 2) }} USD theo tỷ giá tại thời điểm đặt hàng</small>
                        </span>
                        <span class="payment-code">PAYPAL</span>
                    </label>
                    @endif

                    <label class="payment-option lunar-payment-option">
                        <input type="radio" name="payment_method" value="cod" @checked(! $paymentMethods['bank_transfer'] && ! $paymentMethods['vnpay_debit'] && ! $paymentMethods['paypal'])>
                        <span class="payment-radio"></span>
                        <span class="payment-copy">
                            <b>Thanh toán khi nhận hàng</b>
                            <small>Đơn hàng được xác nhận trước khi giao</small>
                        </span>
                        <span class="payment-code">COD</span>
                    </label>
                </div>
            </div>

            <div class="checkout-form-actions">
                <a class="text-link" href="{{ route('checkout.shipping') }}">← Sửa thông tin giao hàng</a>

                <button class="button" type="submit">
                    Đặt hàng an toàn <span>→</span>
                </button>
            </div>
        </form>

        <aside class="checkout-summary-panel">
            <div class="summary-kicker"><span>Deliver to</span> Việt Nam</div>
            <h2>Tóm tắt</h2>

            <div class="shipping-preview">
                <span class="shipping-preview-index">01</span>

                <p>
                    <b>{{ $shipping['recipient_name'] }}</b>
                    <small>{{ $shipping['phone'] }}</small>
                    <small>
                        {{ $shipping['line_1'] }},
                        {{ $shipping['ward'] }},
                        {{ $shipping['province'] }}
                    </small>
                </p>

                <a href="{{ route('checkout.shipping') }}">Sửa</a>
            </div>

            <div class="checkout-products compact-products">
                @foreach($cart->items as $item)
                    <div class="checkout-product-line no-thumb">
                        <div>
                            <b>{{ $item->quantity }} × {{ $item->variant->product->name }}</b>

                            @if($item->variant->name)
                                <small>{{ $item->variant->name }}</small>
                            @endif
                        </div>

                        <strong>
                            <x-money :amount="$item->unit_price_amount * $item->quantity" />
                        </strong>
                    </div>
                @endforeach
            </div>

            <x-order-summary class="checkout-totals" :totals="$totals" :coupon="$coupon" />

            <div class="secure-note">
                <span>✦</span>
                <p>
                    <b>Phí được khóa theo khu vực</b>
                    <small>Server xác nhận lại zone và phí vận chuyển ngay trước khi tạo đơn hàng.</small>
                </p>
            </div>
        </aside>
    </div>
</section>
@endsection
