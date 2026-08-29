@props(['product'])
@php($variant = $product->variants->first())
@php($image = $product->primaryImage?->path ?? $product->images?->first()?->path)
@php($secondaryImage = $product->images?->firstWhere(fn ($img) => $img->path && $img->path !== $image)?->path)
@php($stock = $variant ? max(0, $variant->inventory->sum('quantity_on_hand') - $variant->inventory->sum('quantity_reserved')) : 0)
@php($price = $variant?->price_amount ?? $product->base_price_amount)
@php($compareAt = $variant?->compare_at_price_amount)

<article class="product-card">
    <a class="product-image {{ $secondaryImage ? 'has-alt-image' : '' }}" href="{{ route('products.show', $product) }}" aria-label="Xem {{ $product->name }}">
        <span class="product-badges">
            @if($product->is_featured)<span class="product-badge">Selected</span>@endif
            @if($compareAt && $compareAt > $price)<span class="product-badge product-badge-sale">-{{ (int) round((1 - $price / $compareAt) * 100) }}%</span>@endif
            @if($stock > 0 && $stock <= 2)<span class="product-badge product-badge-warm">Last pieces</span>@endif
        </span>
        @if($image)
            <img class="product-image-primary" src="{{ $image }}" alt="{{ $product->name }}" loading="lazy">
            @if($secondaryImage)
                <img class="product-image-alt" src="{{ $secondaryImage }}" alt="" aria-hidden="true" loading="lazy">
            @endif
        @else
            <span class="product-placeholder" aria-hidden="true">LJ</span>
        @endif
        <span class="product-view">View piece <span>↗</span></span>
    </a>

    @auth
        <form class="card-wishlist-form" method="POST" action="{{ route('account.wishlist.toggle', $product) }}">
            @csrf
            <button class="card-wishlist" type="submit" aria-label="Lưu {{ $product->name }} vào yêu thích" title="Yêu thích">♡</button>
        </form>
    @else
        <a class="card-wishlist" href="{{ route('login') }}" aria-label="Đăng nhập để lưu sản phẩm yêu thích" title="Yêu thích">♡</a>
    @endauth

    <div class="product-info">
        <div class="product-meta">
            <span class="brand">{{ $product->brand?->name ?: 'LUNAR JEWELS' }}</span>
            <span class="product-type">{{ $product->product_type === 'watch' ? 'Watch' : 'Jewelry' }}</span>
        </div>
        <a class="product-name" href="{{ route('products.show', $product) }}">{{ $product->name }}</a>
        <div class="product-bottom">
            <span class="price"><x-price :amount="$price" :compare-at="$compareAt" /></span>
            <span class="stock {{ $stock <= 2 ? 'low' : '' }}">{{ $stock > 0 ? ($stock <= 2 ? 'Sắp hết' : 'Sẵn hàng') : 'Tạm hết' }}</span>
        </div>
    </div>
</article>
