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
            <div class="gallery" data-product-gallery>
                <div class="thumbs" style="--gallery-image-count: {{ max(1, $images->count()) }}">
                    @foreach ($images as $image)
                        <button class="thumb {{ $loop->first ? 'active' : '' }}" type="button" data-thumb="{{ $image->path }}" data-thumb-alt="{{ $image->alt_text ?: $product->name }}" data-image-index="{{ $loop->index }}" aria-label="Xem ảnh {{ $loop->iteration }} trên {{ $images->count() }}" aria-pressed="{{ $loop->first ? 'true' : 'false' }}">
                            <img src="{{ $image->path }}" alt="{{ $image->alt_text ?: $product->name }}" loading="{{ $loop->first ? 'eager' : 'lazy' }}">
                        </button>
                    @endforeach
                </div>
                <div class="main-image">
                    @if ($firstImage)
                        <img data-main-image src="{{ $firstImage }}" alt="{{ $images->first()?->alt_text ?: $product->name }}" fetchpriority="high" decoding="async">
                    @else
                        <span class="product-placeholder detail-placeholder">LJ</span>
                    @endif
                    <div class="image-meta">
                        <span class="image-caption">LUNAR JEWELS · {{ strtoupper($product->product_type === 'watch' ? 'WATCH' : 'JEWELRY') }}</span>
                        @if($images->isNotEmpty())
                            <span class="image-count"><b data-image-current>01</b> / {{ str_pad((string) $images->count(), 2, '0', STR_PAD_LEFT) }}</span>
                        @endif
                    </div>
                    @if($images->count() > 1)
                        <div class="gallery-nav" aria-label="Điều hướng ảnh sản phẩm">
                            <button type="button" data-gallery-prev aria-label="Ảnh trước">←</button>
                            <button type="button" data-gallery-next aria-label="Ảnh tiếp theo">→</button>
                        </div>
                    @endif
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
            <a class="summary-rating" href="#reviews" aria-label="Xem đánh giá sản phẩm">
                <span class="rating-stars" aria-hidden="true">★★★★★</span>
                @if($reviews->isNotEmpty())
                    <b>{{ number_format($reviewAverage, 1, ',', '') }}</b><span>{{ $reviews->count() }} đánh giá đã xác thực</span>
                @else
                    <span>Chưa có đánh giá · Xem điều kiện đánh giá</span>
                @endif
            </a>
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
        <div><span class="eyebrow">Specifications</span><h2 class="display section-title">THÔNG SỐ SẢN PHẨM</h2><p>Các thông tin quan trọng được tách rõ theo từng nhóm để dễ so sánh.</p></div>
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

