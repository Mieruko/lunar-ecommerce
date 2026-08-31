@extends('layouts.store', ['title' => __('store.home.page_title')])

@section('content')
<section class="home-hero">
    <div class="shell home-hero-grid">
        <div class="home-hero-copy">
            <span class="eyebrow eyebrow-light">Lunar Collection · 2026</span>
            <h1 class="display">A QUIET<br><em>kind of</em><br>BRILLIANCE</h1>
            <p>{{ __('store.home.hero_copy') }}</p>
            <div class="hero-actions">
                <a class="button button-light" href="{{ route('watches') }}">{{ __('store.home.explore_watches') }}</a>
                <a class="text-link text-link-light" href="{{ route('jewelry') }}">{{ __('store.home.explore_jewelry') }} <span>↗</span></a>
            </div>
            <div class="hero-meta">
                <span><b>01</b> {{ __('store.home.authentic_pieces') }}</span>
                <span><b>02</b> {{ __('store.home.curated_selection') }}</span>
                <span><b>03</b> {{ __('store.home.personal_service') }}</span>
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

<section class="home-value-bar" aria-label="{{ __('store.home.services_aria') }}" data-reveal="fade">
    <div class="shell home-value-grid">
        <div><span>◇</span><p><b>{{ __('store.home.transparent_title') }}</b><small>{{ __('store.home.transparent_copy') }}</small></p></div>
        <div><span>↗</span><p><b>{{ __('store.home.tracking_title') }}</b><small>{{ __('store.home.tracking_copy') }}</small></p></div>
        <a href="{{ route('support.warranty.form') }}"><span>○</span><p><b>{{ __('store.home.aftercare_title') }}</b><small>{{ __('store.home.aftercare_copy') }}</small></p></a>
        @auth
            <a href="{{ route('account.dashboard') }}"><span>◎</span><p><b>My Lunar</b><small>{{ __('store.home.my_lunar_copy') }}</small></p></a>
        @else
            <a href="{{ route('register') }}"><span>◎</span><p><b>{{ __('store.home.join_my_lunar') }}</b><small>{{ __('store.home.join_my_lunar_copy') }}</small></p></a>
        @endauth
    </div>
</section>

<section class="section shell intro-section">
    <div class="intro-kicker" data-reveal="fade">LUNAR / JEWELS</div>
    <div class="intro-copy" data-reveal>
        <span class="eyebrow">The House</span>
        <h2 class="display section-title">OBJECTS OF TIME.<br>OBJECTS OF LIGHT.</h2>
        <p>{{ __('store.home.house_copy') }}</p>
    </div>
</section>

<section class="section shell">
    <div class="collection-duo" data-reveal-group>
        <a data-reveal="scale" class="collection-panel collection-watch" href="{{ route('watches') }}">
            <div class="collection-index">01</div>
            <div class="collection-art collection-art-watch" aria-hidden="true">
                <img src="{{ asset('images/hero-moon-dial.webp') }}"
                     srcset="{{ asset('images/hero-moon-dial-sm.webp') }} 760w, {{ asset('images/hero-moon-dial.webp') }} 1400w"
                     sizes="(max-width: 900px) 100vw, 50vw"
                     alt="" loading="lazy" decoding="async">
            </div>
            <div class="collection-copy">
                <span class="eyebrow eyebrow-light">Lunar Watches</span>
                <h3>TIME, REFINED.</h3>
                <p>{{ __('store.home.watch_copy') }}</p>
                <span class="text-link text-link-light">{{ __('store.home.view_collection') }} <span>↗</span></span>
            </div>
        </a>

        <a data-reveal="scale" class="collection-panel collection-jewel" href="{{ route('jewelry') }}">
            <div class="collection-index">02</div>
            <div class="collection-art collection-art-jewel" aria-hidden="true">
                <img src="{{ asset('images/products/concepts/eternal-pair-wedding-bands/eternal-pair-wedding-bands-04.jpg') }}"
                     alt="" loading="lazy" decoding="async">
            </div>
            <div class="collection-copy">
                <span class="eyebrow">Lunar Jewels</span>
                <h3>LIGHT, HELD CLOSE.</h3>
                <p>{{ __('store.home.jewelry_copy') }}</p>
                <span class="text-link">{{ __('store.home.view_collection') }} <span>↗</span></span>
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
        <a class="text-link" href="{{ route('shop') }}">{{ __('store.home.view_all') }} <span>↗</span></a>
    </div>

    <div class="product-grid" data-reveal-group>
        @forelse($featured as $product)
            <div data-reveal><x-product-card :product="$product" /></div>
        @empty
            <div class="empty product-grid-empty"><h3>{{ __('store.home.featured_empty') }}</h3><p>{{ __('store.home.featured_empty_copy') }}</p></div>
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
            <p>{{ __('store.home.editorial_copy') }}</p>
            <a class="button button-light" href="{{ route('jewelry') }}">{{ __('store.home.explore_jewelry') }}</a>
        </div>
    </div>
</section>

<section class="section shell product-showcase latest-showcase">
    <div class="section-head luxury-head" data-reveal>
        <div>
            <span class="eyebrow">Just arrived</span>
            <h2 class="display section-title">NEW IN</h2>
        </div>
        <a class="text-link" href="{{ route('shop', ['sort' => 'newest']) }}">{{ __('store.home.latest_link') }} <span>↗</span></a>
    </div>

    <div class="product-grid" data-reveal-group>
        @forelse($latest as $product)
            <div data-reveal><x-product-card :product="$product" /></div>
        @empty
            <div class="empty product-grid-empty"><h3>{{ __('store.home.latest_empty') }}</h3></div>
        @endforelse
    </div>
</section>

<section class="section shell service-strip" data-reveal-group>
    <div class="service-item" data-reveal><span class="service-number">01</span><div><b>{{ __('store.home.service_authenticity') }}</b><p>{{ __('store.home.service_authenticity_copy') }}</p></div></div>
    <div class="service-item" data-reveal><span class="service-number">02</span><div><b>{{ __('store.home.service_aftercare') }}</b><p>{{ __('store.home.service_aftercare_copy') }}</p></div></div>
    <div class="service-item" data-reveal><span class="service-number">03</span><div><b>{{ __('store.home.service_delivery') }}</b><p>{{ __('store.home.service_delivery_copy') }}</p></div></div>
</section>
@endsection
