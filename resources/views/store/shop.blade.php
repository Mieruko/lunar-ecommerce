@extends('layouts.store', ['title' => ($type === 'watch' ? 'Watches' : ($type === 'jewelry' ? 'Jewelry' : 'Shop')).' | LUNAR JEWELS'])

@section('content')
@php($label = $type === 'watch' ? 'WATCHES' : ($type === 'jewelry' ? 'JEWELRY' : 'THE COLLECTION'))
@php($query = request()->except(['page']))
@php($heroCopy = $type === 'watch' ? 'Cỗ máy chính xác, tỷ lệ cân bằng và những thiết kế được tạo để đồng hành lâu dài.' : ($type === 'jewelry' ? 'Trang sức tuyển chọn với ánh kim tinh tế, đường nét thanh lịch và dấu ấn riêng.' : 'Khám phá tuyển tập đồng hồ và trang sức được chọn lọc từ thế giới của Lunar Jewels.'))

<section class="catalog-hero {{ $type === 'jewelry' ? 'catalog-hero-jewelry' : '' }}">
    <div class="shell catalog-hero-inner">
        <div>
            <div class="breadcrumb breadcrumb-light"><a href="{{ route('home') }}">Home</a><span>/</span>{{ ucfirst(strtolower($label)) }}</div>
            <span class="eyebrow eyebrow-light">Lunar Jewels · Collection</span>
            <h1 class="display">{{ $label }}</h1>
            <p>{{ $heroCopy }}</p>
        </div>
        <div class="catalog-orbit" aria-hidden="true"><span></span></div>
    </div>
</section>

