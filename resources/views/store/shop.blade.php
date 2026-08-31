@extends('layouts.store', ['title' => __('store.catalog.variants.'.($type === 'watch' ? 'watch' : ($type === 'jewelry' ? 'jewelry' : (request('sort') === 'best_sellers' ? 'best_sellers' : 'new_in'))).'.breadcrumb').' | LUNAR JEWELS'])

@section('content')
@php($isBestSellers = $type === null && request('sort') === 'best_sellers')
@php($isNewIn = $type === null && ! $isBestSellers)
@php($catalogVariant = $type === 'watch' ? 'watch' : ($type === 'jewelry' ? 'jewelry' : ($isBestSellers ? 'best_sellers' : 'new_in')))
@php($label = __('store.catalog.variants.'.$catalogVariant.'.label'))
@php($query = request()->except(['page']))
@php($heroCopy = __('store.catalog.variants.'.$catalogVariant.'.copy'))
@php($heroVariant = $type === 'watch' ? 'watch' : ($type === 'jewelry' ? 'jewelry' : ($isBestSellers ? 'best-sellers' : 'new-in')))
@php($heroWord = __('store.catalog.variants.'.$catalogVariant.'.word'))
@php($heroMeta = __('store.catalog.variants.'.$catalogVariant.'.meta'))
@php($heroImage = $type === 'watch' ? 'images/catalog/watches-mechanical.webp' : ($type === 'jewelry' ? 'images/catalog/jewelry-macro.webp' : ($isBestSellers ? 'images/catalog/best-sellers-crown.webp' : 'images/catalog/new-in-moonphase.webp')))

<section class="catalog-hero catalog-hero-{{ $heroVariant }}">
    <img class="catalog-hero-photo" src="{{ asset($heroImage) }}" alt="" aria-hidden="true">
    <div class="shell catalog-hero-inner">
        <div class="catalog-hero-copy">
            <div class="breadcrumb breadcrumb-light"><a href="{{ route('home') }}">{{ __('store.common.home') }}</a><span>/</span>{{ __('store.catalog.variants.'.$catalogVariant.'.breadcrumb') }}</div>
            <span class="eyebrow eyebrow-light">Lunar Jewels · Collection</span>
            <h1 class="display">{{ $label }}</h1>
            <p>{{ $heroCopy }}</p>
        </div>
        <div class="catalog-hero-signature" aria-hidden="true">
            <div class="catalog-signature-meta">
                <span>{{ $heroMeta }}</span>
            </div>
            <strong>{{ $heroWord }}</strong>
        </div>
    </div>
</section>

