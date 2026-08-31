@extends('layouts.store', ['title' => $product->name.' | LUNAR JEWELS'])

@section('content')
<section class="page shell product-page">
    <div class="breadcrumb product-breadcrumb">
        <a href="{{ route('home') }}">{{ __('store.common.home') }}</a><span>/</span>
        <a href="{{ $product->product_type === 'watch' ? route('watches') : route('jewelry') }}">{{ $product->product_type === 'watch' ? __('store.nav.watches') : __('store.nav.jewelry') }}</a><span>/</span>
        <span>{{ $product->name }}</span>
    </div>

    <div class="detail-grid">
        <div class="gallery-wrap">
            <div class="gallery" data-product-gallery>
                <div class="thumbs" style="--gallery-image-count: {{ max(1, $images->count()) }}">
                    @foreach ($images as $image)
                        <button class="thumb {{ $loop->first ? 'active' : '' }}" type="button" data-thumb="{{ $image->path }}" data-thumb-alt="{{ $image->alt_text ?: $product->name }}" data-image-index="{{ $loop->index }}" aria-label="{{ __('store.product.gallery_image', ['current' => $loop->iteration, 'total' => $images->count()]) }}" aria-pressed="{{ $loop->first ? 'true' : 'false' }}">
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
                        <div class="gallery-nav" aria-label="{{ __('store.product.gallery_navigation') }}">
                            <button type="button" data-gallery-prev aria-label="{{ __('store.product.previous_image') }}">←</button>
                            <button type="button" data-gallery-next aria-label="{{ __('store.product.next_image') }}">→</button>
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
                <div class="price product-price"><x-price :amount="$product->variants->first()?->price_amount ?? $product->base_price_amount" :compare-at="$product->variants->first()?->compare_at_price_amount" /></div>
                <span class="vat-note">{{ __('store.product.vat_included') }}</span>
            </div>
            <a class="summary-rating" href="#reviews" aria-label="{{ __('store.product.reviews_aria') }}">
                <span class="rating-stars" aria-hidden="true">★★★★★</span>
                @if($reviews->isNotEmpty())
                    <b>{{ number_format($reviewAverage, 1, ',', '') }}</b><span>{{ __('store.product.verified_reviews_count', ['count' => $reviews->count()]) }}</span>
                @else
                    <span>{{ __('store.product.no_reviews_short') }}</span>
                @endif
            </a>
            @if($product->short_description)<p class="product-lead">{{ $product->short_description }}</p>@endif

            @auth
                <form class="product-wishlist-form" method="POST" action="{{ route('account.wishlist.toggle', $product) }}">
                    @csrf
                    <button type="submit"><span>{{ $isWishlisted ? '♥' : '♡' }}</span>{{ $isWishlisted ? __('store.product.saved') : __('store.product.save') }}</button>
                </form>
            @else
                <a class="product-wishlist-form product-wishlist-link" href="{{ route('login') }}"><span>♡</span> {{ __('store.product.login_to_save') }}</a>
            @endauth

            <hr class="divider">

            <form action="{{ route('cart.store', $product) }}" method="POST" class="purchase-form" data-loading-form>
                @csrf
                <div class="variant-list">
                    <div class="variant-heading"><b>{{ __('store.product.choose_variant') }}</b><span>{{ __('store.product.options', ['count' => $product->variants->count()]) }}</span></div>
                    @foreach ($product->variants as $variant)
                        @php($stock = max(0, $variant->inventory->sum('quantity_on_hand') - $variant->inventory->sum('quantity_reserved')))
                        <label class="variant-choice {{ $stock === 0 ? 'is-disabled' : '' }}">
                            <span class="variant-main">
                                <input type="radio" name="variant_id" value="{{ $variant->id }}" @checked($loop->first) @disabled($stock === 0)>
                                <span class="radio-ui"></span>
                                <span><b>{{ $variant->name ?? $variant->sku }}</b><small>{{ $variant->sku }}</small></span>
                            </span>
                            <span class="variant-price"><x-price :amount="$variant->price_amount" :compare-at="$variant->compare_at_price_amount" /><small>{{ $stock > 0 ? ($stock <= 2 ? __('store.product_card.low_stock') : __('store.product_card.in_stock')) : __('store.product_card.out_of_stock') }}</small></span>
                        </label>
                    @endforeach
                </div>

                <div class="add-row">
                    <div class="quantity" data-quantity>
                        <button type="button" data-minus aria-label="{{ __('store.product.decrease') }}">−</button>
                        <input name="quantity" value="1" inputmode="numeric" aria-label="{{ __('store.product.quantity') }}">
                        <button type="button" data-plus aria-label="{{ __('store.product.increase') }}">+</button>
                    </div>
                    <button class="button add-to-bag" @disabled($product->variants->every(fn ($variant) => $variant->inventory->sum('quantity_on_hand') - $variant->inventory->sum('quantity_reserved') <= 0))>
                        {{ __('store.product.add_to_bag') }} <span>↗</span>
                    </button>
                </div>
            </form>

            <div class="product-services">
                <div><span>01</span><p><b>{{ __('store.product.authenticity') }}</b><small>{{ __('store.product.authenticity_copy') }}</small></p></div>
                <div><span>02</span><p><b>{{ __('store.product.warranty') }}</b><small>{{ __('store.product.warranty_copy') }}</small></p></div>
                <div><span>03</span><p><b>{{ __('store.product.delivery') }}</b><small>{{ __('store.product.delivery_copy') }}</small></p></div>
            </div>
        </aside>
    </div>
