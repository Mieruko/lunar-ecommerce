<!doctype html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#0b0d12">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'LUNAR JEWELS' }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
<a class="skip-link" href="#main-content">{{ __('store.common.skip_to_content') }}</a>
<div class="promo">
    <div class="promo-inner">
        <span>{{ __('store.nav.promo_delivery') }}</span>
        <span class="promo-separator">✦</span>
        @auth<a href="{{ route('account.dashboard') }}">{{ __('store.nav.promo_my_lunar') }}</a>@else<a href="{{ route('tracking.form') }}">{{ __('store.nav.promo_tracking') }}</a>@endauth

        <div class="promo-locale" role="group" aria-label="{{ __('store.locale.label') }}">
            @foreach(['vi' => 'VI', 'en' => 'EN'] as $locale => $shortLabel)
                <form method="POST" action="{{ route('locale.switch', $locale) }}">
                    @csrf
                    <button type="submit" @class(['active' => app()->getLocale() === $locale]) aria-label="{{ __('store.locale.'.$locale) }}" aria-pressed="{{ app()->getLocale() === $locale ? 'true' : 'false' }}">{{ $shortLabel }}</button>
                </form>
            @endforeach
        </div>
    </div>
</div>

<header class="site-header">
    <div class="shell nav">
        <button class="mobile-toggle" data-menu-toggle aria-label="{{ __('store.common.open_menu') }}" aria-expanded="false" aria-controls="store-navigation">
            <span></span><span></span><span></span>
        </button>

        <a class="logo" href="{{ route('home') }}" aria-label="Lunar Jewels - {{ __('store.common.home') }}">
            <span class="logo-mark">LUNAR</span>
            <span class="logo-sub">JEWELS</span>
        </a>

        <nav id="store-navigation" class="nav-links" data-mobile-menu aria-label="{{ __('store.common.main_navigation') }}">
            <div class="mobile-menu-head">
                <div><span class="logo-mark">LUNAR</span><span class="logo-sub">JEWELS</span></div>
                <button type="button" data-menu-close aria-label="{{ __('store.common.close_menu') }}">×</button>
            </div>
            <form class="mobile-menu-search" action="{{ route('shop') }}" role="search">
                <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="11" cy="11" r="6.5"></circle><path d="m16 16 4 4"></path></svg>
                <input type="search" name="q" value="{{ request('q') }}" placeholder="{{ __('store.nav.search_mobile_placeholder') }}" aria-label="{{ __('store.nav.search_products') }}">
            </form>
            <a @class(['active' => request()->routeIs('shop') && request('sort') !== 'best_sellers']) href="{{ route('shop') }}"><span>01</span> {{ __('store.nav.new_in') }}</a>
            <a @class(['active' => request()->routeIs('watches')]) href="{{ route('watches') }}"><span>02</span> {{ __('store.nav.watches') }}</a>
            <a @class(['active' => request()->routeIs('jewelry')]) href="{{ route('jewelry') }}"><span>03</span> {{ __('store.nav.jewelry') }}</a>
            <a @class(['active' => request()->routeIs('shop') && request('sort') === 'best_sellers']) href="{{ route('shop', ['sort' => 'best_sellers']) }}"><span>04</span> {{ __('store.nav.best_sellers') }}</a>
            <div class="mobile-menu-support">
                @auth<a href="{{ route('account.dashboard') }}">My Lunar</a>@else<a href="{{ route('login') }}">{{ __('store.common.login') }}</a>@endauth
                <a href="{{ route('tracking.form') }}">{{ __('store.nav.track_short') }}</a>
                <a href="{{ route('support.warranty.form') }}">{{ __('store.nav.warranty') }}</a>
            </div>

            <div class="mobile-menu-locale">
                <span>{{ __('store.locale.label') }}</span>
                <div class="locale-switcher" role="group" aria-label="{{ __('store.locale.label') }}">
                    @foreach(['vi' => 'VI', 'en' => 'EN'] as $locale => $shortLabel)
                        <form method="POST" action="{{ route('locale.switch', $locale) }}">
                            @csrf
                            <button type="submit" @class(['active' => app()->getLocale() === $locale]) aria-label="{{ __('store.locale.'.$locale) }}" aria-pressed="{{ app()->getLocale() === $locale ? 'true' : 'false' }}">{{ $shortLabel }}</button>
                        </form>
                    @endforeach
                </div>
            </div>
        </nav>

        <form class="nav-search" action="{{ route('shop') }}" role="search">
            <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="11" cy="11" r="6.5"></circle><path d="m16 16 4 4"></path></svg>
            <input type="search" name="q" value="{{ request('q') }}" placeholder="{{ __('store.nav.search_placeholder') }}" aria-label="{{ __('store.nav.search_products') }}">
        </form>

        <div class="nav-actions">
            @auth
                @include('store.partials.notification-menu')
                <a class="nav-icon account-link" href="{{ route('account.dashboard') }}" aria-label="{{ __('store.common.account') }}">
                    <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="8" r="3.5"></circle><path d="M5.5 20c.8-4 3-6 6.5-6s5.7 2 6.5 6"></path></svg>
                    <span>{{ __('store.common.account') }}</span>
                </a>
            @else
                <a class="nav-icon account-link" href="{{ route('login') }}" aria-label="{{ __('store.common.login') }}">
                    <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="8" r="3.5"></circle><path d="M5.5 20c.8-4 3-6 6.5-6s5.7 2 6.5 6"></path></svg>
                    <span>{{ __('store.common.login') }}</span>
                </a>
            @endauth

            <a class="nav-icon bag-link" href="{{ route('cart.index') }}" aria-label="{{ __('store.common.cart') }}">
                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M5 8.5h14l-1 11H6l-1-11Z"></path><path d="M9 9V6.5a3 3 0 0 1 6 0V9"></path></svg>
                <span class="counter">{{ app(\App\Services\CartService::class)->itemCount(request()) }}</span>
            </a>
        </div>
    </div>
