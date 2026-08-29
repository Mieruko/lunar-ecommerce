@extends('layouts.store', ['title' => 'LUNAR JEWELS | Đồng hồ & Trang sức'])

@section('content')
<section class="home-hero">
    <div class="shell home-hero-grid">
        <div class="home-hero-copy">
            <span class="eyebrow eyebrow-light">Lunar Collection · 2026</span>
            <h1 class="display">A QUIET<br><em>kind of</em><br>BRILLIANCE</h1>
            <p>Vẻ đẹp không cần phô trương. Từ chiếc đồng hồ moonphase trên cổ tay đến món trang sức giữ lấy ánh sáng — mỗi thiết kế đều được tuyển chọn dưới cảm hứng của ánh trăng và những khoảnh khắc vượt thời gian.</p>
            <div class="hero-actions">
                <a class="button button-light" href="{{ route('watches') }}">Khám phá Watches</a>
                <a class="text-link text-link-light" href="{{ route('jewelry') }}">Khám phá Jewelry <span>↗</span></a>
            </div>
            <div class="hero-meta">
                <span><b>01</b> Authentic pieces</span>
                <span><b>02</b> Curated selection</span>
                <span><b>03</b> Personal service</span>
            </div>
        </div>

        <div class="home-hero-art" aria-hidden="true">
            <img class="hero-photo"
                 src="{{ asset('images/hero-moon-dial.webp') }}"
                 srcset="{{ asset('images/hero-moon-dial-sm.webp') }} 760w, {{ asset('images/hero-moon-dial.webp') }} 1400w"
                 sizes="(max-width: 900px) 100vw, 48vw"
                 alt="" fetchpriority="high" decoding="async">
            <span class="hero-vertical-word">LUNAR</span>
            <span class="hero-edition">JEWELS · EDITION 01</span>
        </div>
    </div>
</section>

<section class="home-value-bar" aria-label="Dịch vụ Lunar Jewels" data-reveal="fade">
    <div class="shell home-value-grid">
        <div><span>◇</span><p><b>Sản phẩm minh bạch</b><small>Thông tin chất liệu và nguồn gốc rõ ràng</small></p></div>
        <div><span>↗</span><p><b>Giao hàng có theo dõi</b><small>Cập nhật tiến trình trong My Lunar</small></p></div>
        <a href="{{ route('support.warranty.form') }}"><span>○</span><p><b>Chăm sóc hậu mãi</b><small>Bảo hành và đổi trả thuận tiện</small></p></a>
        @auth
            <a href="{{ route('account.dashboard') }}"><span>◎</span><p><b>My Lunar</b><small>Đơn hàng, ưu đãi và thông báo</small></p></a>
        @else
            <a href="{{ route('register') }}"><span>◎</span><p><b>Gia nhập My Lunar</b><small>Lưu đơn hàng và sản phẩm yêu thích</small></p></a>
        @endauth
    </div>
</section>

<section class="section shell intro-section">
    <div class="intro-kicker" data-reveal="fade">LUNAR / JEWELS</div>
    <div class="intro-copy" data-reveal>
        <span class="eyebrow">The House</span>
        <h2 class="display section-title">OBJECTS OF TIME.<br>OBJECTS OF LIGHT.</h2>
        <p>Mỗi thiết kế được chọn vì tỷ lệ, chất liệu và cảm giác nó để lại — từ chuyển động chính xác trên cổ tay đến ánh phản chiếu tinh tế của một món trang sức.</p>
    </div>
</section>