</section>

<section class="product-story">
    <div class="shell product-story-grid">
        <div>
            <span class="eyebrow eyebrow-light">{{ __('store.product.the_piece') }}</span>
            <h2 class="display">{!! __('store.product.story_title') !!}</h2>
        </div>
        <div class="product-description">
            <span class="story-index">01 / {{ __('store.product.description') }}</span>
            <p>{{ $product->description ?: $product->short_description }}</p>
        </div>
    </div>
</section>

<section class="section shell product-spec-section">
    <div class="spec-heading">
        <div><span class="eyebrow">{{ __('store.product.specifications') }}</span><h2 class="display section-title">{{ __('store.product.spec_title') }}</h2><p>{{ __('store.product.spec_copy') }}</p></div>
        <span class="spec-mark">LJ / {{ str_pad((string)$product->id, 4, '0', STR_PAD_LEFT) }}</span>
    </div>

    <div class="specs">
        @if ($product->watchDetail)
            @foreach (['movement', 'caliber', 'case_material', 'case_diameter_mm', 'case_thickness_mm', 'dial_color', 'water_resistance_m', 'crystal', 'strap_material', 'strap_color', 'clasp_type', 'power_reserve_hours', 'warranty_months'] as $field)
                @if ($product->watchDetail->{$field})
                    <div>
                        <span>{{ __("store.product.spec.{$field}") }}</span>
                        <b>{{ $product->watchDetail->{$field} }}{{ in_array($field, ['case_diameter_mm', 'case_thickness_mm']) ? ' mm' : ($field === 'water_resistance_m' ? ' m' : ($field === 'power_reserve_hours' ? ' '.__('store.product.spec.hours') : ($field === 'warranty_months' ? ' '.__('store.product.spec.months') : ''))) }}</b>
                    </div>
                @endif
            @endforeach
            @if(filled($product->watchDetail->functions))
                <div class="spec-wide"><span>{{ __('store.product.spec.functions') }}</span><b>{{ implode(' · ', $product->watchDetail->functions) }}</b></div>
            @endif
        @elseif ($product->jewelryDetail)
            @php($jewelryTypes = __('store.product.jewelry_types'))
            @foreach (['jewelry_type', 'gender', 'style', 'ring_size_system', 'chain_length_mm', 'bracelet_length_mm', 'dimensions', 'total_weight_grams', 'plating'] as $field)
                @if ($product->jewelryDetail->{$field})
                    <div><span>{{ __("store.product.spec.{$field}") }}</span><b>{{ $field === 'jewelry_type' ? ($jewelryTypes[$product->jewelryDetail->{$field}] ?? $product->jewelryDetail->{$field}) : $product->jewelryDetail->{$field} }}{{ in_array($field, ['chain_length_mm', 'bracelet_length_mm']) ? ' mm' : ($field === 'total_weight_grams' ? ' g' : '') }}</b></div>
                @endif
            @endforeach
            @if($product->materials->isNotEmpty())
                <div class="spec-wide"><span>{{ __('store.product.spec.materials') }}</span><b>{{ $product->materials->map(fn($material) => $material->name.($material->pivot->percentage ? ' '.$material->pivot->percentage.'%' : ''))->implode(' · ') }}</b></div>
            @endif
            @if($product->gemstones->isNotEmpty())
                <div class="spec-wide"><span>{{ __('store.product.spec.gemstones') }}</span><b>{{ $product->gemstones->map(fn($stone) => $stone->name.' × '.$stone->pivot->quantity.($stone->pivot->total_carat ? ' · '.$stone->pivot->total_carat.' ct' : ''))->implode(' · ') }}</b></div>
            @endif
            @if($product->jewelryDetail->care_instructions)
                <div class="spec-wide"><span>{{ __('store.product.spec.care') }}</span><b>{{ $product->jewelryDetail->care_instructions }}</b></div>
            @endif
        @endif
    </div>
