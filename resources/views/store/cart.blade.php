@extends('layouts.store', ['title' => 'Giỏ hàng | LUNAR JEWELS'])

@section('content')
<section class="subpage-hero">
    <div class="shell subpage-hero-inner">
        <div>
            <span class="eyebrow eyebrow-light">Your selection</span>
            <h1 class="display section-title">GIỎ HÀNG</h1>
        </div>
        <p>{{ $cart->items->count() }} {{ $cart->items->count() === 1 ? 'thiết kế' : 'thiết kế' }} đang chờ bạn hoàn tất lựa chọn.</p>
    </div>
</section>

<section class="page">
    <div class="shell">
        <div class="breadcrumb"><a href="{{ route('home') }}">Trang chủ</a><span>/</span>Giỏ hàng</div>

        @if($cart->items->isEmpty())
            <div class="lunar-empty lunar-empty-wide">
                <span class="empty-orbit" aria-hidden="true"></span>
                <span class="eyebrow">Your bag is empty</span>
                <h2>Chưa có món đồ nào trong giỏ.</h2>
                <p>Khám phá những thiết kế đồng hồ và trang sức được tuyển chọn cho dấu mốc tiếp theo của bạn.</p>
                <a class="button" href="{{ route('shop') }}">Khám phá bộ sưu tập <span>→</span></a>
            </div>
        @else
            <div class="cart-layout lunar-cart-layout">
                <section class="cart-items-panel">
                    <div class="panel-heading">
                        <span class="panel-index">01</span>
                        <div>
                            <span class="eyebrow">Selected pieces</span>
                            <h2>Sản phẩm của bạn</h2>
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
                                <a class="cart-product-image" href="{{ route('products.show', $product) }}" aria-label="Xem {{ $product->name }}">
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
                                    <p class="stock {{ $stock <= 2 ? 'low' : '' }}">{{ $stock > 0 ? ($stock <= 2 ? 'Sắp hết hàng' : 'Còn hàng') : 'Tạm hết hàng' }}</p>

                                    <div class="cart-mobile-price"><x-money :amount="$item->unit_price_amount * $item->quantity" /></div>

                                    <div class="cart-item-actions">
                                        <form action="{{ route('cart.update', $item) }}" method="POST" class="cart-quantity-form">
                                            @csrf
                                            @method('PATCH')
                                            <div class="quantity" data-quantity>
                                                <button type="button" data-minus aria-label="Giảm số lượng">−</button>
                                                <input name="quantity" value="{{ $item->quantity }}" inputmode="numeric" aria-label="Số lượng">
                                                <button type="button" data-plus aria-label="Tăng số lượng">+</button>
                                            </div>
                                            <button type="submit" class="cart-text-action">Cập nhật</button>
                                        </form>

                                        <form action="{{ route('cart.destroy', $item) }}" method="POST">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="cart-text-action danger-action">Xóa</button>
                                        </form>
                                    </div>
                                </div>

                                <div class="cart-line-price">
                                    <span>Thành tiền</span>
                                    <strong><x-money :amount="$item->unit_price_amount * $item->quantity" /></strong>
                                </div>
                            </article>
                        @endforeach
                    </div>

                    <a class="text-link" href="{{ route('shop') }}">← Tiếp tục mua sắm</a>
                </section>

                <aside class="order-summary lunar-summary">
                    <div class="summary-kicker"><span>02</span> Order summary</div>
                    <h2>Tóm tắt đơn hàng</h2>
                    <p class="summary-note">Phí vận chuyển cuối cùng được xác nhận ở bước thanh toán.</p>

                    @if($coupon)
                        <div class="coupon lunar-coupon coupon-applied">
                            <div><b>{{ $coupon['code'] }}</b><small>Đã áp dụng mã ưu đãi</small></div>
                            <form action="{{ route('cart.coupon.remove') }}" method="POST">@csrf @method('DELETE')<button class="button-outline">Gỡ mã</button></form>
                        </div>
                    @else
                        <form class="coupon lunar-coupon" action="{{ route('cart.coupon.apply') }}" method="POST">
                            @csrf
                            <input name="code" placeholder="Mã ưu đãi" maxlength="80" aria-label="Mã ưu đãi">
                            <button class="button-outline">Áp dụng</button>
                        </form>
                    @endif

                    <div class="summary-table">
                        <div class="summary-row"><span>Tạm tính</span><b><x-money :amount="$totals['subtotal']" /></b></div>
                        @if($totals['discount'])<div class="summary-row"><span>Giảm giá{{ $coupon ? ' ('.$coupon['code'].')' : '' }}</span><b>− <x-money :amount="$totals['discount']" /></b></div>@endif
                        <div class="summary-row"><span>Vận chuyển</span><b>@if($totals['shipping'])<x-money :amount="$totals['shipping']" />@else Miễn phí @endif</b></div>
                        <div class="summary-row total"><span>Tổng cộng</span><span><x-money :amount="$totals['total']" /></span></div>
                    </div>

                    <a class="button checkout-button" href="{{ route('checkout.shipping') }}">Tiến hành thanh toán <span>→</span></a>
                    <div class="summary-assurances">
                        <span>✦ Thanh toán bảo mật</span>
                        <span>✦ Chính sách đổi trả minh bạch</span>
                    </div>
                </aside>
            </div>
        @endif
    </div>
</section>
@endsection
