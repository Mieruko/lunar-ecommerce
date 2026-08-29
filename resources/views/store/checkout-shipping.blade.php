@extends('layouts.store', ['title' => 'Thông tin giao hàng | LUNAR JEWELS'])

@section('content')
<section class="checkout-hero">
    <div class="shell checkout-hero-inner">
        <a class="logo checkout-logo" href="{{ route('home') }}">
            <span class="logo-mark">LUNAR</span>
            <span class="logo-sub">JEWELS</span>
        </a>

        <div class="checkout-progress" aria-label="Tiến trình thanh toán">
            <span class="checkout-progress-item active"><b>01</b> Giao hàng</span>
            <span class="checkout-progress-line"></span>
            <span class="checkout-progress-item"><b>02</b> Thanh toán</span>
            <span class="checkout-progress-line"></span>
            <span class="checkout-progress-item"><b>03</b> Hoàn tất</span>
        </div>
    </div>
</section>

<section class="page checkout-page">
    <div class="shell checkout-layout lunar-checkout-layout">
        <form
            class="checkout-form-panel"
            method="POST"
            action="{{ route('checkout.shipping.store') }}"
            data-vn-address-form
            data-provinces-url="{{ route('checkout.locations.provinces') }}"
            data-wards-url="{{ route('checkout.locations.wards') }}"
            data-quote-url="{{ route('checkout.shipping.quote') }}"
            data-loading-form
        >
            @csrf

            <div class="checkout-section-head">
                <span class="eyebrow">Giao hàng tại Việt Nam</span>
                <h1>Thông tin giao hàng</h1>
                <p>
                    LUNAR JEWELS sử dụng danh mục hành chính Việt Nam 2 cấp:
                    Tỉnh/Thành phố → Phường/Xã/Đặc khu.
                </p>
            </div>

            <div class="form-grid lunar-form-grid">
                <label class="field">
                    <span>Họ và tên *</span>
                    <input
                        name="shipping[recipient_name]"
                        value="{{ old('shipping.recipient_name', $shipping['recipient_name'] ?? auth()->user()?->name) }}"
                        autocomplete="name"
                        required
                    >
                </label>

                <label class="field">
                    <span>Số điện thoại *</span>
                    <input
                        name="shipping[phone]"
                        value="{{ old('shipping.phone', $shipping['phone'] ?? auth()->user()?->phone) }}"
                        autocomplete="tel"
                        inputmode="tel"
                        required
                    >
                </label>

                <label class="field full">
                    <span>Email *</span>
                    <input
                        name="shipping[email]"
                        type="email"
                        value="{{ old('shipping.email', $shipping['email'] ?? auth()->user()?->email) }}"
                        autocomplete="email"
                        required
                    >
                </label>

                <div class="field full">
                    <span>Quốc gia / Khu vực</span>
                    <div class="checkout-country-fixed">
                        <span class="vn-flag" aria-hidden="true">VN</span>
                        <b>Việt Nam</b>
                        <small>Storefront hiện hỗ trợ giao hàng nội địa Việt Nam</small>
                    </div>
                </div>

                <label class="field">
                    <span>Tỉnh / Thành phố *</span>
                    <select
                        name="shipping[province_code]"
                        data-vn-province
                        data-selected="{{ old('shipping.province_code', $shipping['province_code'] ?? '') }}"
                        autocomplete="address-level1"
                        required
                    >
                        <option value="">Đang tải Tỉnh / Thành...</option>
                    </select>
                </label>

                <label class="field">
                    <span>Phường / Xã / Đặc khu *</span>
                    <select
                        name="shipping[ward_code]"
                        data-vn-ward
                        data-selected="{{ old('shipping.ward_code', $shipping['ward_code'] ?? '') }}"
                        disabled
                        required
                    >
                        <option value="">Chọn Tỉnh / Thành trước</option>
                    </select>
                </label>

                <label class="field full">
                    <span>Số nhà, tên đường *</span>
                    <input
                        name="shipping[line_1]"
                        value="{{ old('shipping.line_1', $shipping['line_1'] ?? '') }}"
                        placeholder="Ví dụ: 25 Nguyễn Huệ"
                        autocomplete="street-address"
                        required
                    >
                </label>

                <div class="field full">
                    <span>Phí giao hàng theo khu vực</span>

                    <div class="shipping-zone-quote" data-shipping-zone-quote>
                        <div>
                            <b data-shipping-zone-title>
                                {{ $shippingQuote['zone_name'] ?? 'Chọn địa chỉ để tính phí' }}
                            </b>

                            <small data-shipping-zone-message>
                                @if($shippingQuote)
                                    Phí được lấy từ khu vực giao hàng đã cấu hình trong hệ thống.
                                @else
                                    Chọn Tỉnh/Thành và Phường/Xã để hệ thống xác định khu vực giao hàng.
                                @endif
                            </small>
                        </div>

                        <strong data-shipping-zone-fee>
                            @if($shippingQuote)
                                @if($shippingQuote['shipping_fee'])
                                    <x-money :amount="$shippingQuote['shipping_fee']" />
                                @else
                                    Miễn phí
                                @endif
                            @else
                                —
                            @endif
                        </strong>
                    </div>
                </div>

                <label class="field full">
                    <span>Lời nhắn cho đơn hàng</span>
                    <textarea
                        name="shipping[note]"
                        rows="4"
                        placeholder="Ghi chú giao hàng, thời gian nhận hàng..."
                    >{{ old('shipping.note', $shipping['note'] ?? '') }}</textarea>
                </label>
            </div>

            @error('shipping.ward_code')
                <div class="error">{{ $message }}</div>
            @enderror

            <div class="checkout-form-actions">
                <a class="text-link" href="{{ route('cart.index') }}">← Quay lại giỏ hàng</a>

                <button class="button" type="submit">
                    Tiếp tục thanh toán <span>→</span>
                </button>
            </div>
        </form>

        <aside class="checkout-summary-panel">
            <div class="summary-kicker"><span>Order</span> Lunar Jewels</div>
            <h2>Đơn hàng</h2>

            <div class="checkout-products">
                @foreach($cart->items as $item)
                    @php
                        $product = $item->variant->product;
                        $image = $product->images->first()?->path;
                    @endphp

                    <div class="checkout-product-line">
                        <div class="checkout-product-thumb">
                            @if($image)
                                <img src="{{ $image }}" alt="{{ $product->name }}">
                            @else
                                <span>LJ</span>
                            @endif

                            <i>{{ $item->quantity }}</i>
                        </div>

                        <div>
                            <b>{{ $product->name }}</b>

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

            <x-order-summary class="checkout-totals" :totals="$totals" :shipping-pending="! $shippingQuote" />

            <div class="checkout-trust">
                <span>01</span>
                <p>
                    <b>Tính phí ở backend</b>
                    <small>Trình duyệt không gửi số tiền vận chuyển. Server tự xác định zone và tính lại trước khi tạo đơn.</small>
                </p>
            </div>
        </aside>
    </div>
</section>
@endsection