<section class="page shell catalog-page">
    <div class="catalog-topline">
        <div class="catalog-count">
            <span>{{ str_pad((string) $products->total(), 2, '0', STR_PAD_LEFT) }}</span>
            <small>{{ __('store.catalog.pieces') }}</small>
        </div>
        <div class="catalog-topline-copy">{{ __('store.catalog.curated_by') }}</div>
    </div>

    <div class="shop-layout">
        <form class="filter-panel" data-filter-panel data-catalog-form method="GET" action="{{ url()->current() }}">
            <div class="filter-top">
                <div><span class="eyebrow">{{ __('store.catalog.refine') }}</span><b>{{ __('store.catalog.filters') }}</b></div>
                <div class="filter-top-actions"><a href="{{ url()->current() }}">{{ __('store.catalog.reset') }}</a><button type="button" data-filter-close aria-label="{{ __('store.catalog.close_filters') }}">×</button></div>
            </div>

            @if(request('q'))<input type="hidden" name="q" value="{{ request('q') }}">@endif

            <details class="filter-group" open>
                <summary>{{ __('store.catalog.category') }} <span>+</span></summary>
                <div class="filter-options">
                    @foreach($categories as $category)
                        @php($categoryTranslation = 'store.categories.'.$category->slug)
                        <label><input type="checkbox" name="filter[category][]" value="{{ $category->id }}" @checked(in_array((string)$category->id, array_map('strval',(array)($filters['category']??[]))))><span>{{ \Illuminate\Support\Facades\Lang::has($categoryTranslation) ? __($categoryTranslation) : $category->name }}</span></label>
                    @endforeach
                </div>
            </details>

            <details class="filter-group" open>
                <summary>{{ __('store.catalog.brand') }} <span>+</span></summary>
                <div class="filter-options">
                    @foreach($brands as $brand)
                        <label><input type="checkbox" name="filter[brand][]" value="{{ $brand->id }}" @checked(in_array((string)$brand->id, array_map('strval',(array)($filters['brand']??[]))))><span>{{ $brand->name }}</span></label>
                    @endforeach
                </div>
            </details>

            @if($type !== 'jewelry')
                <details class="filter-group" open>
                    <summary>{{ __('store.catalog.watch_filters') }} <span>+</span></summary>
                    <div class="filter-options">
                        <label><input type="checkbox" name="filter[movement][]" value="Automatic" @checked(in_array('Automatic',(array)($filters['movement']??[])))><span>{{ __('store.catalog.automatic') }}</span></label>
                        <label><input type="checkbox" name="filter[strap_material][]" value="Thép không gỉ" @checked(in_array('Thép không gỉ',(array)($filters['strap_material']??[])))><span>{{ __('store.catalog.steel_strap') }}</span></label>
                        <label><input type="checkbox" name="filter[dial_color][]" value="Xanh navy" @checked(in_array('Xanh navy',(array)($filters['dial_color']??[])))><span>{{ __('store.catalog.navy_dial') }}</span></label>
                        <label><input type="checkbox" name="filter[water_resistance][]" value="50" @checked(in_array('50', array_map('strval',(array)($filters['water_resistance']??[]))))><span>{{ __('store.catalog.water_resistance') }}</span></label>
                    </div>
                </details>
            @endif

            @if($type !== 'watch')
                <details class="filter-group" open>
                    <summary>{{ __('store.catalog.jewelry_filters') }} <span>+</span></summary>
                    <div class="filter-options">
                        <label><input type="checkbox" name="filter[jewelry_type][]" value="ring" @checked(in_array('ring',(array)($filters['jewelry_type']??[])))><span>{{ __('store.catalog.ring') }}</span></label>
                        <label><input type="checkbox" name="filter[jewelry_type][]" value="earrings" @checked(in_array('earrings',(array)($filters['jewelry_type']??[])))><span>{{ __('store.catalog.earrings') }}</span></label>
                        <label><input type="checkbox" name="filter[jewelry_type][]" value="necklace" @checked(in_array('necklace',(array)($filters['jewelry_type']??[])))><span>{{ __('store.catalog.necklace') }}</span></label>
                        <label><input type="checkbox" name="filter[jewelry_type][]" value="bracelet" @checked(in_array('bracelet',(array)($filters['jewelry_type']??[])))><span>{{ __('store.catalog.bracelet') }}</span></label>
                        <label><input type="checkbox" name="filter[jewelry_type][]" value="pendant" @checked(in_array('pendant',(array)($filters['jewelry_type']??[])))><span>{{ __('store.catalog.pendant') }}</span></label>
                        @foreach($materials as $material)
                            <label><input type="checkbox" name="filter[material][]" value="{{ $material->id }}" @checked(in_array((string)$material->id, array_map('strval',(array)($filters['material']??[]))))><span>{{ $material->name }}</span></label>
                        @endforeach
                    </div>
                </details>
            @endif

            <details class="filter-group" open>
                <summary>{{ __('store.catalog.price_range') }} <span>+</span></summary>
                <div class="price-filter">
                    <label><small>{{ __('store.catalog.from') }}</small><input name="min_price" type="number" min="0" placeholder="0 ₫" value="{{ request('min_price') }}"></label>
                    <label><small>{{ __('store.catalog.to') }}</small><input name="max_price" type="number" min="0" placeholder="50.000.000 ₫" value="{{ request('max_price') }}"></label>
                </div>
            </details>

            <button class="button filter-submit">{{ __('store.catalog.apply_filters') }}</button>
        </form>
        <button class="filter-backdrop" type="button" data-filter-close aria-label="{{ __('store.catalog.close_filters') }}"></button>

        <div class="catalog-results">
            <div class="filter-toolbar">
                <div>
                    @if(request('q'))<span class="search-result-label">{{ __('store.catalog.results_for', ['query' => request('q')]) }}</span>@endif
                    <p>{{ __('store.catalog.showing', ['first' => $products->firstItem() ?? 0, 'last' => $products->lastItem() ?? 0, 'total' => $products->total()]) }}</p>
                </div>

                <div class="toolbar-actions">
                    <button class="button-outline mobile-filters" type="button" data-filter-toggle>{{ __('store.catalog.mobile_filters') }}</button>
                    <form method="GET" action="{{ url()->current() }}" data-catalog-form>
                        @if(request('q'))<input type="hidden" name="q" value="{{ request('q') }}">@endif
                        @if(request('min_price'))<input type="hidden" name="min_price" value="{{ request('min_price') }}">@endif
                        @if(request('max_price'))<input type="hidden" name="max_price" value="{{ request('max_price') }}">@endif
                        @foreach($filters as $key => $values)
                            @foreach((array) $values as $value)
                                <input type="hidden" name="filter[{{ $key }}][]" value="{{ $value }}">
                            @endforeach
                        @endforeach
                        <label class="sort-select">{{ __('store.catalog.sort_by') }}
                            <select name="sort" data-catalog-sort onchange="this.form.submit()">
                                <option value="newest" @selected(request('sort','newest')==='newest')>{{ __('store.catalog.newest') }}</option>
                                <option value="price_asc" @selected(request('sort')==='price_asc')>{{ __('store.catalog.price_low_high') }}</option>
                                <option value="price_desc" @selected(request('sort')==='price_desc')>{{ __('store.catalog.price_high_low') }}</option>
                                <option value="best_sellers" @selected(request('sort')==='best_sellers')>{{ __('store.catalog.best_sellers') }}</option>
                            </select>
                        </label>
                    </form>
                </div>
            </div>

            @if(!empty($filters)||request('min_price')||request('max_price'))
                <div class="filter-chips">
                    @foreach($filters as $key => $values)
                        @foreach((array) $values as $value)
                            <span class="chip">{{ str_replace('_',' ',$key) }}: {{ $value }}</span>
                        @endforeach
                    @endforeach
                    @if(request('min_price')||request('max_price'))<span class="chip">{{ __('store.catalog.selected_price_range') }}</span>@endif
                    <a class="chip chip-clear" href="{{ url()->current() }}">{{ __('store.catalog.clear_all') }}</a>
                </div>
            @endif

            <div class="product-grid catalog-grid" data-catalog-grid data-reveal-group>
                @forelse($products as $product)
                    <div data-reveal><x-product-card :product="$product" /></div>
                @empty
                    <div class="empty product-grid-empty">
                        <span class="empty-orbit"></span>
                        <h3>{{ __('store.catalog.empty_title') }}</h3>
                        <p>{{ __('store.catalog.empty_copy') }}</p>
                        <a class="button-outline" href="{{ url()->current() }}">{{ __('store.catalog.clear_filters') }}</a>
                    </div>
                @endforelse
            </div>

            <div class="pagination">{{ $products->links() }}</div>
        </div>
    </div>
</section>
@endsection
