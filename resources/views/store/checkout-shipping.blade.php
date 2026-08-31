@extends('layouts.store', ['title' => __('store.checkout.shipping_page_title').' | LUNAR JEWELS'])

@section('content')
<section class="checkout-hero">
    <div class="shell checkout-hero-inner">
        <a class="logo checkout-logo" href="{{ route('home') }}">
            <span class="logo-mark">LUNAR</span>
            <span class="logo-sub">JEWELS</span>
        </a>

        <div class="checkout-progress" aria-label="{{ __('store.checkout.progress_aria') }}">
            <span class="checkout-progress-item active"><b>01</b> {{ __('store.checkout.shipping_step') }}</span>
            <span class="checkout-progress-line"></span>
            <span class="checkout-progress-item"><b>02</b> {{ __('store.checkout.payment_step') }}</span>
            <span class="checkout-progress-line"></span>
            <span class="checkout-progress-item"><b>03</b> {{ __('store.checkout.complete_step') }}</span>
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
            data-i18n="{{ json_encode([
                'addressError' => __('store.checkout.address_error'),
                'selectAddress' => __('store.checkout.select_address'),
                'selectAddressHelp' => __('store.checkout.select_address_help'),
                'chooseAddress' => __('store.order_summary.choose_address'),
                'determiningZone' => __('store.checkout.determining_zone'),
                'calculatingShipping' => __('store.checkout.calculating_shipping'),
                'free' => __('store.order_summary.free'),
                'freeShippingThreshold' => __('store.checkout.free_shipping_threshold'),
                'configuredFee' => __('store.checkout.configured_fee'),
                'feeUnavailable' => __('store.checkout.fee_unavailable'),
                'undetermined' => __('store.checkout.undetermined'),
                'loadingWard' => __('store.checkout.loading_ward'),
                'selectProvinceFirst' => __('store.checkout.select_province_first'),
                'selectWard' => __('store.checkout.select_ward'),
                'wardLoadError' => __('store.checkout.ward_load_error'),
                'addressLoadError' => __('store.checkout.address_load_error'),
                'loadingProvince' => __('store.checkout.loading_province'),
                'selectProvince' => __('store.checkout.select_province'),
                'addressDataMissing' => __('store.checkout.address_data_missing'),
                'administrativeDataMissing' => __('store.checkout.administrative_data_missing'),
            ]) }}"
            data-loading-form
        >
            @csrf

            <div class="checkout-section-head">
                <span class="eyebrow">{{ __('store.checkout.shipping_kicker') }}</span>
                <h1>{{ __('store.checkout.shipping_heading') }}</h1>
                <p>{{ __('store.checkout.shipping_intro') }}</p>
            </div>

            <div class="form-grid lunar-form-grid">
                <label class="field">
                    <span>{{ __('store.checkout.full_name') }}</span>
                    <input
                        name="shipping[recipient_name]"
                        value="{{ old('shipping.recipient_name', $shipping['recipient_name'] ?? auth()->user()?->name) }}"
                        autocomplete="name"
                        required
                    >
                </label>

                <label class="field">
                    <span>{{ __('store.checkout.phone') }}</span>
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
                    <span>{{ __('store.checkout.country') }}</span>
                    <div class="checkout-country-fixed">
                        <span class="vn-flag" aria-hidden="true">VN</span>
                        <b>{{ __('store.checkout.country_name') }}</b>
                        <small>{{ __('store.checkout.domestic_only') }}</small>
                    </div>
                </div>

                <label class="field">
                    <span>{{ __('store.checkout.province') }}</span>
                    <select
                        name="shipping[province_code]"
                        data-vn-province
                        data-selected="{{ old('shipping.province_code', $shipping['province_code'] ?? '') }}"
                        autocomplete="address-level1"
                        required
                    >
                        <option value="">{{ __('store.checkout.loading_province') }}</option>
                    </select>
                </label>

                <label class="field">
                    <span>{{ __('store.checkout.ward') }}</span>
                    <select
                        name="shipping[ward_code]"
                        data-vn-ward
                        data-selected="{{ old('shipping.ward_code', $shipping['ward_code'] ?? '') }}"
                        disabled
                        required
                    >
                        <option value="">{{ __('store.checkout.select_province_first') }}</option>
                    </select>
                </label>

                <label class="field full">
                    <span>{{ __('store.checkout.street') }}</span>
                    <input
                        name="shipping[line_1]"
                        value="{{ old('shipping.line_1', $shipping['line_1'] ?? '') }}"
                        placeholder="{{ __('store.checkout.street_placeholder') }}"
                        autocomplete="street-address"
                        required
                    >
                </label>

                <div class="field full">
                    <span>{{ __('store.checkout.shipping_fee_by_zone') }}</span>

                    <div class="shipping-zone-quote" data-shipping-zone-quote>
                        <div>
                            <b data-shipping-zone-title>
                                {{ $shippingQuote['zone_name'] ?? __('store.checkout.select_address') }}
                            </b>

                            <small data-shipping-zone-message>
                                @if($shippingQuote)
                                    {{ __('store.checkout.configured_zone_fee') }}
                                @else
                                    {{ __('store.checkout.select_address_help') }}
                                @endif
                            </small>
                        </div>

                        <strong data-shipping-zone-fee>
                            @if($shippingQuote)
                                @if($shippingQuote['shipping_fee'])
                                    <x-money :amount="$shippingQuote['shipping_fee']" />
                                @else
                                    {{ __('store.order_summary.free') }}
                                @endif
                            @else
                                —
                            @endif
                        </strong>
                    </div>
                </div>

                <label class="field full">
                    <span>{{ __('store.checkout.order_note') }}</span>
                    <textarea
                        name="shipping[note]"
                        rows="4"
                        placeholder="{{ __('store.checkout.order_note_placeholder') }}"
                    >{{ old('shipping.note', $shipping['note'] ?? '') }}</textarea>
                </label>
            </div>

            @error('shipping.ward_code')
                <div class="error">{{ $message }}</div>
            @enderror

            <div class="checkout-form-actions">
                <a class="text-link" href="{{ route('cart.index') }}">← {{ __('store.checkout.back_to_cart') }}</a>

                <button class="button" type="submit">
                    {{ __('store.checkout.continue_payment') }} <span>→</span>
                </button>
            </div>
        </form>

        <aside class="checkout-summary-panel">
            <div class="summary-kicker"><span>{{ __('store.checkout.order') }}</span> Lunar Jewels</div>
            <h2>{{ __('store.checkout.order') }}</h2>

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
                    <b>{{ __('store.checkout.backend_fee_title') }}</b>
                    <small>{{ __('store.checkout.backend_fee_copy') }}</small>
                </p>
            </div>
        </aside>
    </div>
</section>
@endsection
