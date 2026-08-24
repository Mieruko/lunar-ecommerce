@extends('layouts.store', ['title' => $product->name.' | LUNAR JEWELS'])

@section('content')
<section class="page shell product-page">
    <div class="breadcrumb product-breadcrumb">
        <a href="{{ route('home') }}">Home</a><span>/</span>
        <a href="{{ $product->product_type === 'watch' ? route('watches') : route('jewelry') }}">{{ $product->product_type === 'watch' ? 'Watches' : 'Jewelry' }}</a><span>/</span>
        <span>{{ $product->name }}</span>
    </div>

    <div class="detail-grid">
        <div class="gallery-wrap">
            <div class="gallery">
                <div class="thumbs">
                    @foreach ($images as $image)
                        <button class="thumb {{ $loop->first ? 'active' : '' }}" type="button" data-thumb="{{ $image->path }}" aria-label="Xem ảnh {{ $loop->iteration }}">
                            <img src="{{ $image->path }}" alt="{{ $image->alt_text ?: $product->name }}">
                        </button>
                    @endforeach
                </div>
                <div class="main-image">
                    @if ($firstImage)
                        <img data-main-image src="{{ $firstImage }}" alt="{{ $product->name }}">
                    @else
                        <span class="product-placeholder detail-placeholder">LJ</span>
                    @endif
                    <span class="image-caption">LUNAR JEWELS · {{ strtoupper($product->product_type === 'watch' ? 'WATCH' : 'JEWELRY') }}</span>
                </div>
            </div>
        </div>

        <aside class="product-summary">
            <div class="product-summary-head">
                <span class="eyebrow">{{ $product->brand?->name ?: 'Lunar Jewels' }}</span>
                <span class="product-code">{{ $product->product_type === 'watch' ? 'WATCH' : 'JEWEL' }}</span>
            </div>
            <h1 class="product-title">{{ $product->name }}</h1>
            <div class="product-price-row">
                <div class="price product-price"><x-money :amount="$product->variants->first()?->price_amount ?? $product->base_price_amount" /></div>
                <span class="vat-note">VAT included</span>
            </div>
            @if($product->short_description)<p class="product-lead">{{ $product->short_description }}</p>@endif

            @auth
                <form class="product-wishlist-form" method="POST" action="{{ route('account.wishlist.toggle', $product) }}">
                    @csrf
                    <button type="submit"><span>{{ $isWishlisted ? '♥' : '♡' }}</span>{{ $isWishlisted ? 'Đã lưu vào yêu thích' : 'Lưu vào yêu thích' }}</button>
                </form>
            @else
                <a class="product-wishlist-form product-wishlist-link" href="{{ route('login') }}"><span>♡</span> Đăng nhập để lưu sản phẩm</a>
            @endauth

            <hr class="divider">

            <form action="{{ route('cart.store', $product) }}" method="POST" class="purchase-form">
                @csrf
                <div class="variant-list">
                    <div class="variant-heading"><b>Chọn phiên bản</b><span>{{ $product->variants->count() }} options</span></div>
                    @foreach ($product->variants as $variant)
                        @php($stock = max(0, $variant->inventory->sum('quantity_on_hand') - $variant->inventory->sum('quantity_reserved')))
                        <label class="variant-choice {{ $stock === 0 ? 'is-disabled' : '' }}">
                            <span class="variant-main">
                                <input type="radio" name="variant_id" value="{{ $variant->id }}" @checked($loop->first) @disabled($stock === 0)>
                                <span class="radio-ui"></span>
                                <span><b>{{ $variant->name ?? $variant->sku }}</b><small>{{ $variant->sku }}</small></span>
                            </span>
                            <span class="variant-price"><x-money :amount="$variant->price_amount" /><small>{{ $stock > 0 ? ($stock <= 2 ? 'Sắp hết hàng' : 'Sẵn hàng') : 'Hết hàng' }}</small></span>
                        </label>
                    @endforeach
                </div>

                <div class="add-row">
                    <div class="quantity" data-quantity>
                        <button type="button" data-minus aria-label="Giảm số lượng">−</button>
                        <input name="quantity" value="1" inputmode="numeric" aria-label="Số lượng">
                        <button type="button" data-plus aria-label="Tăng số lượng">+</button>
                    </div>
                    <button class="button add-to-bag" @disabled($product->variants->every(fn ($variant) => $variant->inventory->sum('quantity_on_hand') - $variant->inventory->sum('quantity_reserved') <= 0))>
                        Add to bag <span>↗</span>
                    </button>
                </div>
            </form>

            <div class="product-services">
                <div><span>01</span><p><b>Authenticity assured</b><small>Thông tin sản phẩm và thương hiệu minh bạch.</small></p></div>
                <div><span>02</span><p><b>Warranty & care</b><small>Hỗ trợ bảo hành và đổi trả theo chính sách.</small></p></div>
                <div><span>03</span><p><b>Considered delivery</b><small>Miễn phí vận chuyển cho đơn từ 5.000.000 ₫.</small></p></div>
            </div>
        </aside>
    </div>
