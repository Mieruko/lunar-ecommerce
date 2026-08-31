@extends('layouts.store', ['title' => __('store.checkout.payment_page_title').' | LUNAR JEWELS'])

@section('content')
<section class="checkout-hero">
    <div class="shell checkout-hero-inner">
        <a class="logo checkout-logo" href="{{ route('home') }}">
            <span class="logo-mark">LUNAR</span>
            <span class="logo-sub">JEWELS</span>
        </a>

        <div class="checkout-progress" aria-label="{{ __('store.checkout.progress_aria') }}">
            <a class="checkout-progress-item complete" href="{{ route('checkout.shipping') }}"><b>01</b> {{ __('store.checkout.shipping_step') }}</a>
            <span class="checkout-progress-line complete"></span>
            <span class="checkout-progress-item active"><b>02</b> {{ __('store.checkout.payment_step') }}</span>
            <span class="checkout-progress-line"></span>
            <span class="checkout-progress-item"><b>03</b> {{ __('store.checkout.complete_step') }}</span>
        </div>
    </div>
</section>

<section class="page checkout-page">
    <div class="shell checkout-layout lunar-checkout-layout">
        <form class="checkout-form-panel" method="POST" action="{{ route('checkout.place') }}" data-loading-form>
            @csrf
            <input type="hidden" name="shipping_method" value="standard">

            <div class="checkout-section-head">
                <span class="eyebrow">{{ __('store.checkout.secure_kicker') }}</span>
                <h1>{{ __('store.checkout.payment_heading') }}</h1>
                <p>{{ __('store.checkout.payment_intro') }}</p>
            </div>

            <div class="checkout-block">
                <div class="checkout-block-title">
                    <span>01</span>
                    <h2>{{ __('store.checkout.shipping_zone') }}</h2>
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
                            {{ __('store.order_summary.free') }}
                        @endif
                    </strong>
                </div>
            </div>

            <div class="checkout-block">
                <div class="checkout-block-title">
                    <span>02</span>
                    <h2>{{ __('store.checkout.payment_method') }}</h2>
                </div>

                <div class="payment-options lunar-payment-options">
                    @if($paymentMethods['bank_transfer'])
                        <label class="payment-option lunar-payment-option">
                            <input type="radio" name="payment_method" value="bank_transfer" checked>
                            <span class="payment-radio"></span>
                            <span class="payment-copy">
                                <b>{{ __('store.checkout.bank_transfer') }}</b>
                                <small>{{ __('store.checkout.bank_transfer_copy') }}</small>
                            </span>
                            <span class="payment-code">VIETQR</span>
                        </label>
                    @endif

                    @if($paymentMethods['vnpay_debit'])
                    <label class="payment-option lunar-payment-option">
                        <input type="radio" name="payment_method" value="vnpay_debit" @checked(! $paymentMethods['bank_transfer'])>
                        <span class="payment-radio"></span>
                        <span class="payment-copy">
                            <b>{{ __('store.checkout.debit_card') }}</b>
                            <small>{{ __('store.checkout.debit_card_copy') }}</small>
                        </span>
                        <span class="payment-code">VNPAY</span>
                    </label>

                    <label class="payment-option lunar-payment-option">
                        <input type="radio" name="payment_method" value="vnpay_credit">
                        <span class="payment-radio"></span>
                        <span class="payment-copy">
                            <b>{{ __('store.checkout.credit_card') }}</b>
                            <small>{{ __('store.checkout.credit_card_copy') }}</small>
                        </span>
                        <span class="payment-code">CARD</span>
                    </label>

                    <label class="payment-option lunar-payment-option">
                        <input type="radio" name="payment_method" value="vnpay_qr">
                        <span class="payment-radio"></span>
                        <span class="payment-copy">
                            <b>QR Code VNPAY</b>
                            <small>{{ __('store.checkout.vnpay_qr_copy') }}</small>
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
                            <small>{{ __('store.checkout.paypal_copy', ['amount' => number_format($paypalUsd, 2)]) }}</small>
                        </span>
                        <span class="payment-code">PAYPAL</span>
                    </label>
                    @endif

                    <label class="payment-option lunar-payment-option">
                        <input type="radio" name="payment_method" value="cod" @checked(! $paymentMethods['bank_transfer'] && ! $paymentMethods['vnpay_debit'] && ! $paymentMethods['paypal'])>
                        <span class="payment-radio"></span>
                        <span class="payment-copy">
                            <b>{{ __('store.checkout.cod') }}</b>
                            <small>{{ __('store.checkout.cod_copy') }}</small>
                        </span>
                        <span class="payment-code">COD</span>
                    </label>
                </div>
            </div>

            <div class="checkout-form-actions">
                <a class="text-link" href="{{ route('checkout.shipping') }}">← {{ __('store.checkout.edit_shipping') }}</a>

                <button class="button" type="submit">
                    {{ __('store.checkout.place_order') }} <span>→</span>
                </button>
            </div>
        </form>

        <aside class="checkout-summary-panel">
            <div class="summary-kicker"><span>{{ __('store.checkout.deliver_to') }}</span> {{ __('store.checkout.country_name') }}</div>
            <h2>{{ __('store.checkout.summary') }}</h2>

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

                <a href="{{ route('checkout.shipping') }}">{{ __('store.checkout.edit') }}</a>
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
                    <b>{{ __('store.checkout.zone_locked') }}</b>
                    <small>{{ __('store.checkout.zone_locked_copy') }}</small>
                </p>
            </div>
        </aside>
    </div>
</section>
@endsection
