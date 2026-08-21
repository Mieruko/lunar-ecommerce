<!doctype html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#0b0d12">
    <title>{{ $title ?? 'LUNAR JEWELS' }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
<a class="skip-link" href="#main-content">Đi tới nội dung chính</a>
<div class="promo">
    <div class="promo-inner">
        <span>Complimentary delivery cho đơn từ 5.000.000 ₫</span>
        <span class="promo-separator">✦</span>
        @auth<a href="{{ route('account.dashboard') }}">My Lunar · Theo dõi đơn tự động</a>@else<a href="{{ route('tracking.form') }}">Theo dõi đơn hàng</a>@endauth
    </div>
</div>

<header class="site-header">
    <div class="shell nav">
        <button class="mobile-toggle" data-menu-toggle aria-label="Mở menu" aria-expanded="false" aria-controls="store-navigation">
            <span></span><span></span><span></span>
        </button>

        <a class="logo" href="{{ route('home') }}" aria-label="Lunar Jewels - Trang chủ">
            <span class="logo-mark">LUNAR</span>
            <span class="logo-sub">JEWELS</span>
        </a>

        <nav id="store-navigation" class="nav-links" data-mobile-menu aria-label="Điều hướng chính">
            <div class="mobile-menu-head">
                <div><span class="logo-mark">LUNAR</span><span class="logo-sub">JEWELS</span></div>
                <button type="button" data-menu-close aria-label="Đóng menu">×</button>
            </div>
            <form class="mobile-menu-search" action="{{ route('shop') }}" role="search">
                <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="11" cy="11" r="6.5"></circle><path d="m16 16 4 4"></path></svg>
                <input type="search" name="q" value="{{ request('q') }}" placeholder="Tìm đồng hồ, trang sức..." aria-label="Tìm kiếm sản phẩm">
            </form>
            <a @class(['active' => request()->routeIs('shop') && request('sort') !== 'best_sellers']) href="{{ route('shop') }}"><span>01</span> New In</a>
            <a @class(['active' => request()->routeIs('watches')]) href="{{ route('watches') }}"><span>02</span> Watches</a>
            <a @class(['active' => request()->routeIs('jewelry')]) href="{{ route('jewelry') }}"><span>03</span> Jewelry</a>
            <a @class(['active' => request()->routeIs('shop') && request('sort') === 'best_sellers']) href="{{ route('shop', ['sort' => 'best_sellers']) }}"><span>04</span> Best Sellers</a>
            <div class="mobile-menu-support">
                @auth<a href="{{ route('account.dashboard') }}">My Lunar</a>@else<a href="{{ route('login') }}">Đăng nhập</a>@endauth
                <a href="{{ route('tracking.form') }}">Theo dõi đơn</a>
                <a href="{{ route('support.warranty.form') }}">Bảo hành</a>
            </div>
        </nav>

        <form class="nav-search" action="{{ route('shop') }}" role="search">
            <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="11" cy="11" r="6.5"></circle><path d="m16 16 4 4"></path></svg>
            <input type="search" name="q" value="{{ request('q') }}" placeholder="Tìm kiếm Lunar Jewels" aria-label="Tìm kiếm sản phẩm">
        </form>

        <div class="nav-actions">
            @auth
                @include('store.partials.notification-menu')
                <a class="nav-icon account-link" href="{{ route('account.dashboard') }}" aria-label="Tài khoản">
                    <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="8" r="3.5"></circle><path d="M5.5 20c.8-4 3-6 6.5-6s5.7 2 6.5 6"></path></svg>
                    <span>Tài khoản</span>
                </a>
            @else
                <a class="nav-icon account-link" href="{{ route('login') }}" aria-label="Đăng nhập">
                    <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="8" r="3.5"></circle><path d="M5.5 20c.8-4 3-6 6.5-6s5.7 2 6.5 6"></path></svg>
                    <span>Đăng nhập</span>
                </a>
            @endauth

            <a class="nav-icon bag-link" href="{{ route('cart.index') }}" aria-label="Giỏ hàng">
                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M5 8.5h14l-1 11H6l-1-11Z"></path><path d="M9 9V6.5a3 3 0 0 1 6 0V9"></path></svg>
                <span class="counter">{{ app(\App\Services\CartService::class)->itemCount(request()) }}</span>
            </a>
        </div>
    </div>
</header>
<button class="menu-backdrop" type="button" data-menu-close aria-label="Đóng menu"></button>

<main id="main-content">
    <div class="shell flash-stack">
        @if(session('success'))<div class="notice">{{ session('success') }}</div>@endif
        @if(session('error'))<div class="error">{{ session('error') }}</div>@endif
        @if($errors->any())<div class="error">{{ $errors->first() }}</div>@endif
    </div>
    @yield('content')
</main>

<footer class="footer">
    <div class="shell footer-top">
        <div class="footer-brand">
            <a class="logo logo-light" href="{{ route('home') }}">
                <span class="logo-mark">LUNAR</span>
                <span class="logo-sub">JEWELS</span>
            </a>
            <p>Đồng hồ và trang sức tuyển chọn dành cho những dấu mốc xứng đáng được ghi nhớ.</p>
            <div class="footer-signature">Made for moments that stay.</div>
        </div>

        <div class="footer-column">
            <h4>Khám phá</h4>
            <a href="{{ route('shop') }}">New In</a>
            <a href="{{ route('watches') }}">Watches</a>
            <a href="{{ route('jewelry') }}">Jewelry</a>
            <a href="{{ route('shop', ['sort' => 'best_sellers']) }}">Best Sellers</a>
        </div>

        <div class="footer-column">
            <h4>Dịch vụ</h4>
            <a href="{{ route('tracking.form') }}">Theo dõi đơn hàng</a>
            <a href="{{ route('cart.index') }}">Giỏ hàng</a>
            <a href="#">Hướng dẫn chọn size</a>
            <a href="{{ route('support.return.form') }}">Yêu cầu đổi trả</a>
            <a href="{{ route('support.warranty.form') }}">Yêu cầu bảo hành</a>
        </div>

        <div class="footer-column">
            <h4>Tài khoản</h4>
            @auth
                <a href="{{ route('account.dashboard') }}">My Lunar</a>
                <a href="{{ route('account.notifications') }}">Thông báo</a>
                <a href="{{ route('account.orders') }}">Đơn hàng</a>
            @else
                <a href="{{ route('login') }}">Đăng nhập</a>
                <a href="{{ route('register') }}">Đăng ký</a>
            @endauth
        </div>
    </div>

    <div class="shell footer-bottom">
        <span>© {{ date('Y') }} LUNAR JEWELS</span>
        <span>Authenticity · Craft · Service</span>
    </div>
</footer>
</body>
</html>
