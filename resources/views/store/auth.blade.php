@extends('layouts.store', ['title' => ($mode === 'login' ? __('store.auth.login_title') : __('store.auth.register_title')).' | LUNAR JEWELS'])

@section('content')
<section class="auth-page">
    <div class="shell auth-grid">
        <div class="auth-art" aria-hidden="true">
            <div class="auth-art-orbit orbit-a"></div>
            <div class="auth-art-orbit orbit-b"></div>
            <div class="auth-art-moon"></div>
            <span class="auth-art-index">LJ / 01</span>
            <div class="auth-art-copy">
                <span>LUNAR JEWELS</span>
                <p>Pieces chosen for the moments that become part of your story.</p>
            </div>
        </div>

        <div class="auth-panel">
            <div class="auth-panel-inner">
                <span class="eyebrow">{{ __('store.auth.kicker') }}</span>
                <h1>{{ $mode === 'login' ? __('store.auth.welcome_back') : __('store.auth.start_journey') }}</h1>
                <p class="auth-intro">{{ $mode === 'login' ? __('store.auth.login_intro') : __('store.auth.register_intro') }}</p>

                <form class="auth-form" method="POST" action="{{ $mode === 'login' ? route('login.store') : route('register.store') }}">
                    @csrf

                    @if($mode === 'register')
                        <label class="field">
                            <span>{{ __('store.auth.name') }}</span>
                            <input name="name" value="{{ old('name') }}" autocomplete="name" required>
                        </label>
                        <label class="field">
                            <span>{{ __('store.auth.phone') }}</span>
                            <input name="phone" value="{{ old('phone') }}" autocomplete="tel">
                        </label>
                    @endif

                    <label class="field">
                        <span>Email</span>
                        <input name="email" type="email" value="{{ old('email') }}" autocomplete="email" required>
                    </label>
                    <label class="field">
                        <span>{{ __('store.auth.password') }}</span>
                        <input name="password" type="password" autocomplete="{{ $mode === 'login' ? 'current-password' : 'new-password' }}" required>
                    </label>

                    @if($mode === 'register')
                        <label class="field">
                            <span>{{ __('store.auth.confirm_password') }}</span>
                            <input name="password_confirmation" type="password" autocomplete="new-password" required>
                        </label>
                    @else
                        <label class="remember-row"><input type="checkbox" name="remember"><span>{{ __('store.auth.remember') }}</span></label>
                    @endif

                    <button class="button auth-submit" type="submit">{{ $mode === 'login' ? __('store.common.login') : __('store.auth.create_account') }} <span>→</span></button>
                </form>

                <div class="auth-switch">
                    <span>{{ $mode === 'login' ? __('store.auth.no_account') : __('store.auth.has_account') }}</span>
                    <a class="text-link" href="{{ $mode === 'login' ? route('register') : route('login') }}">{{ $mode === 'login' ? __('store.common.register') : __('store.common.login') }}</a>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection
