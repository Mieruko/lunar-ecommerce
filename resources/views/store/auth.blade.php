@extends('layouts.store', ['title' => ($mode === 'login' ? 'Đăng nhập' : 'Đăng ký').' | LUNAR JEWELS'])

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
                <span class="eyebrow">Private client account</span>
                <h1>{{ $mode === 'login' ? 'Chào mừng trở lại.' : 'Bắt đầu hành trình của bạn.' }}</h1>
                <p class="auth-intro">{{ $mode === 'login' ? 'Đăng nhập để theo dõi đơn hàng và quản lý thông tin của bạn.' : 'Tạo tài khoản để lưu thông tin giao hàng và theo dõi những đơn hàng tiếp theo.' }}</p>

                <form class="auth-form" method="POST" action="{{ $mode === 'login' ? route('login.store') : route('register.store') }}">
                    @csrf

                    @if($mode === 'register')
                        <label class="field">
                            <span>Họ và tên</span>
                            <input name="name" value="{{ old('name') }}" autocomplete="name" required>
                        </label>
                        <label class="field">
                            <span>Số điện thoại</span>
                            <input name="phone" value="{{ old('phone') }}" autocomplete="tel">
                        </label>
                    @endif

                    <label class="field">
                        <span>Email</span>
                        <input name="email" type="email" value="{{ old('email') }}" autocomplete="email" required>
                    </label>
                    <label class="field">
                        <span>Mật khẩu</span>
                        <input name="password" type="password" autocomplete="{{ $mode === 'login' ? 'current-password' : 'new-password' }}" required>
                    </label>

                    @if($mode === 'register')
                        <label class="field">
                            <span>Xác nhận mật khẩu</span>
                            <input name="password_confirmation" type="password" autocomplete="new-password" required>
                        </label>
                    @else
                        <label class="remember-row"><input type="checkbox" name="remember"><span>Ghi nhớ đăng nhập trên thiết bị này</span></label>
                    @endif

                    <button class="button auth-submit" type="submit">{{ $mode === 'login' ? 'Đăng nhập' : 'Tạo tài khoản' }} <span>→</span></button>
                </form>

                <div class="auth-switch">
                    <span>{{ $mode === 'login' ? 'Chưa có tài khoản?' : 'Đã có tài khoản?' }}</span>
                    <a class="text-link" href="{{ $mode === 'login' ? route('register') : route('login') }}">{{ $mode === 'login' ? 'Đăng ký' : 'Đăng nhập' }}</a>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection
