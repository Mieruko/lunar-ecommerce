@extends('layouts.store', ['title' => __('store.cart.page_title').' | LUNAR JEWELS'])

@section('content')
<section class="subpage-hero">
    <div class="shell subpage-hero-inner">
        <div>
            <span class="eyebrow eyebrow-light">Your selection</span>
            <h1 class="display section-title">{{ __('store.cart.heading') }}</h1>
        </div>
        <p>{{ __('store.cart.count', ['count' => $cart->items->count()]) }}</p>
    </div>
</section>

<section class="page">
    <div class="shell">
        <div class="breadcrumb"><a href="{{ route('home') }}">{{ __('store.common.home') }}</a><span>/</span>{{ __('store.cart.page_title') }}</div>

        @if($cart->items->isEmpty())
            <div class="lunar-empty lunar-empty-wide">
                <span class="empty-orbit" aria-hidden="true"></span>
                <span class="eyebrow">{{ __('store.cart.empty_kicker') }}</span>
                <h2>{{ __('store.cart.empty_title') }}</h2>
                <p>{{ __('store.cart.empty_copy') }}</p>
                <a class="button" href="{{ route('shop') }}">{{ __('store.cart.explore') }} <span>→</span></a>
            </div>
        @else
            <div class="cart-layout lunar-cart-layout">
                <section class="cart-items-panel">
                    <div class="panel-heading">
                        <span class="panel-index">01</span>
                        <div>
                            <span class="eyebrow">Selected pieces</span>
                            <h2>{{ __('store.cart.your_products') }}</h2>
                        </div>
                    </div>

                    <div class="cart-lines">
                        @foreach($cart->items as $item)
                            @php
                                $product = $item->variant->product;
                                $image = $product->images->first()?->path;
                                $stock = max(0, $item->variant->inventory->sum('quantity_on_hand') - $item->variant->inventory->sum('quantity_reserved'));
                            @endphp

                            <article class="lunar-cart-item">
                                <a class="cart-product-image" href="{{ route('products.show', $product) }}" aria-label="{{ __('store.product_card.view', ['product' => $product->name]) }}">
                                    @if($image)
                                        <img src="{{ $image }}" alt="{{ $product->name }}">
                                    @else
                                        <span>LJ</span>
                                    @endif
                                </a>

                                <div class="cart-product-copy">
                                    <span class="cart-brand">{{ $product->brand?->name ?? 'LUNAR JEWELS' }}</span>
                                    <a href="{{ route('products.show', $product) }}"><h3>{{ $product->name }}</h3></a>
                                    @if($item->variant->name)
                                        <p class="cart-variant">{{ $item->variant->name }}</p>
                                    @endif
                                    <p class="stock {{ $stock <= 2 ? 'low' : '' }}">{{ $stock > 0 ? ($stock <= 2 ? __('store.cart.low_stock') : __('store.cart.in_stock')) : __('store.cart.out_of_stock') }}</p>

                                    <div class="cart-mobile-price"><x-money :amount="$item->unit_price_amount * $item->quantity" /></div>

                                    <div class="cart-item-actions">
                                        <form action="{{ route('cart.update', $item) }}" method="POST" class="cart-quantity-form">
                                            @csrf
                                            @method('PATCH')
                                            <div class="quantity" data-quantity>
                                                <button type="button" data-minus aria-label="{{ __('store.cart.decrease') }}">−</button>
                                                <input name="quantity" value="{{ $item->quantity }}" inputmode="numeric" aria-label="{{ __('store.cart.quantity') }}">
                                                <button type="button" data-plus aria-label="{{ __('store.cart.increase') }}">+</button>
                                            </div>
                                            <button type="submit" class="cart-text-action">{{ __('store.cart.update') }}</button>
                                        </form>

                                        <form action="{{ route('cart.destroy', $item) }}" method="POST" data-remove-line>
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="cart-text-action danger-action">{{ __('store.cart.remove') }}</button>
                                        </form>
                                    </div>
                                </div>

                                <div class="cart-line-price">
                                    <span>{{ __('store.cart.line_total') }}</span>
                                    <strong><x-money :amount="$item->unit_price_amount * $item->quantity" /></strong>
                                </div>
                            </article>
                        @endforeach
                    </div>

                    <a class="text-link" href="{{ route('shop') }}">← {{ __('store.cart.continue') }}</a>
                </section>

                <aside class="order-summary lunar-summary">
                    <div class="summary-kicker"><span>02</span> Order summary</div>
                    <h2>{{ __('store.cart.summary') }}</h2>
                    <p class="summary-note">{{ __('store.cart.summary_note') }}</p>

                    @if($coupon)
                        <div class="coupon lunar-coupon coupon-applied">
                            <div>
                                <b>{{ $coupon['code'] }}</b>
                                <small>{{ __('store.cart.coupon_applied') }}</small>
                                @if($totals['discount'])
                                    <span class="coupon-saving">{{ __('store.cart.you_save') }} <x-money :amount="$totals['discount']" /></span>
                                @endif
                            </div>
                            <form action="{{ route('cart.coupon.remove') }}" method="POST" data-loading-form>@csrf @method('DELETE')<button class="button-outline">{{ __('store.cart.remove_coupon') }}</button></form>
                        </div>
                    @else
                        <form class="coupon lunar-coupon" action="{{ route('cart.coupon.apply') }}" method="POST" data-loading-form>
                            @csrf
                            <input name="code" value="{{ old('code') }}" placeholder="{{ __('store.cart.coupon') }}" maxlength="80" aria-label="{{ __('store.cart.coupon') }}">
                            <button class="button-outline">{{ __('store.cart.apply') }}</button>
                        </form>
                        @error('code')<p class="coupon-error-hint" role="alert">{{ $message }}</p>@enderror
                    @endif

                    <x-order-summary :totals="$totals" :coupon="$coupon" />

                    <a class="button checkout-button" href="{{ route('checkout.shipping') }}">{{ __('store.cart.checkout') }} <span>→</span></a>
                    <div class="summary-assurances">
                        <span>✦ {{ __('store.cart.secure_payment') }}</span>
                        <span>✦ {{ __('store.cart.transparent_returns') }}</span>
                    </div>
                </aside>
            </div>
        @endif
    </div>
</section>
@endsection