<section class="section product-review-section" id="reviews">
    <div class="shell">
        <div class="review-heading">
            <div>
                <span class="eyebrow">Verified reviews</span>
                <h2 class="display section-title">ĐÁNH GIÁ KHÁCH HÀNG</h2>
                <p>Chỉ tài khoản có đơn chứa sản phẩm này và đã chuyển sang “Đã hoàn thành” mới được gửi đánh giá.</p>
            </div>
            <span class="spec-mark">{{ str_pad((string) $reviews->count(), 2, '0', STR_PAD_LEFT) }} / REVIEWS</span>
        </div>

        <div class="review-grid">
            <aside class="review-overview">
                <div class="review-score">
                    <strong>{{ $reviews->isEmpty() ? '—' : number_format($reviewAverage, 1, ',', '') }}</strong>
                    <div><span class="rating-stars" aria-hidden="true">★★★★★</span><small>{{ $reviews->isEmpty() ? 'Chưa có đánh giá được duyệt' : $reviews->count().' đánh giá đã xác thực' }}</small></div>
                </div>
                <div class="rating-bars">
                    @foreach(range(5, 1) as $rating)
                        @php($percentage = $reviews->count() ? round(($ratingCounts[$rating] / $reviews->count()) * 100) : 0)
                        <div class="rating-bar-row">
                            <span>{{ $rating }} sao</span>
                            <i><b style="width: {{ $percentage }}%"></b></i>
                            <em>{{ $ratingCounts[$rating] }}</em>
                        </div>
                    @endforeach
                </div>

                <div class="review-permission">
                    @guest
                        <span class="verified-icon">✓</span>
                        <div><b>Đánh giá từ người mua thật</b><p><a href="{{ route('login') }}">Đăng nhập</a> để hệ thống kiểm tra đơn hàng đã hoàn thành của bạn.</p></div>
                    @else
                        @if($eligibleOrderItem)
                            <span class="verified-icon">✓</span>
                            <div><b>Bạn đủ điều kiện đánh giá</b><p>Đơn #{{ $eligibleOrderItem->order?->order_number }} đã hoàn thành.</p></div>
                        @else
                            <span class="verified-icon is-locked">⌁</span>
                            <div><b>Chưa mở quyền đánh giá</b><p>Quyền này tự động mở sau khi đơn hàng chứa sản phẩm được hoàn thành.</p></div>
                        @endif
                    @endguest
                </div>
            </aside>

            <div class="review-content">
                @auth
                    @if($eligibleOrderItem)
                        @php($reviewStatus = ['pending' => 'Đang chờ duyệt', 'approved' => 'Đã được duyệt', 'rejected' => 'Cần chỉnh sửa'])
                        <form class="review-form" method="POST" action="{{ route('products.reviews.store', $product) }}">
                            @csrf
                            <div class="review-form-head">
                                <div><span class="eyebrow">Your experience</span><h3>{{ $myReview ? 'Cập nhật đánh giá của bạn' : 'Chia sẻ trải nghiệm của bạn' }}</h3></div>
                                @if($myReview)<span class="review-status status-{{ $myReview->status }}">{{ $reviewStatus[$myReview->status] }}</span>@endif
                            </div>
                            <fieldset class="rating-field">
                                <legend>Mức độ hài lòng</legend>
                                <div class="review-rating-input">
                                    @for($rating = 5; $rating >= 1; $rating--)
                                        <input id="review-rating-{{ $rating }}" type="radio" name="rating" value="{{ $rating }}" @checked((int) old('rating', $myReview?->rating) === $rating) required>
                                        <label for="review-rating-{{ $rating }}" title="{{ $rating }} sao"><span aria-hidden="true">★</span><span class="sr-only">{{ $rating }} sao</span></label>
                                    @endfor
                                </div>
                            </fieldset>
                            <div class="review-fields">
                                <label class="field"><span>Tiêu đề <small>không bắt buộc</small></span><input name="title" maxlength="120" value="{{ old('title', $myReview?->title) }}" placeholder="Điểm bạn ấn tượng nhất"></label>
                                <label class="field"><span>Nội dung đánh giá</span><textarea name="body" rows="5" minlength="10" maxlength="2000" required placeholder="Chia sẻ về thiết kế, độ hoàn thiện và trải nghiệm sử dụng...">{{ old('body', $myReview?->body) }}</textarea></label>
                            </div>
                            <div class="review-submit-row"><p><span>✓</span> Đánh giá sẽ mang nhãn “Đã mua hàng” sau khi được duyệt.</p><button class="button" type="submit">{{ $myReview ? 'Gửi lại để duyệt' : 'Gửi đánh giá' }} <span>↗</span></button></div>
                        </form>
                    @endif
                @endauth

                <div class="review-list">
                    @forelse($reviews as $review)
                        <article class="review-card">
                            <div class="review-card-meta">
                                <span class="review-avatar">{{ mb_strtoupper(mb_substr($review->user?->name ?: 'K', 0, 1)) }}</span>
                                <div><b>{{ $review->user?->name ?: 'Khách hàng' }}</b><span><i>✓</i> Đã mua hàng</span></div>
                                <time datetime="{{ $review->created_at->toDateString() }}">{{ $review->created_at->format('d.m.Y') }}</time>
                            </div>
                            <div class="review-card-body">
                                <span class="rating-stars" aria-label="{{ $review->rating }} trên 5 sao">{{ str_repeat('★', $review->rating) }}<i>{{ str_repeat('★', 5 - $review->rating) }}</i></span>
                                @if($review->title)<h3>{{ $review->title }}</h3>@endif
                                <p>{{ $review->body }}</p>
                            </div>
                        </article>
                    @empty
                        <div class="review-empty"><span>✦</span><h3>Chưa có đánh giá được duyệt</h3><p>Hãy là khách hàng đầu tiên chia sẻ trải nghiệm sau khi hoàn thành đơn hàng.</p></div>
                    @endforelse
                </div>
            </div>
        </div>
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