<section class="page shell catalog-page">
    <div class="catalog-topline">
        <div class="catalog-count">
            <span>{{ str_pad((string) $products->total(), 2, '0', STR_PAD_LEFT) }}</span>
            <small>pieces</small>
        </div>
        <div class="catalog-topline-copy">Curated by Lunar Jewels</div>
    </div>

    <div class="shop-layout">
        <form class="filter-panel" data-filter-panel method="GET" action="{{ url()->current() }}">
            <div class="filter-top">
                <div><span class="eyebrow">Refine</span><b>Bộ lọc</b></div>
                <div class="filter-top-actions"><a href="{{ url()->current() }}">Đặt lại</a><button type="button" data-filter-close aria-label="Đóng bộ lọc">×</button></div>
            </div>

            @if(request('q'))<input type="hidden" name="q" value="{{ request('q') }}">@endif

            <details class="filter-group" open>
                <summary>Danh mục <span>+</span></summary>
                <div class="filter-options">
                    @foreach($categories as $category)
                        <label><input type="checkbox" name="filter[category][]" value="{{ $category->id }}" @checked(in_array((string)$category->id, array_map('strval',(array)($filters['category']??[]))))><span>{{ $category->name }}</span></label>
                    @endforeach
                </div>
            </details>

            <details class="filter-group" open>
                <summary>Thương hiệu <span>+</span></summary>
                <div class="filter-options">
                    @foreach($brands as $brand)
                        <label><input type="checkbox" name="filter[brand][]" value="{{ $brand->id }}" @checked(in_array((string)$brand->id, array_map('strval',(array)($filters['brand']??[]))))><span>{{ $brand->name }}</span></label>
                    @endforeach
                </div>
            </details>

            @if($type !== 'jewelry')
                <details class="filter-group" open>
                    <summary>Watches <span>+</span></summary>
                    <div class="filter-options">
                        <label><input type="checkbox" name="filter[movement][]" value="Automatic" @checked(in_array('Automatic',(array)($filters['movement']??[])))><span>Automatic</span></label>
                        <label><input type="checkbox" name="filter[strap_material][]" value="Thép không gỉ" @checked(in_array('Thép không gỉ',(array)($filters['strap_material']??[])))><span>Dây thép</span></label>
                        <label><input type="checkbox" name="filter[dial_color][]" value="Xanh navy" @checked(in_array('Xanh navy',(array)($filters['dial_color']??[])))><span>Mặt xanh navy</span></label>
                        <label><input type="checkbox" name="filter[water_resistance][]" value="50" @checked(in_array('50', array_map('strval',(array)($filters['water_resistance']??[]))))><span>Kháng nước 50m+</span></label>
                    </div>
                </details>
            @endif

            @if($type !== 'watch')
                <details class="filter-group" open>
                    <summary>Jewelry <span>+</span></summary>
                    <div class="filter-options">
                        <label><input type="checkbox" name="filter[jewelry_type][]" value="ring" @checked(in_array('ring',(array)($filters['jewelry_type']??[])))><span>Nhẫn</span></label>
                        <label><input type="checkbox" name="filter[jewelry_type][]" value="earrings" @checked(in_array('earrings',(array)($filters['jewelry_type']??[])))><span>Bông tai</span></label>
                        <label><input type="checkbox" name="filter[jewelry_type][]" value="necklace" @checked(in_array('necklace',(array)($filters['jewelry_type']??[])))><span>Dây chuyền</span></label>
                        <label><input type="checkbox" name="filter[jewelry_type][]" value="bracelet" @checked(in_array('bracelet',(array)($filters['jewelry_type']??[])))><span>Lắc tay</span></label>
                        <label><input type="checkbox" name="filter[jewelry_type][]" value="pendant" @checked(in_array('pendant',(array)($filters['jewelry_type']??[])))><span>Mặt dây chuyền</span></label>
                        @foreach($materials as $material)
                            <label><input type="checkbox" name="filter[material][]" value="{{ $material->id }}" @checked(in_array((string)$material->id, array_map('strval',(array)($filters['material']??[]))))><span>{{ $material->name }}</span></label>
                        @endforeach
                    </div>
                </details>
            @endif

            <details class="filter-group" open>
                <summary>Khoảng giá <span>+</span></summary>
                <div class="price-filter">
                    <label><small>Từ</small><input name="min_price" type="number" min="0" placeholder="0 ₫" value="{{ request('min_price') }}"></label>
                    <label><small>Đến</small><input name="max_price" type="number" min="0" placeholder="50.000.000 ₫" value="{{ request('max_price') }}"></label>
                </div>
            </details>

            <button class="button filter-submit">Áp dụng bộ lọc</button>
        </form>
        <button class="filter-backdrop" type="button" data-filter-close aria-label="Đóng bộ lọc"></button>

        <div class="catalog-results">
            <div class="filter-toolbar">
                <div>
                    @if(request('q'))<span class="search-result-label">Kết quả cho “{{ request('q') }}”</span>@endif
                    <p>Hiển thị {{ $products->firstItem() ?? 0 }}–{{ $products->lastItem() ?? 0 }} / {{ $products->total() }} sản phẩm</p>
                </div>

                <div class="toolbar-actions">
                    <button class="button-outline mobile-filters" type="button" data-filter-toggle>Filters</button>
                    <form method="GET" action="{{ url()->current() }}">
                        @if(request('q'))<input type="hidden" name="q" value="{{ request('q') }}">@endif
                        @if(request('min_price'))<input type="hidden" name="min_price" value="{{ request('min_price') }}">@endif
                        @if(request('max_price'))<input type="hidden" name="max_price" value="{{ request('max_price') }}">@endif
                        @foreach($filters as $key => $values)
                            @foreach((array) $values as $value)
                                <input type="hidden" name="filter[{{ $key }}][]" value="{{ $value }}">
                            @endforeach
                        @endforeach
                        <label class="sort-select">Sort by
                            <select name="sort" onchange="this.form.submit()">
                                <option value="newest" @selected(request('sort','newest')==='newest')>Newest</option>
                                <option value="price_asc" @selected(request('sort')==='price_asc')>Price: Low to High</option>
                                <option value="price_desc" @selected(request('sort')==='price_desc')>Price: High to Low</option>
                                <option value="best_sellers" @selected(request('sort')==='best_sellers')>Best Sellers</option>
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
                    @if(request('min_price')||request('max_price'))<span class="chip">Khoảng giá đã chọn</span>@endif
                    <a class="chip chip-clear" href="{{ url()->current() }}">Xóa tất cả ×</a>
                </div>
            @endif

            <div class="product-grid catalog-grid">
                @forelse($products as $product)
                    <x-product-card :product="$product" />
                @empty
                    <div class="empty product-grid-empty">
                        <span class="empty-orbit"></span>
                        <h3>Không tìm thấy sản phẩm phù hợp</h3>
                        <p>Hãy thử một từ khóa khác hoặc giảm bớt điều kiện lọc.</p>
                        <a class="button-outline" href="{{ url()->current() }}">Xóa bộ lọc</a>
                    </div>
                @endforelse
            </div>

            <div class="pagination">{{ $products->links() }}</div>
        </div>
    </div>
</section>
@endsection