</section>

<section class="section product-review-section" id="reviews">
    <div class="shell">
        <div class="review-heading">
            <div>
                <span class="eyebrow">{{ __('store.product.customer_reviews') }}</span>
                <h2 class="display section-title">{{ __('store.product.review_title') }}</h2>
                <p>{{ __('store.product.review_copy') }}</p>
            </div>
            <span class="spec-mark">{{ str_pad((string) $reviews->count(), 2, '0', STR_PAD_LEFT) }} / REVIEWS</span>
        </div>

        <div class="review-grid">
            <aside class="review-overview">
                <div class="review-score">
                    <strong>{{ $reviews->isEmpty() ? '—' : number_format($reviewAverage, 1, ',', '') }}</strong>
                    <div><span class="rating-stars" aria-hidden="true">★★★★★</span><small>{{ $reviews->isEmpty() ? __('store.product.no_approved_reviews') : __('store.product.verified_reviews_count', ['count' => $reviews->count()]) }}</small></div>
                </div>
                <div class="rating-bars">
                    @foreach(range(5, 1) as $rating)
                        @php($percentage = $reviews->count() ? round(($ratingCounts[$rating] / $reviews->count()) * 100) : 0)
                        <div class="rating-bar-row">
                            <span>{{ __('store.product.stars', ['count' => $rating]) }}</span>
                            <i><b style="width: {{ $percentage }}%"></b></i>
                            <em>{{ $ratingCounts[$rating] }}</em>
                        </div>
                    @endforeach
                </div>

                <div class="review-permission">
                    @guest
                        <span class="verified-icon">✓</span>
                        <div><b>{{ __('store.product.real_buyers') }}</b><p><a href="{{ route('login') }}">{{ __('store.common.login') }}</a> {{ __('store.product.login_review') }}</p></div>
                    @else
                        @if($eligibleOrderItem)
                            <span class="verified-icon">✓</span>
                            <div><b>{{ __('store.product.eligible') }}</b><p>{{ __('store.product.eligible_copy', ['order' => $eligibleOrderItem->order?->order_number]) }}</p></div>
                        @else
                            <span class="verified-icon is-locked">⌁</span>
                            <div><b>{{ __('store.product.not_eligible') }}</b><p>{{ __('store.product.not_eligible_copy') }}</p></div>
                        @endif
                    @endguest
                </div>
            </aside>

            <div class="review-content">
                @auth
                    @if($eligibleOrderItem)
                        @php($reviewStatus = ['pending' => __('store.product.review_pending'), 'approved' => __('store.product.review_approved'), 'rejected' => __('store.product.review_rejected')])
                        <form class="review-form" method="POST" action="{{ route('products.reviews.store', $product) }}">
                            @csrf
                            <div class="review-form-head">
                                <div><span class="eyebrow">{{ __('store.product.customer_reviews') }}</span><h3>{{ $myReview ? __('store.product.update_review') : __('store.product.share_review') }}</h3></div>
                                @if($myReview)<span class="review-status status-{{ $myReview->status }}">{{ $reviewStatus[$myReview->status] }}</span>@endif
                            </div>
                            <fieldset class="rating-field">
                                <legend>{{ __('store.product.satisfaction') }}</legend>
                                <div class="review-rating-input">
                                    @for($rating = 5; $rating >= 1; $rating--)
                                        <input id="review-rating-{{ $rating }}" type="radio" name="rating" value="{{ $rating }}" @checked((int) old('rating', $myReview?->rating) === $rating) required>
                                        <label for="review-rating-{{ $rating }}" title="{{ __('store.product.stars', ['count' => $rating]) }}"><span aria-hidden="true">★</span><span class="sr-only">{{ __('store.product.stars', ['count' => $rating]) }}</span></label>
                                    @endfor
                                </div>
                            </fieldset>
                            <div class="review-fields">
                                <label class="field"><span>{{ __('store.product.review_heading') }} <small>{{ __('store.product.optional') }}</small></span><input name="title" maxlength="120" value="{{ old('title', $myReview?->title) }}" placeholder="{{ __('store.product.review_heading_placeholder') }}"></label>
                                <label class="field"><span>{{ __('store.product.review_body') }}</span><textarea name="body" rows="5" minlength="10" maxlength="2000" required placeholder="{{ __('store.product.review_body_placeholder') }}">{{ old('body', $myReview?->body) }}</textarea></label>
                            </div>
                            <div class="review-submit-row"><p><span>✓</span> {{ __('store.product.verified_badge_copy') }}</p><button class="button" type="submit">{{ $myReview ? __('store.product.resubmit_review') : __('store.product.submit_review') }} <span>↗</span></button></div>
                        </form>
                    @endif
                @endauth

                <div class="review-list">
                    @forelse($reviews as $review)
                        <article class="review-card">
                            <div class="review-card-meta">
                                <span class="review-avatar">{{ mb_strtoupper(mb_substr($review->user?->name ?: 'K', 0, 1)) }}</span>
                                <div><b>{{ $review->user?->name ?: __('store.product.customer') }}</b><span><i>✓</i> {{ __('store.product.verified_purchase') }}</span></div>
                                <time datetime="{{ $review->created_at->toDateString() }}">{{ $review->created_at->format('d.m.Y') }}</time>
                            </div>
                            <div class="review-card-body">
                                <span class="rating-stars" aria-label="{{ __('store.product.rating_aria', ['count' => $review->rating]) }}">{{ str_repeat('★', $review->rating) }}<i>{{ str_repeat('★', 5 - $review->rating) }}</i></span>
                                @if($review->title)<h3>{{ $review->title }}</h3>@endif
                                <p>{{ $review->body }}</p>
                            </div>
                        </article>
                    @empty
                        <div class="review-empty"><span>✦</span><h3>{{ __('store.product.no_approved_reviews') }}</h3><p>{{ __('store.product.review_empty_copy') }}</p></div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</section>

@if($related->isNotEmpty())
<section class="section shell related-section">
    <div class="section-head luxury-head" data-reveal>
        <div><span class="eyebrow">{{ __('store.product.continue_exploring') }}</span><h2 class="display section-title">{{ __('store.product.you_may_like') }}</h2></div>
        <a class="text-link" href="{{ $product->product_type === 'watch' ? route('watches') : route('jewelry') }}">{{ __('store.product.view_collection') }} <span>↗</span></a>
    </div>
    <div class="product-grid" data-reveal-group>
        @foreach ($related as $item)
            <div data-reveal><x-product-card :product="$item" /></div>
        @endforeach
    </div>
</section>
@endif
@endsection
