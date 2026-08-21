@extends('layouts.store', ['title' => 'Yêu thích | LUNAR JEWELS'])

@section('content')
<section class="account-hero">
    <div class="shell account-hero-inner">
        <div><span class="eyebrow eyebrow-light">Saved selection</span><h1 class="display section-title">YÊU THÍCH</h1></div>
        <p>Lưu lại những thiết kế bạn muốn khám phá sau.</p>
    </div>
</section>
<section class="page">
    <div class="shell account-layout lunar-account-layout">
        @include('store.partials.account-nav')
        <div class="account-content">
            <section class="account-section">
                <div class="account-section-head"><span>05</span><div><span class="eyebrow">Your selection</span><h2>Sản phẩm đã lưu</h2></div></div>
                @if($items->isNotEmpty())
                    <div class="wishlist-grid">
                        @foreach($items as $item)
                            <div class="wishlist-card-wrap">
                                <x-product-card :product="$item->product" />
                                <form method="POST" action="{{ route('account.wishlist.toggle', $item->product) }}">@csrf<button type="submit" class="wishlist-remove">Bỏ khỏi yêu thích</button></form>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="account-simple-empty"><span>♡</span><h3>Danh sách đang trống</h3><p>Lưu sản phẩm để dễ dàng quay lại so sánh và mua sắm.</p><a class="button" href="{{ route('shop') }}">Khám phá sản phẩm</a></div>
                @endif
                <div class="pagination lunar-pagination">{{ $items->links() }}</div>
            </section>
        </div>
    </div>
</section>
@endsection