<section class="section shell">
    <div class="collection-duo" data-reveal-group>
        <a data-reveal="scale" class="collection-panel collection-watch" href="{{ route('watches') }}">
            <div class="collection-index">01</div>
            <div class="collection-art collection-art-watch">
                <span class="watch-dial"></span>
                <span class="watch-hand watch-hand-one"></span>
                <span class="watch-hand watch-hand-two"></span>
            </div>
            <div class="collection-copy">
                <span class="eyebrow eyebrow-light">Lunar Watches</span>
                <h3>TIME, REFINED.</h3>
                <p>Những cỗ máy mang nhịp điệu chính xác, được chọn cho phong cách sống hiện đại.</p>
                <span class="text-link text-link-light">View collection <span>↗</span></span>
            </div>
        </a>

        <a data-reveal="scale" class="collection-panel collection-jewel" href="{{ route('jewelry') }}">
            <div class="collection-index">02</div>
            <div class="collection-art collection-art-jewel">
                <span class="jewel-ring jewel-ring-one"></span>
                <span class="jewel-ring jewel-ring-two"></span>
                <span class="jewel-stone"></span>
            </div>
            <div class="collection-copy">
                <span class="eyebrow">Lunar Jewels</span>
                <h3>LIGHT, HELD CLOSE.</h3>
                <p>Kim loại, đá quý và đường nét tối giản tạo nên một dấu ấn riêng nhưng không ồn ào.</p>
                <span class="text-link">View collection <span>↗</span></span>
            </div>
        </a>
    </div>
</section>

<section class="section shell product-showcase">
    <div class="section-head luxury-head" data-reveal>
        <div>
            <span class="eyebrow">Curated selection</span>
            <h2 class="display section-title">FEATURED PIECES</h2>
        </div>
        <a class="text-link" href="{{ route('shop') }}">Xem tất cả <span>↗</span></a>
    </div>

    <div class="product-grid" data-reveal-group>
        @forelse($featured as $product)
            <div data-reveal><x-product-card :product="$product" /></div>
        @empty
            <div class="empty product-grid-empty"><h3>Chưa có sản phẩm nổi bật</h3><p>Hãy đánh dấu Featured cho sản phẩm trong trang quản trị.</p></div>
        @endforelse
    </div>
</section>

<section class="editorial-band">
    <div class="shell editorial-grid" data-reveal-group>
        <div class="editorial-art" aria-hidden="true" data-reveal="scale">
            <div class="editorial-moon"></div>
            <span>JEWELS</span>
        </div>
        <div class="editorial-copy" data-reveal>
            <span class="eyebrow eyebrow-light">New chapter</span>
            <h2 class="display">FOR THE MOMENTS<br>THAT BECOME <em>yours.</em></h2>
            <p>Một món quà, một cột mốc, hay đơn giản là lựa chọn dành cho chính bạn. Lunar Jewels tin rằng những vật phẩm ý nghĩa nhất luôn gắn với một câu chuyện riêng.</p>
            <a class="button button-light" href="{{ route('jewelry') }}">Khám phá Jewelry</a>
        </div>
    </div>
</section>

<section class="section shell product-showcase latest-showcase">
    <div class="section-head luxury-head" data-reveal>
        <div>
            <span class="eyebrow">Just arrived</span>
            <h2 class="display section-title">NEW IN</h2>
        </div>
        <a class="text-link" href="{{ route('shop', ['sort' => 'newest']) }}">Khám phá mới nhất <span>↗</span></a>
    </div>

    <div class="product-grid" data-reveal-group>
        @forelse($latest as $product)
            <div data-reveal><x-product-card :product="$product" /></div>
        @empty
            <div class="empty product-grid-empty"><h3>Chưa có sản phẩm mới</h3></div>
        @endforelse
    </div>
</section>

<section class="section shell service-strip" data-reveal-group>
    <div class="service-item" data-reveal><span class="service-number">01</span><div><b>Authenticity assured</b><p>Nguồn gốc và thông tin sản phẩm minh bạch.</p></div></div>
    <div class="service-item" data-reveal><span class="service-number">02</span><div><b>Care after purchase</b><p>Bảo hành và hành trình đơn hàng rõ ràng.</p></div></div>
    <div class="service-item" data-reveal><span class="service-number">03</span><div><b>Considered delivery</b><p>Đóng gói cẩn thận và hỗ trợ theo dõi đơn.</p></div></div>
</section>
@endsection