</section>

<section class="product-story">
    <div class="shell product-story-grid">
        <div>
            <span class="eyebrow eyebrow-light">The piece</span>
            <h2 class="display">DETAILS MAKE<br>THE DIFFERENCE.</h2>
        </div>
        <div class="product-description">
            <span class="story-index">01 / Description</span>
            <p>{{ $product->description ?: $product->short_description }}</p>
        </div>
    </div>
</section>

<section class="section shell product-spec-section">
    <div class="spec-heading">
        <div><span class="eyebrow">Specifications</span><h2 class="display section-title">THE DETAILS</h2></div>
        <span class="spec-mark">LJ / {{ str_pad((string)$product->id, 4, '0', STR_PAD_LEFT) }}</span>
    </div>

    <div class="specs">
        @if ($product->watchDetail)
            @foreach (['Bộ máy' => 'movement', 'Caliber' => 'caliber', 'Chất liệu vỏ' => 'case_material', 'Đường kính' => 'case_diameter_mm', 'Độ dày' => 'case_thickness_mm', 'Mặt số' => 'dial_color', 'Kháng nước' => 'water_resistance_m', 'Mặt kính' => 'crystal', 'Dây đeo' => 'strap_material', 'Màu dây' => 'strap_color', 'Loại khóa' => 'clasp_type', 'Trữ cót' => 'power_reserve_hours', 'Bảo hành' => 'warranty_months'] as $label => $field)
                @if ($product->watchDetail->{$field})
                    <div>
                        <span>{{ $label }}</span>
                        <b>{{ $product->watchDetail->{$field} }}{{ in_array($field, ['case_diameter_mm', 'case_thickness_mm']) ? ' mm' : ($field === 'water_resistance_m' ? ' m' : ($field === 'power_reserve_hours' ? ' giờ' : ($field === 'warranty_months' ? ' tháng' : ''))) }}</b>
                    </div>
                @endif
            @endforeach
            @if(filled($product->watchDetail->functions))
                <div class="spec-wide"><span>Chức năng</span><b>{{ implode(' · ', $product->watchDetail->functions) }}</b></div>
            @endif
        @elseif ($product->jewelryDetail)
            @php($jewelryTypes = ['ring' => 'Nhẫn', 'earrings' => 'Bông tai', 'necklace' => 'Dây chuyền', 'bracelet' => 'Lắc tay', 'pendant' => 'Mặt dây chuyền', 'other' => 'Trang sức'])
            @foreach (['Loại' => 'jewelry_type', 'Đối tượng' => 'gender', 'Phong cách' => 'style', 'Hệ size nhẫn' => 'ring_size_system', 'Dài dây chuyền' => 'chain_length_mm', 'Dài lắc tay' => 'bracelet_length_mm', 'Kích thước' => 'dimensions', 'Trọng lượng' => 'total_weight_grams', 'Lớp mạ' => 'plating'] as $label => $field)
                @if ($product->jewelryDetail->{$field})
                    <div><span>{{ $label }}</span><b>{{ $field === 'jewelry_type' ? ($jewelryTypes[$product->jewelryDetail->{$field}] ?? $product->jewelryDetail->{$field}) : $product->jewelryDetail->{$field} }}{{ in_array($field, ['chain_length_mm', 'bracelet_length_mm']) ? ' mm' : ($field === 'total_weight_grams' ? ' g' : '') }}</b></div>
                @endif
            @endforeach
            @if($product->materials->isNotEmpty())
                <div class="spec-wide"><span>Chất liệu</span><b>{{ $product->materials->map(fn($material) => $material->name.($material->pivot->percentage ? ' '.$material->pivot->percentage.'%' : ''))->implode(' · ') }}</b></div>
            @endif
            @if($product->gemstones->isNotEmpty())
                <div class="spec-wide"><span>Đá đính</span><b>{{ $product->gemstones->map(fn($stone) => $stone->name.' × '.$stone->pivot->quantity.($stone->pivot->total_carat ? ' · '.$stone->pivot->total_carat.' ct' : ''))->implode(' · ') }}</b></div>
            @endif
            @if($product->jewelryDetail->care_instructions)
                <div class="spec-wide"><span>Hướng dẫn bảo quản</span><b>{{ $product->jewelryDetail->care_instructions }}</b></div>
            @endif
        @endif
    </div>
</section>

@if($related->isNotEmpty())
<section class="section shell related-section">
    <div class="section-head luxury-head">
        <div><span class="eyebrow">Continue exploring</span><h2 class="display section-title">YOU MAY ALSO LIKE</h2></div>
        <a class="text-link" href="{{ $product->product_type === 'watch' ? route('watches') : route('jewelry') }}">View collection <span>↗</span></a>
    </div>
    <div class="product-grid">
        @foreach ($related as $item)
            <x-product-card :product="$item" />
        @endforeach
    </div>
</section>
@endif
@endsection