</header>
<button class="menu-backdrop" type="button" data-menu-close aria-label="{{ __('store.common.close_menu') }}"></button>

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
            <p>{{ __('store.footer.description') }}</p>
            <div class="footer-signature">Made for moments that stay.</div>
        </div>

        <div class="footer-column">
            <h4>{{ __('store.footer.explore') }}</h4>
            <a href="{{ route('shop') }}">{{ __('store.nav.new_in') }}</a>
            <a href="{{ route('watches') }}">{{ __('store.nav.watches') }}</a>
            <a href="{{ route('jewelry') }}">{{ __('store.nav.jewelry') }}</a>
            <a href="{{ route('shop', ['sort' => 'best_sellers']) }}">{{ __('store.nav.best_sellers') }}</a>
        </div>

        <div class="footer-column">
            <h4>{{ __('store.footer.services') }}</h4>
            <a href="{{ route('tracking.form') }}">{{ __('store.footer.track_order') }}</a>
            <a href="{{ route('cart.index') }}">{{ __('store.common.cart') }}</a>
            <a href="#">{{ __('store.footer.size_guide') }}</a>
            <a href="{{ route('support.return.form') }}">{{ __('store.footer.return_request') }}</a>
            <a href="{{ route('support.warranty.form') }}">{{ __('store.footer.warranty_request') }}</a>
        </div>

        <div class="footer-column">
            <h4>{{ __('store.footer.account') }}</h4>
            @auth
                <a href="{{ route('account.dashboard') }}">My Lunar</a>
                <a href="{{ route('account.notifications') }}">{{ __('store.common.notifications') }}</a>
                <a href="{{ route('account.orders') }}">{{ __('store.common.orders') }}</a>
            @else
                <a href="{{ route('login') }}">{{ __('store.common.login') }}</a>
                <a href="{{ route('register') }}">{{ __('store.common.register') }}</a>
            @endauth
        </div>
    </div>

    <div class="shell footer-bottom">
        <span>© {{ date('Y') }} LUNAR JEWELS</span>
        <span>Authenticity · Craft · Service</span>
    </div>
</footer>

@include('store.partials.support-chat')
</body>
</html>
