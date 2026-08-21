@extends('layouts.store', ['title' => 'Ưu đãi | LUNAR JEWELS'])

@section('content')
<section class="account-hero">
    <div class="shell account-hero-inner">
        <div><span class="eyebrow eyebrow-light">Private privileges</span><h1 class="display section-title">ƯU ĐÃI</h1></div>
        <p>Các mã đang áp dụng toàn bộ cửa hàng Lunar Jewels.</p>
    </div>
</section>
<section class="page">
    <div class="shell account-layout lunar-account-layout">
        @include('store.partials.account-nav')
        <div class="account-content">
            <section class="account-section">
                <div class="account-section-head"><span>04</span><div><span class="eyebrow">Available offers</span><h2>Mã ưu đãi khả dụng</h2></div></div>
                <div class="benefit-grid">
                    @forelse($coupons as $coupon)
                        @php($usedUp = $coupon->per_customer_limit !== null && $coupon->customer_usage_count >= $coupon->per_customer_limit)
                        <article @class(['benefit-card', 'is-used' => $usedUp])>
                            <div class="benefit-card-value"><span>Ưu đãi</span><b>{{ $coupon->discount_type === 'percent' ? $coupon->discount_value.'%' : number_format($coupon->discount_value, 0, ',', '.').'đ' }}</b></div>
                            <div class="benefit-card-copy">
                                <span class="eyebrow">Lunar privilege</span><h3>{{ $coupon->name }}</h3>
                                <p>Đơn tối thiểu <x-money :amount="$coupon->minimum_order_amount" />.</p>
                                <small>@if($coupon->ends_at)Hạn dùng {{ $coupon->ends_at->format('d/m/Y H:i') }}@else Không giới hạn thời gian @endif</small>
                            </div>
                            <button class="coupon-copy" type="button" data-copy-code="{{ $coupon->code }}" @disabled($usedUp)><code>{{ $coupon->code }}</code><span>{{ $usedUp ? 'Đã hết lượt' : 'Sao chép' }}</span></button>
                        </article>
                    @empty
                        <div class="account-simple-empty benefit-empty"><span>◇</span><h3>Chưa có ưu đãi</h3><p>Các mã giảm giá mới sẽ xuất hiện tại đây.</p></div>
                    @endforelse
                </div>
                <div class="pagination lunar-pagination">{{ $coupons->links() }}</div>
            </section>
        </div>
    </div>
</section>
@endsection
